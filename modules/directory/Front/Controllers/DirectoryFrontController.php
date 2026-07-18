<?php

namespace Modules\Directory\Front\Controllers;

use Core\Database;
use Core\Settings;
use Core\Support\Captcha;
use Core\Support\Csrf;
use Core\Support\RateLimiter;
use Core\Support\Request;
use Core\Support\Response;
use Core\Support\Security;
use Core\Support\Session;
use Core\Support\Url;
use Core\Terms;
use Core\View;
use Modules\Directory\Support\Phone;

/**
 * النموذج العام الوحيد الذي يجمع بيانات جوال العائلة، ونموذج طلب إلغاء الاشتراك
 * الذاتي. لا يوجد أي عرض عام لأي بيانات — فقط استقبال بيانات وإرسال رسالة عامة.
 */
final class DirectoryFrontController
{
    public function registerForm(array $params): void
    {
        $this->renderRegisterForm();
    }

    public function registerSubmit(array $params): void
    {
        if (!Csrf::verify(Request::post('csrf_token'))) {
            Session::flash('directory_error', 'انتهت صلاحية الجلسة، الرجاء إعادة المحاولة.');
            Response::redirect(Url::to('directory/register'));
        }

        $ip = Request::ip();
        $throttleKey = 'directory_register:' . $ip;

        if (RateLimiter::tooManyAttempts($throttleKey, 8, 15)) {
            Session::flash('directory_error', 'تم إيقاف المحاولة مؤقتًا بسبب تكرار الطلبات. حاول لاحقًا بعد 15 دقيقة.');
            Response::redirect(Url::to('directory/register'));
        }
        RateLimiter::hit($throttleKey);

        $errors = [];

        if (!Captcha::verify(Request::post('captcha_answer'))) {
            $errors[] = 'إجابة رمز التحقق غير صحيحة.';
        }

        $name = Security::cleanText(Request::trimmed('name'));
        if ($name === '') {
            $errors[] = 'الاسم مطلوب.';
        }

        $phoneRaw = Request::trimmed('phone');
        if ($phoneRaw === '') {
            $errors[] = 'رقم الجوال مطلوب.';
        }
        // حقل التأكيد اختياري (غير معروض في النموذج المختصر)، ويُتحقق منه فقط إن أُرسل
        $phoneConfirmRaw = Request::trimmed('phone_confirm');
        if ($phoneConfirmRaw !== '' && $phoneRaw !== $phoneConfirmRaw) {
            $errors[] = 'رقم الجوال وتأكيده غير متطابقين.';
        }

        $phone = Phone::normalize($phoneRaw);
        if ($phoneRaw !== '' && !Phone::isValid($phone)) {
            $errors[] = 'رقم الجوال غير صالح.';
        }

        $email = Security::cleanText(Request::trimmed('email'));
        if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'صيغة البريد الإلكتروني غير صحيحة.';
        }

        if (!Request::bool('consent')) {
            $errors[] = 'يجب الموافقة الصريحة قبل إتمام التسجيل.';
        }

        if ($errors) {
            Session::flash('directory_error', implode(' ', $errors));
            Response::redirect(Url::to('directory/register'));
        }

        $preventDuplicates = Settings::get('directory_prevent_duplicates', '1') === '1';
        if ($preventDuplicates) {
            $exists = (int) Database::fetchValue(
                'SELECT COUNT(*) FROM ' . Database::table('directory_contacts') . " WHERE phone = ? AND status = 'active'",
                [$phone]
            );
            if ($exists) {
                // رسالة عامة متعمدة: لا نكشف للزائر عن كون الرقم مسجّلًا مسبقًا أم لا.
                Session::flash('directory_error', 'تعذر إتمام التسجيل، يرجى التواصل مع إدارة الموقع.');
                Response::redirect(Url::to('directory/register'));
            }
        }

        // المدن: تحديد متعدد — تُحفظ الأولى في city_id للتوافق، وكامل القائمة في city_ids
        $cityId = null;
        $cityIdsCsv = null;
        if (Settings::get('directory_city_enabled', '1') === '1') {
            $cityIds = array_values(array_filter(array_map('intval', (array) ($_POST['city_ids'] ?? []))));
            if (!$cityIds && Request::int('city_id')) {
                $cityIds = [Request::int('city_id')];
            }
            $cityId = $cityIds[0] ?? null;
            $cityIdsCsv = $cityIds ? implode(',', $cityIds) : null;
        }

        $branchId = null;
        if (Settings::get('branches_enabled', '0') === '1') {
            $branchId = Request::int('branch_id') ?: null;
        }

