<?php

namespace Core;

use Core\Support\Security;
use Core\Support\Session;
use Core\Support\RateLimiter;

final class Auth
{
    private const SESSION_KEY = 'admin_user_id';
    private const SESSION_PASSWORD_FINGERPRINT = 'admin_password_fingerprint';
    private const DUMMY_PASSWORD_HASH = '$2y$10$X5NZScxSiYQ.vRiuP/Fas.NubLmSM7lPRfm8gp.tc3gJTJBOTn67u';
    private static ?array $userCache = null;

    public static function attempt(string $identifier, string $password): array
    {
        $normalizedIdentifier = strtolower(trim($identifier));
        $accountThrottleKey = 'login-account:' . hash('sha256', $normalizedIdentifier);
        $ipThrottleKey = 'login-ip:' . \Core\Support\Request::ip();

        if (RateLimiter::tooManyAttempts($accountThrottleKey, 8, 15)
            || RateLimiter::tooManyAttempts($ipThrottleKey, 30, 15)) {
            return ['ok' => false, 'message' => 'تم إيقاف المحاولة مؤقتًا بسبب تكرار محاولات الدخول الفاشلة. حاول لاحقًا بعد 15 دقيقة.'];
        }

        $user = Database::fetchOne(
            'SELECT u.*, r.slug AS role_slug, r.name AS role_name FROM ' . Database::table('admin_users') . ' u
             LEFT JOIN ' . Database::table('roles') . ' r ON r.id = u.role_id
             WHERE (u.username = ? OR u.email = ?) LIMIT 1',
            [$identifier, $identifier]
        );

        $isActive = $user !== null && $user['status'] === 'active';
        $passwordHash = $isActive ? $user['password_hash'] : self::DUMMY_PASSWORD_HASH;
        $validPassword = Security::verifyPassword($password, $passwordHash);

        if (!$isActive || !$validPassword) {
            RateLimiter::hit($accountThrottleKey);
            RateLimiter::hit($ipThrottleKey);
            ActivityLog::record('login_failed', 'محاولة دخول فاشلة للمستخدم: ' . $identifier);
            return ['ok' => false, 'message' => 'بيانات الدخول غير صحيحة.'];
        }

        if (password_needs_rehash($user['password_hash'], PASSWORD_DEFAULT)) {
            $user['password_hash'] = Security::hashPassword($password);
            Database::update('admin_users', ['password_hash' => $user['password_hash']], ['id' => $user['id']]);
        }

        RateLimiter::clear($accountThrottleKey);

        if (TwoFactor::enabled((int) $user['id'])) {
            Session::regenerate();
            TwoFactor::beginChallenge((int) $user['id']);
            return ['ok' => true, 'requires_2fa' => true];
        }

        self::startAuthenticatedSession($user);

        return ['ok' => true, 'requires_2fa' => false];
    }

    public static function completeTwoFactor(int $userId): bool
    {
        if (TwoFactor::pendingUserId() !== $userId) {
            return false;
        }
        $user = Database::fetchOne(
            'SELECT id, password_hash, status FROM ' . Database::table('admin_users') . ' WHERE id = ? LIMIT 1',
            [$userId]
        );
        if (!$user || $user['status'] !== 'active') {
            TwoFactor::clearChallenge();
            return false;
        }
        TwoFactor::clearChallenge();
        self::startAuthenticatedSession($user);
        return true;
    }

    public static function logout(): void
    {
        ActivityLog::record('logout', 'تسجيل خروج');
        Session::remove(self::SESSION_KEY);
        Session::remove(self::SESSION_PASSWORD_FINGERPRINT);
        self::$userCache = null;
        Session::destroy();
    }

    public static function check(): bool
    {
        return Session::has(self::SESSION_KEY) && self::user() !== null;
    }

    public static function user(): ?array
    {
        if (self::$userCache !== null) {
            return self::$userCache;
        }

        $id = Session::get(self::SESSION_KEY);
        if (!$id) {
            return null;
        }

        $user = Database::fetchOne(
            'SELECT u.id, u.name, u.email, u.username, u.password_hash, u.role_id, u.status, r.slug AS role_slug, r.name AS role_name
             FROM ' . Database::table('admin_users') . ' u
             LEFT JOIN ' . Database::table('roles') . ' r ON r.id = u.role_id
             WHERE u.id = ? LIMIT 1',
            [$id]
        );

        if (!$user || $user['status'] !== 'active') {
            return null;
        }

        $fingerprint = (string) Session::get(self::SESSION_PASSWORD_FINGERPRINT, '');
        if ($fingerprint === '' || !hash_equals(self::passwordFingerprint($user['password_hash']), $fingerprint)) {
            Session::remove(self::SESSION_KEY);
            Session::remove(self::SESSION_PASSWORD_FINGERPRINT);
            return null;
        }

        unset($user['password_hash']);

        return self::$userCache = $user;
    }

    private static function passwordFingerprint(string $passwordHash): string
    {
        $key = defined('AUTH_KEY') ? AUTH_KEY : 'session-password-fingerprint';
        return hash_hmac('sha256', $passwordHash, $key);
    }

    private static function startAuthenticatedSession(array $user): void
    {
        Session::regenerate();
        Session::set(self::SESSION_KEY, $user['id']);
        Session::set(self::SESSION_PASSWORD_FINGERPRINT, self::passwordFingerprint($user['password_hash']));
        Database::update('admin_users', ['last_login_at' => date('Y-m-d H:i:s')], ['id' => $user['id']]);
        self::$userCache = null;
        ActivityLog::record('login', 'تسجيل دخول ناجح');
    }

    public static function can(string $permission): bool
    {
        $user = self::user();
        if (!$user) {
            return false;
        }

        if ($user['role_slug'] === 'system_admin') {
            return true;
        }

        static $permCache = [];
        $roleId = $user['role_id'];

        if (!isset($permCache[$roleId])) {
            $rows = Database::fetchAll(
                'SELECT permission_key FROM ' . Database::table('role_permissions') . ' WHERE role_id = ?',
                [$roleId]
            );
            $permCache[$roleId] = array_column($rows, 'permission_key');
        }

        return in_array($permission, $permCache[$roleId], true);
    }

    public static function requireLogin(): void
    {
        if (!self::check()) {
            \Core\Support\Response::redirect(\Core\Support\Url::admin('login'));
        }
    }

    public static function requirePermission(string $permission): void
    {
        self::requireLogin();
        if (!self::can($permission)) {
            http_response_code(403);
            echo 'لا تملك صلاحية الوصول لهذا القسم.';
            exit;
        }
    }
}
