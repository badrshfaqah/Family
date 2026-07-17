<?php

namespace Modules\Calendar\Admin\Controllers;

use Core\ActivityLog;
use Core\Auth;
use Core\Database;
use Core\Media;
use Core\Support\Csrf;
use Core\Support\Request;
use Core\Support\Response;
use Core\Support\Security;
use Core\Support\Session;
use Core\Support\Url;
use Core\View;

final class CalendarAdminController
{
    public const TYPES = ['زواج', 'وليمة', 'حفل', 'تخرج', 'اجتماع', 'عزاء', 'مناسبة خاصة'];

    public function index(array $params): void
    {
        Auth::requirePermission('content.calendar');

        $rows = Database::fetchAll(
            'SELECT ce.*, c.name AS city_name, ' . ($this->eventsAvailable() ? 'ev.title AS event_title' : 'NULL AS event_title') . '
             FROM ' . Database::table('calendar_entries') . ' ce
             LEFT JOIN ' . Database::table('cities') . ' c ON c.id = ce.city_id
             ' . ($this->eventsAvailable() ? 'LEFT JOIN ' . Database::table('events') . ' ev ON ev.id = ce.event_id' : '') . '
             ORDER BY ce.entry_datetime DESC'
        );

        echo View::render(CORE_PATH . '/Admin/Views/layouts/admin.php', [
            'pageTitle' => 'رزنامة المناسبات',
            'contentView' => __DIR__ . '/../Views/index.php',
            'rows' => $rows,
        ]);
    }

    public function create(array $params): void
    {
        Auth::requirePermission('content.calendar');
        echo View::render(CORE_PATH . '/Admin/Views/layouts/admin.php', [
            'pageTitle' => 'إضافة موعد',
            'contentView' => __DIR__ . '/../Views/form.php',
            'entryRow' => null,
            'cities' => $this->cityList(),
            'events' => $this->eventOptions(),
        ]);
    }

