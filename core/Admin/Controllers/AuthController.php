<?php

namespace Core\Admin\Controllers;

use Core\ActivityLog;
use Core\Auth;
use Core\Database;
use Core\Mail;
use Core\Settings;
use Core\Support\Csrf;
use Core\Support\RateLimiter;
use Core\Support\Request;
use Core\Support\Response;
use Core\Support\Security;
use Core\Support\Session;
use Core\Support\Str;
use Core\Support\Url;
use Core\View;

final class AuthController
{
    public function showLogin(array $params): void
    {
        if (Auth::check()) {
            Response::redirect(Url::admin(''));
        }

        echo View::render(CORE_PATH . '/Admin/Views/auth/login.php', [
            'error' => null,
        ]);
    }

    public function login(array $params): void
    {
        Csrf::verifyRequestOrFail();

        $identifier = Request::trimmed('identifier');
        $password = (string) Request::post('password', '');

        $result = Auth::attempt($identifier, $password);

        if (!$result['ok']) {
            echo View::render(CORE_PATH . '/Admin/Views/auth/login.php', [
                'error' => $result['message'],
                'identifier' => $identifier,
            ]);
            return;
        }

        if (!empty($result['requires_2fa'])) {
            Response::redirect(Url::admin('login/two-factor'));
        }

        Response::redirect(Url::admin(''));
    }

    public function logout(array $params): void
    {
        Auth::requireLogin();
        Csrf::verifyRequestOrFail();
        Auth::logout();
        Response::redirect(Url::admin('login'));
    }

    public function showForgotPassword(array $params): void
    {
        if (Auth::check()) {
            Response::redirect(Url::admin(''));
        }

        echo View::render(CORE_PATH . '/Admin/Views/auth/forgot-password.php', ['sent' => false]);
    }

    public function sendResetLink(array $params): void
    {
        Csrf::verifyRequestOrFail();

        $email = filter_var(Request::trimmed('email'), FILTER_VALIDATE_EMAIL) ?: '';

        // رسالة عامة واحدة دائمًا، سواء كان البريد مسجلًا أم لا، لمنع كشف حسابات موجودة
        $genericMessage = true;
        $ipKey = 'forgot-password-ip:' . Request::ip();

        if ($email !== ''
            && !RateLimiter::tooManyAttempts($ipKey, 10, 15)
            && !RateLimiter::tooManyAttempts('forgot-password:' . $email, 5, 15)) {
            RateLimiter::hit($ipKey);
            RateLimiter::hit('forgot-password:' . $email);

            $user = Database::fetchOne(
                'SELECT * FROM ' . Database::table('admin_users') . ' WHERE email = ? AND status = "active"',
                [$email]
            );

            if ($user) {
                $token = Str::random(48);
                Database::query(
                    'UPDATE ' . Database::table('password_resets') . ' SET used_at = NOW() WHERE user_id = ? AND used_at IS NULL',
                    [$user['id']]
                );
                Database::insert('password_resets', [
                    'user_id' => $user['id'],
                    'token' => hash('sha256', $token),
                    'expires_at' => date('Y-m-d H:i:s', time() + 3600),
                    'created_at' => date('Y-m-d H:i:s'),
                ]);

                $resetUrl = Url::admin('reset-password/' . $token);
                $siteName = Settings::get('identity_short_name', '') ?: Settings::get('identity_official_name', '');

                Mail::send(
                    $email,
                    'إعادة تعيين كلمة المرور - ' . $siteName,
                    '<div dir="rtl" style="font-family:Tahoma,Arial,sans-serif">'
                    . '<p>مرحبًا ' . Security::e($user['name']) . '،</p>'
                    . '<p>وصلنا طلب لإعادة تعيين كلمة مرور حسابك في لوحة تحكم ' . Security::e($siteName) . '.</p>'
                    . '<p><a href="' . Security::e($resetUrl) . '">اضغط هنا لتعيين كلمة مرور جديدة</a></p>'
                    . '<p>هذا الرابط صالح لمدة ساعة واحدة فقط. إذا لم تطلب ذلك، تجاهل هذه الرسالة.</p>'
                    . '</div>'
                );

                ActivityLog::record('password_reset_request', 'طلب إعادة تعيين كلمة مرور للمستخدم: ' . $user['username']);
            }
        }

        echo View::render(CORE_PATH . '/Admin/Views/auth/forgot-password.php', ['sent' => $genericMessage]);
    }

    public function showResetPassword(array $params): void
    {
        $token = $params['token'] ?? '';
        $reset = $this->validToken($token);

        echo View::render(CORE_PATH . '/Admin/Views/auth/reset-password.php', [
            'token' => $token,
            'valid' => $reset !== null,
            'error' => null,
        ]);
    }

    public function updatePassword(array $params): void
    {
        Csrf::verifyRequestOrFail();

        $token = $params['token'] ?? '';
        $reset = $this->validToken($token);

        if (!$reset) {
            echo View::render(CORE_PATH . '/Admin/Views/auth/reset-password.php', [
                'token' => $token,
                'valid' => false,
                'error' => 'رابط إعادة التعيين غير صالح أو منتهي الصلاحية.',
            ]);
            return;
        }

        $password = (string) Request::post('password', '');
        $confirm = (string) Request::post('password_confirm', '');

        if (strlen($password) < 8 || $password !== $confirm) {
            echo View::render(CORE_PATH . '/Admin/Views/auth/reset-password.php', [
                'token' => $token,
                'valid' => true,
                'error' => 'كلمة المرور يجب ألا تقل عن 8 أحرف، ويجب أن تتطابق مع التأكيد.',
            ]);
            return;
        }

        Database::update('admin_users', [
            'password_hash' => Security::hashPassword($password),
        ], ['id' => $reset['user_id']]);

        Database::query(
            'UPDATE ' . Database::table('password_resets') . ' SET used_at = NOW() WHERE user_id = ? AND used_at IS NULL',
            [$reset['user_id']]
        );

        $user = Database::fetchOne('SELECT username FROM ' . Database::table('admin_users') . ' WHERE id = ?', [$reset['user_id']]);
        ActivityLog::record('password_reset_complete', 'إعادة تعيين كلمة مرور ناجحة للمستخدم: ' . ($user['username'] ?? $reset['user_id']));

        Session::flash('success', 'تم تعيين كلمة المرور الجديدة بنجاح. يمكنك تسجيل الدخول الآن.');
        Response::redirect(Url::admin('login'));
    }

    private function validToken(string $token): ?array
    {
        if ($token === '') {
            return null;
        }
        $tokenHash = hash('sha256', $token);
        return Database::fetchOne(
            'SELECT * FROM ' . Database::table('password_resets') . '
             WHERE token IN (?, ?) AND used_at IS NULL AND expires_at >= NOW()
             ORDER BY id DESC LIMIT 1',
            [$tokenHash, $token]
        );
    }
}
