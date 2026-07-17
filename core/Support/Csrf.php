<?php

namespace Core\Support;

final class Csrf
{
    private const KEY = '_csrf_token';

    public static function token(): string
    {
        if (!Session::has(self::KEY)) {
            Session::set(self::KEY, bin2hex(random_bytes(32)));
        }
        return Session::get(self::KEY);
    }

    public static function field(): string
    {
        return '<input type="hidden" name="csrf_token" value="' . Security::e(self::token()) . '">';
    }

    public static function verify(?string $token): bool
    {
        if (!$token || !Session::has(self::KEY)) {
            return false;
        }
        return hash_equals(Session::get(self::KEY), $token);
    }

    /** يتحقق من الرمز داخل طلب POST الحالي، ويوقف التنفيذ برسالة واضحة عند الفشل */
    public static function verifyRequestOrFail(): void
    {
        $token = $_POST['csrf_token'] ?? '';
        if (!self::verify($token)) {
            http_response_code(419);
            echo 'انتهت صلاحية الجلسة أو الطلب غير موثوق. الرجاء إعادة تحميل الصفحة والمحاولة مجددًا.';
            exit;
        }
    }
}