    public function store(array $params): void
    {
        Auth::requirePermission('content.calendar');
        Csrf::verifyRequestOrFail();

        $title = Security::cleanText(Request::trimmed('title'));
        $entryDatetime = $this->toDatetime(Request::trimmed('entry_date'), Request::trimmed('entry_time'));
        if ($title === '' || !$entryDatetime) {
            Session::flash('error', 'العنوان والتاريخ مطلوبان.');
            Response::redirect(Url::admin('calendar/create'));
        }

        Database::insert('calendar_entries', [
            'title' => $title,
            'entry_type' => $this->resolveEntryType(),
            'entry_datetime' => $entryDatetime,
            'city_id' => Request::int('city_id') ?: null,
            'venue_name' => Security::cleanText(Request::trimmed('venue_name')),
            'maps_url' => Security::cleanText(Request::trimmed('maps_url')),
            'notes' => Security::cleanText(Request::trimmed('notes')),
            'cover_media_id' => $this->handleCoverUpload(),
            'event_id' => $this->resolveEventId(),
            'status' => $this->resolveStatus(),
            'created_by' => Auth::user()['id'] ?? null,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        ActivityLog::record('calendar_create', 'إضافة موعد: ' . $title);
        Session::flash('success', 'تمت إضافة الموعد.');
        Response::redirect(Url::admin('calendar'));
    }

    public function edit(array $params): void
    {
        Auth::requirePermission('content.calendar');
        $item = Database::fetchOne('SELECT * FROM ' . Database::table('calendar_entries') . ' WHERE id = ?', [(int) $params['id']]);
        if (!$item) {
            Response::redirect(Url::admin('calendar'));
        }

        echo View::render(CORE_PATH . '/Admin/Views/layouts/admin.php', [
            'pageTitle' => 'تعديل موعد',
            'contentView' => __DIR__ . '/../Views/form.php',
            'entryRow' => $item,
            'cities' => $this->cityList(),
            'events' => $this->eventOptions(),
        ]);
    }

    public function update(array $params): void
    {
        Auth::requirePermission('content.calendar');
        Csrf::verifyRequestOrFail();

        $id = (int) $params['id'];
        $existing = Database::fetchOne('SELECT * FROM ' . Database::table('calendar_entries') . ' WHERE id = ?', [$id]);
        if (!$existing) {
            Response::redirect(Url::admin('calendar'));
        }

        $title = Security::cleanText(Request::trimmed('title'));
        $entryDatetime = $this->toDatetime(Request::trimmed('entry_date'), Request::trimmed('entry_time')) ?: $existing['entry_datetime'];

        $data = [
            'title' => $title,
            'entry_type' => $this->resolveEntryType(),
            'entry_datetime' => $entryDatetime,
            'city_id' => Request::int('city_id') ?: null,
            'venue_name' => Security::cleanText(Request::trimmed('venue_name')),
            'maps_url' => Security::cleanText(Request::trimmed('maps_url')),
            'notes' => Security::cleanText(Request::trimmed('notes')),
            'event_id' => $this->resolveEventId(),
            'status' => $this->resolveStatus(),
            'updated_at' => date('Y-m-d H:i:s'),
        ];

        $coverId = $this->handleCoverUpload();
        if ($coverId) {
            $data['cover_media_id'] = $coverId;
        }

        Database::update('calendar_entries', $data, ['id' => $id]);

        ActivityLog::record('calendar_update', 'تعديل موعد: ' . $title, 'calendar_entry', $id);
        Session::flash('success', 'تم تحديث الموعد.');
        Response::redirect(Url::admin('calendar'));
    }

    public function delete(array $params): void
    {
        Auth::requirePermission('content.calendar');
        Csrf::verifyRequestOrFail();

        $id = (int) $params['id'];
        Database::delete('calendar_entries', ['id' => $id]);
        ActivityLog::record('calendar_delete', 'حذف موعد رقم: ' . $id);
        Session::flash('success', 'تم حذف الموعد.');
        Response::redirect(Url::admin('calendar'));
    }

    private function cityList(): array
    {
        return Database::fetchAll('SELECT * FROM ' . Database::table('cities') . ' ORDER BY sort_order ASC, id ASC');
    }

    private function eventsAvailable(): bool
    {
        return Database::tableExists('events');
    }

    private function eventOptions(): array
    {
        if (!$this->eventsAvailable()) {
            return [];
        }
        return Database::fetchAll('SELECT id, title FROM ' . Database::table('events') . ' ORDER BY id DESC LIMIT 500');
    }

    private function resolveEventId(): ?int
    {
        if (!$this->eventsAvailable()) {
            return null;
        }
        return Request::int('event_id') ?: null;
    }

    private function resolveEntryType(): string
    {
        $type = Request::trimmed('entry_type');
        if ($type === 'مخصص' || $type === 'custom' || $type === '') {
            $custom = Security::cleanText(Request::trimmed('entry_type_custom'));
            return $custom !== '' ? $custom : ($type === '' ? 'مناسبة خاصة' : $type);
        }
        return Security::cleanText($type);
    }

    private function resolveStatus(): string
    {
        $status = Request::post('status', 'draft');
        return in_array($status, ['draft', 'published', 'cancelled'], true) ? $status : 'draft';
    }

    private function toDatetime(string $date, string $time): ?string
    {
        if ($date === '') {
            return null;
        }
        $time = $time !== '' ? $time : '00:00';
        $ts = strtotime($date . ' ' . $time);
        return $ts ? date('Y-m-d H:i:s', $ts) : null;
    }

    private function handleCoverUpload(): ?int
    {
        $file = Request::file('cover');
        if (!$file) {
            return null;
        }
        try {
            $data = Media::handleUpload($file, Auth::user()['id'] ?? null);
            return (int) Database::insert('media', array_merge($data, ['created_at' => date('Y-m-d H:i:s')]));
        } catch (\Throwable $e) {
            Session::flash('error', $e->getMessage());
            return null;
        }
    }
}
