<?php

namespace Core\Front;

use Core\Redirects;
use Core\Support\Request;
use Core\Support\Url;

/**
 * نقطة موحدة لعرض 404 في الواجهة العامة، تتحقق أولًا من وجود تحويل مسجّل
 * لهذا الرابط (بسبب تغيير Slug) قبل عرض صفحة "غير موجود" فعليًا.
 * تُستخدم من كل متحكم عام بدل عرض 404 مباشرة.
 */
final class NotFound
{
    public static function render(): void
    {
        $redirectTo = Redirects::resolve(Request::path());
        if ($redirectTo) {
            header('Location: ' . Url::to(ltrim($redirectTo, '/')), true, 301);
            exit;
        }

        http_response_code(404);
        require CORE_PATH . '/Front/Views/404.php';
    }
}
