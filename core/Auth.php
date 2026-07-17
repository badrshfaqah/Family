<?php

namespace Core;

use Core\Support\Security;
use Core\Support\Session;
use Core\Support\RateLimiter;

final class Auth
{
    private const SESSION_KEY = 'admin_user_id';
    private static ?array $userCache = null;

    public static function attempt(string $identifier, string $password): array
    {
        $throttleKey = 'login:' . strtolower($identifier) . ':' . \Core\Support\Request::ip();

        if (RateLimiter::tooManyAttempts($throttleKey, 8, 15)) {
            return ['ok' => false, 'message' => 'تم إيقاف المحاولة مؤقتًا بسبب تكرار محاولات الدخول الفاشلة. حاول لاحقًا بعد 15 دقيقة.'];
        }

        $user = Database::fetchOne(
            'SELECT u.*, r.slug AS role_slug, r.name AS role_name FROM ' . Database::table('admin_users') . ' u
             LEFT JOIN ' . Database::table('roles') . ' r ON r.id = u.role_id
             WHERE (u.username = ? OR u.email = ?) LIMIT 1',
            [$identifier, $identifier]
        );

        if (!$user || $user['status'] !== 'active' || !Security::verifyPassword($password, $user['password_hash'])) {
            RateLimiter::hit($throttleKey);
            ActivityLog::record('login_failed', 'محاولة دخول فاشلة للمستخدم: ' . $identifier);
            return ['ok' => false, 'message' => 'بيانات الدخول غير صحيحة.'];
        }

        RateLimiter::clear($throttleKey);

        Session::regenerate();
        Session::set(self::SESSION_KEY, $user['id']);

        Database::update('admin_users', ['last_login_at' => date('Y-m-d H:i:s')], ['id' => $user['id']]);

        self::$userCache = null;
        ActivityLog::record('login', 'تسجيل دخول ناجح');

        return ['ok' => true];
    }

    public static function logout(): void
    {
        ActivityLog::record('logout', 'تسجيل خروج');
        Session::remove(self::SESSION_KEY);
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
            'SELECT u.id, u.name, u.email, u.username, u.role_id, u.status, r.slug AS role_slug, r.name AS role_name
             FROM ' . Database::table('admin_users') . ' u
             LEFT JOIN ' . Database::table('roles') . ' r ON r.id = u.role_id
             WHERE u.id = ? LIMIT 1',
            [$id]
        );

        if (!$user || $user['status'] !== 'active') {
            return null;
        }

        return self::$userCache = $user;
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
