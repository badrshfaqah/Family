<?php

namespace Modules\Announcements\Admin\Controllers;

use Core\ActivityLog;
use Core\Auth;
use Core\Database;
use Core\Support\Csrf;
use Core\Support\Request;
use Core\Support\Response;
use Core\Support\Security;
use Core\Support\Session;
use Core\Support\Url;
use Core\View;

final class AnnouncementsAdminController
{
    public const TYPES = [
        'تغيير موقع مناسبة',
        'تأجيل مناسبة',
        'إعلان اجتماع',
        'إعلان عزاء',
        'تنبيه مهم',
        'رسالة من الإدارة',
        'إعلان عام',
    ];

    public const PLACEMENTS = ['home_card', 'top_bar', 'popup', 'page'];
    public const PLACEMENT_LABELS = [
        'home_card' => 'بطاقة في الرئيسية',
        'top_bar' => 'شريط علوي',
        'popup' => 'نافذة منبثقة',
        'page' => 'داخل صفحة محددة',
    ];

    public function index(array $params): void
    {
        Auth::requirePermission('content.announcements');

        $rows = Database::fetchAll('SELECT * FROM ' . Database::table('announcements') . ' ORDER BY id DESC');

        echo View::render(CORE_PATH . '/Admin/Views/layouts/admin.php', [
            'pageTitle' => 'الإعلانات',
            'contentView' => __DIR__ . '/../Views/index.php',
            'rows' => $rows,
        ]);
    }

    public function create(array $params): void
    {
        Auth::requirePermission('content.announcements');
        echo View::render(CORE_PATH . '/Admin/Views/layouts/admin.php', [
            'pageTitle' => 'إضافة إعلان',
            'contentView' => __DIR__ . '/../Views/form.php',
            'item' => null,
        ]);
    }

    public function store(array $params): void
    {
        Auth::requirePermission('content.announcements');
        Csrf::verifyRequestOrFail();

        $title = Security::cleanText(Request::trimmed('title'));
        $message = Security::cleanText(Request::trimmed('message'));
        if ($title === '' || $message === '') {
            Session::flash('error', 'العنوان ونص الإعلان مطلوبان.');
            Response::redirect(Url::admin('announcements/create'));
        }

        Database::insert('announcements', [
            'title' => $title,
            'message' => $message,
            'announcement_type' => $this->resolveType(),
            'placement' => $this->resolvePlacement(),
            'target_page_slug' => Security::cleanText(Request::trimmed('target_page_slug')) ?: null,
            'status' => $this->resolveStatus(),
            'starts_at' => $this->toDatetime(Request::trimmed('starts_at')),
            'ends_at' => $this->toDatetime(Request::trimmed('ends_at')),
            'popup_frequency' => $this->resolvePopupFrequency(),
            'popup_interval_days' => Request::int('popup_interval_days') ?: null,
            'show_on_desktop' => Request::bool('show_on_desktop') ? 1 : 0,
            'show_on_mobile' => Request::bool('show_on_mobile') ? 1 : 0,
            'created_by' => Auth::user()['id'] ?? null,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        ActivityLog::record('announcement_create', 'إضافة إعلان: ' . $title);
        Session::flash('success', 'تمت إضافة الإعلان.');
        Response::redirect(Url::admin('announcements'));
    }

    public function edit(array $params): void
    {
        Auth::requirePermission('content.announcements');
        $item = Database::fetchOne('SELECT * FROM ' . Database::table('announcements') . ' WHERE id = ?', [(int) $params['id']]);
        if (!$item) {
            Response::redirect(Url::admin('announcements'));
        }

        echo View::render(CORE_PATH . '/Admin/Views/layouts/admin.php', [
            'pageTitle' => 'تعديل إعلان',
            'contentView' => __DIR__ . '/../Views/form.php',
            'item' => $item,
        ]);
    }

    public function update(array $params): void
    {
        Auth::requirePermission('content.announcements');
        Csrf::verifyRequestOrFail();

        $id = (int) $params['id'];
        $title = Security::cleanText(Request::trimmed('title'));
        $message = Security::cleanText(Request::trimmed('message'));

        Database::update('announcements', [
            'title' => $title,
            'message' => $message,
            'announcement_type' => $this->resolveType(),
            'placement' => $this->resolvePlacement(),
            'target_page_slug' => Security::cleanText(Request::trimmed('target_page_slug')) ?: null,
            'status' => $this->resolveStatus(),
            'starts_at' => $this->toDatetime(Request::trimmed('starts_at')),
            'ends_at' => $this->toDatetime(Request::trimmed('ends_at')),
            'popup_frequency' => $this->resolvePopupFrequency(),
            'popup_interval_days' => Request::int('popup_interval_days') ?: null,
            'show_on_desktop' => Request::bool('show_on_desktop') ? 1 : 0,
            'show_on_mobile' => Request::bool('show_on_mobile') ? 1 : 0,
            'updated_at' => date('Y-m-d H:i:s'),
        ], ['id' => $id]);

        ActivityLog::record('announcement_update', 'تعديل إعلان: ' . $title, 'announcement', $id);
        Session::flash('success', 'تم تحديث الإعلان.');
        Response::redirect(Url::admin('announcements'));
    }

    public function delete(array $params): void
    {
        Auth::requirePermission('content.announcements');
        Csrf::verifyRequestOrFail();

        $id = (int) $params['id'];
        Database::delete('announcements', ['id' => $id]);
        ActivityLog::record('announcement_delete', 'حذف إعلان رقم: ' . $id);
        Session::flash('success', 'تم حذف الإعلان.');
        Response::redirect(Url::admin('announcements'));
    }

    private function resolveType(): ?string
    {
        $type = Request::trimmed('announcement_type');
        if ($type === 'مخصص' || $type === '') {
            $custom = Security::cleanText(Request::trimmed('announcement_type_custom'));
            return $custom !== '' ? $custom : null;
        }
        return Security::cleanText($type);
    }

    private function resolvePlacement(): string
    {
        $placement = Request::post('placement', 'home_card');
        return in_array($placement, self::PLACEMENTS, true) ? $placement : 'home_card';
    }

    private function resolveStatus(): string
    {
        $status = Request::post('status', 'active');
        return in_array($status, ['active', 'inactive'], true) ? $status : 'active';
    }

    private function resolvePopupFrequency(): string
    {
        $freq = Request::post('popup_frequency', 'once');
        return in_array($freq, ['once', 'every_visit', 'interval_days'], true) ? $freq : 'once';
    }

    private function toDatetime(string $value): ?string
    {
        if ($value === '') {
            return null;
        }
        $ts = strtotime($value);
        return $ts ? date('Y-m-d H:i:s', $ts) : null;
    }
}