        Database::insert('directory_contacts', [
            'name' => $name,
            'phone' => $phone,
            'city_id' => $cityId,
            'city_ids' => $cityIdsCsv,
            'branch_id' => $branchId,
            'email' => $email !== '' ? $email : null,
            'status' => 'active',
            'source' => 'website_form',
            'consent_at' => date('Y-m-d H:i:s'),
            'registered_at' => date('Y-m-d H:i:s'),
            'created_by' => null,
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        Session::flash('directory_success', 'تم استلام طلبك بنجاح.');
        Response::redirect(Url::to('directory/register'));
    }

    public function manageSubscriptionForm(array $params): void
    {
        $this->renderManageForm();
    }

    public function manageSubscriptionSubmit(array $params): void
    {
        if (!Csrf::verify(Request::post('csrf_token'))) {
            Session::flash('directory_manage_error', 'انتهت صلاحية الجلسة، الرجاء إعادة المحاولة.');
            Response::redirect(Url::to('directory/manage-subscription'));
        }

        $ip = Request::ip();
        $throttleKey = 'directory_manage_subscription:' . $ip;

        if (RateLimiter::tooManyAttempts($throttleKey, 8, 15)) {
            Session::flash('directory_manage_error', 'تم إيقاف المحاولة مؤقتًا بسبب تكرار الطلبات. حاول لاحقًا بعد 15 دقيقة.');
            Response::redirect(Url::to('directory/manage-subscription'));
        }
        RateLimiter::hit($throttleKey);

        if (!Captcha::verify(Request::post('captcha_answer'))) {
            Session::flash('directory_manage_error', 'إجابة رمز التحقق غير صحيحة.');
            Response::redirect(Url::to('directory/manage-subscription'));
        }

        $phone = Phone::normalize(Request::trimmed('phone'));
        if (!Phone::isValid($phone)) {
            Session::flash('directory_manage_error', 'الرجاء إدخال رقم جوال صالح.');
            Response::redirect(Url::to('directory/manage-subscription'));
        }

        // نُنشئ طلبًا يراجعه مدير من لوحة التحكم فقط؛ لا يوجد أي حذف أو استبعاد تلقائي
        // بدون تسجيل دخول، ولا نكشف للزائر عمّا إذا كان الرقم مسجّلًا أصلًا.
        Database::insert('directory_removal_requests', [
            'phone' => $phone,
            'requested_at' => date('Y-m-d H:i:s'),
            'status' => 'pending',
        ]);

        Session::flash('directory_manage_success', 'تم استلام طلبك، وستتم مراجعته من إدارة الموقع.');
        Response::redirect(Url::to('directory/manage-subscription'));
    }

    private function renderRegisterForm(): void
    {
        $captcha = Captcha::generateAnswer();
        $consentText = Settings::get('directory_consent_text', '');
        if (trim((string) $consentText) === '') {
            $consentText = Terms::phrase('consent_directory');
        }

        $cityEnabled = Settings::get('directory_city_enabled', '1') === '1';
        $branchesEnabled = Settings::get('branches_enabled', '0') === '1';

        $layout = CORE_PATH . '/Front/Views/layouts/main.php';
        $view = __DIR__ . '/../Views/register.php';

        echo View::renderLayout($layout, $view, [
            'captcha' => $captcha,
            'consentText' => $consentText,
            'cityEnabled' => $cityEnabled,
            'branchesEnabled' => $branchesEnabled,
            'cities' => $cityEnabled ? $this->cityList() : [],
            'branches' => $branchesEnabled ? $this->branchList() : [],
            'success' => Session::flash('directory_success'),
            'error' => Session::flash('directory_error'),
            'pageTitle' => Terms::phrase('family_directory_register'),
            'metaDescription' => Settings::get('seo_default_description', ''),
        ]);
    }

    private function renderManageForm(): void
    {
        $captcha = Captcha::generateAnswer();

        $layout = CORE_PATH . '/Front/Views/layouts/main.php';
        $view = __DIR__ . '/../Views/manage_subscription.php';

        echo View::renderLayout($layout, $view, [
            'captcha' => $captcha,
            'success' => Session::flash('directory_manage_success'),
            'error' => Session::flash('directory_manage_error'),
            'pageTitle' => 'إدارة الاشتراك في ' . Terms::phrase('family_directory'),
            'metaDescription' => Settings::get('seo_default_description', ''),
        ]);
    }

    private function cityList(): array
    {
        if (!Database::tableExists('cities')) {
            return [];
        }
        return Database::fetchAll('SELECT id, name FROM ' . Database::table('cities') . " WHERE status = 'active' ORDER BY sort_order ASC, name ASC");
    }

    private function branchList(): array
    {
        if (!Database::tableExists('branches')) {
            return [];
        }
        return Database::fetchAll('SELECT id, name FROM ' . Database::table('branches') . " WHERE status = 'active' ORDER BY sort_order ASC, name ASC");
    }
}
