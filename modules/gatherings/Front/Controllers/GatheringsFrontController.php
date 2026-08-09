<?php

namespace Modules\Gatherings\Front\Controllers;

use Core\Database;
use Core\Settings;
use Core\View;

final class GatheringsFrontController
{
    public function index(array $params): void
    {
        // كل الجمعات النشطة دفعة واحدة بالترتيب اليدوي الذي يحدده المدير
        $rows = Database::fetchAll(
            'SELECT g.*, c.name AS city_name FROM ' . Database::table('gatherings') . ' g
             LEFT JOIN ' . Database::table('cities') . " c ON c.id = g.city_id
             WHERE g.status = 'active'
             ORDER BY g.sort_order ASC, g.id DESC
             LIMIT 200"
        );

        $layout = CORE_PATH . '/Front/Views/layouts/main.php';
        $view = __DIR__ . '/../Views/index.php';

        echo View::renderLayout($layout, $view, [
            'rows' => $rows,
            'pageTitle' => 'الجمعات',
            'metaDescription' => Settings::get('seo_default_description', ''),
        ]);
    }

    /** صفحة تفاصيل جمعة واحدة */
    public function show(array $params): void
    {
        $id = (int) ($params['id'] ?? 0);

        $item = Database::fetchOne(
            'SELECT g.*, c.name AS city_name, m.stored_path AS cover_path
             FROM ' . Database::table('gatherings') . ' g
             LEFT JOIN ' . Database::table('cities') . ' c ON c.id = g.city_id
             LEFT JOIN ' . Database::table('media') . " m ON m.id = g.cover_media_id
             WHERE g.id = ? AND g.status != 'pending'",
            [$id]
        );

        if (!$item) {
            \Core\Front\NotFound::render();
            return;
        }

        $layout = CORE_PATH . '/Front/Views/layouts/main.php';
        $view = __DIR__ . '/../Views/show.php';

        echo View::renderLayout($layout, $view, [
            'item' => $item,
            'pageTitle' => $item['title'],
            'metaDescription' => (string) ($item['description'] ?: Settings::get('seo_default_description', '')),
        ]);
    }

    /** نموذج اقتراح جمعة من الزوار — تُنشر بعد موافقة الإدارة */
    public function suggestForm(array $params): void
    {
        $cities = Database::tableExists('cities')
            ? Database::fetchAll('SELECT id, name FROM ' . Database::table('cities') . " WHERE status = 'active' ORDER BY sort_order ASC, name ASC")
            : [];

        echo View::renderLayout(CORE_PATH . '/Front/Views/layouts/main.php', __DIR__ . '/../Views/suggest.php', [
            'cities' => $cities,
            'captcha' => \Core\Support\Captcha::generateAnswer(),
            'success' => \Core\Support\Session::flash('gathering_suggest_success'),
            'error' => \Core\Support\Session::flash('gathering_suggest_error'),
            'pageTitle' => 'أضف جمعة',
            'metaDescription' => 'اقترح جمعة دورية لتظهر في صفحة الجمعات بعد اعتماد الإدارة.',
            'metaRobots' => 'noindex, follow',
        ]);
    }

    public function suggestSubmit(array $params): void
    {
        $back = \Core\Support\Url::to('gatherings/suggest');

        if (!\Core\Support\Csrf::verify(\Core\Support\Request::post('csrf_token'))) {
            \Core\Support\Session::flash('gathering_suggest_error', 'انتهت صلاحية الجلسة، أعد المحاولة.');
            \Core\Support\Response::redirect($back);
        }

        $throttleKey = 'gathering_suggest:' . \Core\Support\Request::ip();
        if (\Core\Support\RateLimiter::tooManyAttempts($throttleKey, 5, 30)) {
            \Core\Support\Session::flash('gathering_suggest_error', 'تم إيقاف الإرسال مؤقتًا لكثرة المحاولات — حاول بعد نصف ساعة.');
            \Core\Support\Response::redirect($back);
        }
        \Core\Support\RateLimiter::hit($throttleKey);

        $errors = [];
        if (!\Core\Support\Captcha::verify(\Core\Support\Request::post('captcha_answer'))) {
            $errors[] = 'إجابة رمز التحقق غير صحيحة.';
        }

        $title = \Core\Support\Security::cleanText(\Core\Support\Request::trimmed('title'));
        $label = \Core\Support\Security::cleanText(\Core\Support\Request::trimmed('recurrence_label'));
        $name = \Core\Support\Security::cleanText(\Core\Support\Request::trimmed('submitted_name'));
        $phone = \Core\Support\Security::cleanText(\Core\Support\Request::trimmed('submitted_phone'));

        if ($title === '' || $label === '') {
            $errors[] = 'اسم الجمعة وموعدها الدوري مطلوبان.';
        }
        if ($name === '' || $phone === '') {
            $errors[] = 'اسمك ورقم جوالك مطلوبان لتتواصل معك الإدارة عند الحاجة.';
        }

        if ($errors) {
            \Core\Support\Session::flash('gathering_suggest_error', implode(' ', $errors));
            \Core\Support\Response::redirect($back);
        }

        $timePeriod = \Core\Support\Request::post('time_period', '');
        if (!isset(\Modules\Gatherings\Support\RecurrenceLabel::PERIODS[$timePeriod])) {
            $timePeriod = null;
        }
        $periodLabel = \Modules\Gatherings\Support\RecurrenceLabel::periodLabel($timePeriod);

        Database::insert('gatherings', [
            'title' => mb_substr($title, 0, 150),
            'description' => \Core\Support\Security::cleanText(\Core\Support\Request::trimmed('description')) ?: null,
            'city_id' => \Core\Support\Request::int('city_id') ?: null,
            'venue' => \Core\Support\Security::cleanText(\Core\Support\Request::trimmed('venue')) ?: null,
            'time_period' => $timePeriod,
            'recurrence_type' => 'custom',
            'recurrence_label' => mb_substr($label . ($periodLabel !== '' ? '، ' . $periodLabel : ''), 0, 200),
            'starts_on' => date('Y-m-d'),
            'status' => 'pending',
            'submitted_name' => mb_substr($name, 0, 150),
            'submitted_phone' => mb_substr($phone, 0, 30),
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        \Core\ActivityLog::record('gathering_suggest', 'اقتراح جمعة من زائر: ' . $title);
        \Core\Support\Session::flash('gathering_suggest_success', 'استلمنا اقتراح الجمعة بنجاح ☕ — ستظهر في الصفحة فور اعتمادها من الإدارة.');
        \Core\Support\Response::redirect($back);
    }
}
