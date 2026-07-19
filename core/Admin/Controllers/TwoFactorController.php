<?php

namespace Core\Admin\Controllers;

use Core\ActivityLog;
use Core\Auth;
use Core\Settings;
use Core\TwoFactor;
use Core\Support\Csrf;
use Core\Support\RateLimiter;
use Core\Support\Request;
use Core\Support\Response;
use Core\Support\Session;
use Core\Support\Url;
use Core\View;

final class TwoFactorController
{
    private const SETUP_SECRET_KEY = '_2fa_setup_secret';
    private const RECOVERY_CODES_KEY = '_2fa_recovery_codes_once';

    public function challengeForm(array $params): void
    {
        if (!TwoFactor::pendingUserId()) {
            Response::redirect(Url::admin('login'));
        }
        echo View::render(CORE_PATH . '/Admin/Views/auth/two-factor.php', ['error' => null]);
    }

    public function challenge(array $params): void
    {
        Csrf::verifyRequestOrFail();
        $userId = TwoFactor::pendingUserId();
        if (!$userId) {
            Response::redirect(Url::admin('login'));
        }

        $key = 'login-2fa:' . $userId . ':' . Request::ip();
        if (RateLimiter::tooManyAttempts($key, 8, 15)) {
            echo View::render(CORE_PATH . '/Admin/Views/auth/two-factor.php', ['error' => 'محاولات كثيرة. حاول مجددًا بعد 15 دقيقة.']);
            return;
        }

        if (!TwoFactor::verifyForUser($userId, Request::trimmed('code'))) {
            RateLimiter::hit($key);
            echo View::render(CORE_PATH . '/Admin/Views/auth/two-factor.php', ['error' => 'رمز التحقق غير صحيح.']);
            return;
        }

        RateLimiter::clear($key);
        if (!Auth::completeTwoFactor($userId)) {
            Response::redirect(Url::admin('login'));
        }
        Response::redirect(Url::admin(''));
    }

    public function settings(array $params): void
    {
        Auth::requireLogin();
        $user = Auth::user();
        $enabled = TwoFactor::enabled((int) $user['id']);
        $secret = '';
        $uri = '';
        if (!$enabled) {
            $secret = (string) Session::get(self::SETUP_SECRET_KEY, '');
            if ($secret === '') {
                $secret = TwoFactor::generateSecret();
                Session::set(self::SETUP_SECRET_KEY, $secret);
            }
            $issuer = Settings::get('identity_short_name', '') ?: 'Family CMS';
            $uri = TwoFactor::provisioningUri($secret, $user['email'], $issuer);
        }

        $recoveryCodes = Session::get(self::RECOVERY_CODES_KEY, []);
        Session::remove(self::RECOVERY_CODES_KEY);
        echo View::render(CORE_PATH . '/Admin/Views/layouts/admin.php', [
            'pageTitle' => 'المصادقة الثنائية',
            'contentView' => CORE_PATH . '/Admin/Views/security/two-factor.php',
            'enabled' => $enabled,
            'secret' => $secret,
            'provisioningUri' => $uri,
            'recoveryCodes' => is_array($recoveryCodes) ? $recoveryCodes : [],
        ]);
    }

    public function enable(array $params): void
    {
        Auth::requireLogin();
        Csrf::verifyRequestOrFail();
        $secret = (string) Session::get(self::SETUP_SECRET_KEY, '');
        if ($secret === '' || !TwoFactor::verifyTotp($secret, preg_replace('/\D+/', '', Request::trimmed('code')) ?? '')) {
            Session::flash('error', 'رمز تطبيق المصادقة غير صحيح.');
            Response::redirect(Url::admin('two-factor'));
        }

        $userId = (int) Auth::user()['id'];
        $codes = TwoFactor::enable($userId, $secret);
        Session::remove(self::SETUP_SECRET_KEY);
        Session::set(self::RECOVERY_CODES_KEY, $codes);
        ActivityLog::record('two_factor_enable', 'تفعيل المصادقة الثنائية');
        Session::flash('success', 'تم تفعيل المصادقة الثنائية. احفظ رموز الاسترداد الآن.');
        Response::redirect(Url::admin('two-factor'));
    }

    public function disable(array $params): void
    {
        Auth::requireLogin();
        Csrf::verifyRequestOrFail();
        $userId = (int) Auth::user()['id'];
        if (!TwoFactor::verifyForUser($userId, Request::trimmed('code'))) {
            Session::flash('error', 'رمز التحقق غير صحيح، لم يتم تعطيل المصادقة الثنائية.');
            Response::redirect(Url::admin('two-factor'));
        }
        TwoFactor::disable($userId);
        ActivityLog::record('two_factor_disable', 'تعطيل المصادقة الثنائية');
        Session::flash('success', 'تم تعطيل المصادقة الثنائية.');
        Response::redirect(Url::admin('two-factor'));
    }
}
