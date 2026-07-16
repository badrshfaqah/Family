<?php

namespace Modules\Newsletter\Front\Controllers;

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

final class NewsletterFrontController
{
    private const CONSENT_TEXT = 'أوافق على استقبال رسائل ونشرات بريدية من إدارة الموقع.';

    public function subscribeForm(array $params): void
    {
        $captcha = Captcha::generateAnswer();

        $this->render('subscribe', [
            'captcha' => $captcha,
            'consentText' => self::CONSENT_TEXT,
            'old' => ['name' => '', 'email' => ''],
            'success' => Session::flash('newsletter_success'),
        ]);
    }

    public function subscribe(array $params): void
    {
        $old = [
            'name' => Request::trimmed('name'),
            'email' => Request::trimmed('email'),
        ];

        if (!Csrf::verify(Request::post('csrf_token'))) {
            $this->renderError('انتهت صلاحية الجلسة، الرجاء إعادة تحميل الصفحة والمحاولة مجددًا.', $old);
            return;
        }

        $throttleKey = 'newsletter_subscribe:' . Request::ip();
        if (RateLimiter::tooManyAttempts($throttleKey, 6, 10)) {
            $this->renderError('محاولات كثيرة، الرجاء المحاولة لاحقًا بعد قليل.', $old);
            return;
        }

        if (!Captcha::verify(Request::trimmed('captcha_answer'))) {
            RateLimiter::hit($throttleKey);
            $this->renderError('إجابة رمز التحقق غير صحيحة.', $old);
            return;
        }

        $name = Security::cleanText($old['name']);
        $email = strtolower(trim($old['email']));

        if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            RateLimiter::hit($throttleKey);
            $this->renderError('الرجاء إدخال بريد إلكتروني صحيح.', $old);
            return;
        }

        if (!Request::bool('consent')) {
            RateLimiter::hit($throttleKey);
            $this->renderError('الموافقة على استقبال الرسائل مطلوبة للاشتراك.', $old);
            return;
        }

        RateLimiter::hit($throttleKey);

        $existing = Database::fetchOne(
            'SELECT id, status FROM ' . Database::table('newsletter_subscribers') . ' WHERE LOWER(email) = ?',
            [$email]
        );

        if ($existing) {
            if ($existing['status'] === 'unsubscribed') {
                // إعادة تفعيل اشتراك سابق بدل رفضه أو إنشاء صف مكرر.
                Database::update('newsletter_subscribers', [
                    'name' => $name !== '' ? $name : null,
                    'status' => 'subscribed',
                    'source' => 'website_form',
                    'subscribed_at' => date('Y-m-d H:i:s'),
                    'unsubscribed_at' => null,
                    'updated_at' => date('Y-m-d H:i:s'),
                ], ['id' => $existing['id']]);
            }
            // بريد مشترك بالفعل: رسالة عامة ودّية دون كشف تفاصيل إضافية.
            Session::flash('newsletter_success', 'أنت مشترك بالفعل. شكرًا لاهتمامك!');
            Response::redirect(Url::to('newsletter/subscribe'));
            return;
        }

        Database::insert('newsletter_subscribers', [
            'name' => $name !== '' ? $name : null,
            'email' => $email,
            'status' => 'subscribed',
            'category' => null,
            'source' => 'website_form',
            'subscribed_at' => date('Y-m-d H:i:s'),
            'unsubscribed_at' => null,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        Session::flash('newsletter_success', 'تم اشتراكك في القائمة البريدية بنجاح، شكرًا لك!');
        Response::redirect(Url::to('newsletter/subscribe'));
    }

    public function unsubscribeForm(array $params): void
    {
        $this->render('unsubscribe', [
            'old' => ['email' => Request::trimmed('email')],
            'success' => Session::flash('newsletter_success'),
        ]);
    }

    public function unsubscribe(array $params): void
    {
        $email = strtolower(Request::trimmed('email'));

        if (!Csrf::verify(Request::post('csrf_token'))) {
            $this->render('unsubscribe', ['old' => ['email' => $email], 'error' => 'انتهت صلاحية الجلسة، الرجاء إعادة تحميل الصفحة والمحاولة مجددًا.']);
            return;
        }

        $throttleKey = 'newsletter_unsubscribe:' . Request::ip();
        if (RateLimiter::tooManyAttempts($throttleKey, 10, 10)) {
            $this->render('unsubscribe', ['old' => ['email' => $email], 'error' => 'محاولات كثيرة، الرجاء المحاولة لاحقًا بعد قليل.']);
            return;
        }
        RateLimiter::hit($throttleKey);

        if ($email !== '' && filter_var($email, FILTER_VALIDATE_EMAIL)) {
            // لا نكشف عبر الاستجابة أو التوقيت ما إذا كان البريد موجودًا فعلًا في القائمة.
            Database::query(
                'UPDATE ' . Database::table('newsletter_subscribers') . "
                 SET status = 'unsubscribed', unsubscribed_at = NOW(), updated_at = NOW()
                 WHERE LOWER(email) = ? AND status = 'subscribed'",
                [$email]
            );
        }

        Session::flash('newsletter_success', 'تم إلغاء الاشتراك.');
        Response::redirect(Url::to('newsletter/unsubscribe'));
    }

    private function renderError(string $message, array $old): void
    {
        $captcha = Captcha::generateAnswer();
        $this->render('subscribe', [
            'captcha' => $captcha,
            'consentText' => self::CONSENT_TEXT,
            'old' => $old,
            'error' => $message,
        ]);
    }

    private function render(string $view, array $data): void
    {
        $layout = CORE_PATH . '/Front/Views/layouts/main.php';
        $viewFile = __DIR__ . '/../Views/' . $view . '.php';

        $titles = [
            'subscribe' => 'الاشتراك في القائمة البريدية',
            'unsubscribe' => 'إلغاء الاشتراك في القائمة البريدية',
        ];

        echo View::renderLayout($layout, $viewFile, array_merge($data, [
            'pageTitle' => $titles[$view] ?? 'القائمة البريدية',
            'metaDescription' => '',
        ]));
    }
}
