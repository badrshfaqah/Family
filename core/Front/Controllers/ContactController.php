<?php

namespace Core\Front\Controllers;

use Core\ActivityLog;
use Core\Database;
use Core\Support\Captcha;
use Core\Support\Csrf;
use Core\Support\RateLimiter;
use Core\Support\Request;
use Core\Support\Response;
use Core\Support\Security;
use Core\Support\Session;
use Core\Support\Url;
use Core\View;

/** نموذج مراسلة الإدارة: رسائل الزوار تصل شاشة الرسائل في لوحة التحكم */
final class ContactController
{
    public function form(array $params): void
    {
        echo View::renderLayout(CORE_PATH . '/Front/Views/layouts/main.php', CORE_PATH . '/Front/Views/contact.php', [
            'captcha' => Captcha::generateAnswer(),
            'success' => Session::flash('contact_success'),
            'error' => Session::flash('contact_error'),
            'pageTitle' => 'راسل الإدارة',
            'metaDescription' => 'تواصل مع إدارة الموقع — رسالتك تصل مباشرة للوحة التحكم.',
            'metaRobots' => 'noindex, follow',
        ]);
    }

    public function submit(array $params): void
    {
        $back = Url::to('contact');

        if (!Csrf::verify(Request::post('csrf_token'))) {
            Session::flash('contact_error', 'انتهت صلاحية الجلسة، أعد المحاولة.');
            Response::redirect($back);
        }

        $throttleKey = 'contact:' . Request::ip();
        if (RateLimiter::tooManyAttempts($throttleKey, 3, 60)) {
            Session::flash('contact_error', 'تم إيقاف الإرسال مؤقتًا لكثرة المحاولات — حاول بعد ساعة.');
            Response::redirect($back);
        }
        RateLimiter::hit($throttleKey);

        // فخ البوتات والحد الزمني: نتظاهر بالنجاح كي لا يتعلم البوت من الرفض
        if (!\Core\Support\SpamGuard::passes('contact')) {
            Session::flash('contact_success', 'وصلت رسالتك للإدارة بنجاح 🎉 — شكرًا لتواصلك.');
            Response::redirect($back);
        }

        $errors = [];
        if (!Captcha::verify(Request::post('captcha_answer'))) {
            $errors[] = 'إجابة رمز التحقق غير صحيحة.';
        }

        $name = Security::cleanText(Request::trimmed('name'));
        $message = Security::cleanText(trim((string) Request::post('message', '')));

        if ($name === '' || $message === '') {
            $errors[] = 'الاسم ونص الرسالة مطلوبان.';
        }
        if (mb_strlen($message) > 3000) {
            $errors[] = 'الرسالة أطول من المسموح (3000 حرف).';
        }
        if ($message !== '' && \Core\Support\SpamGuard::hasLink($message . ' ' . Request::trimmed('subject'))) {
            $errors[] = 'يمنع وضع روابط في الرسالة.';
        }
        if ($message !== '' && \Core\Support\SpamGuard::lacksArabic($message)) {
            $errors[] = 'رجاءً اكتب رسالتك باللغة العربية.';
        }

        if ($errors) {
            Session::flash('contact_error', implode(' ', $errors));
            Response::redirect($back);
        }

        Database::insert('contact_messages', [
            'name' => mb_substr($name, 0, 150),
            'phone' => Security::cleanText(Request::trimmed('phone')) ?: null,
            'subject' => Security::cleanText(Request::trimmed('subject')) ?: null,
            'message' => $message,
            'status' => 'new',
            'created_at' => date('Y-m-d H:i:s'),
        ]);

        ActivityLog::record('contact_message', 'رسالة جديدة من زائر: ' . $name);
        Session::flash('contact_success', 'وصلت رسالتك للإدارة بنجاح 🎉 — شكرًا لتواصلك.');
        Response::redirect($back);
    }
}
