<?php

namespace Modules\Gatherings\Admin\Controllers;

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
use Modules\Gatherings\Support\RecurrenceLabel;

final class GatheringsAdminController
{
    public function index(array $params): void
    {
        Auth::requirePermission('content.gatherings');

        $rows = Database::fetchAll(
            'SELECT g.*, c.name AS city_name FROM ' . Database::table('gatherings') . ' g
             LEFT JOIN ' . Database::table('cities') . ' c ON c.id = g.city_id
             ORDER BY g.id DESC'
        );

        $pending = Database::fetchAll(
            'SELECT g.*, c.name AS city_name FROM ' . Database::table('gatherings') . " g
             LEFT JOIN " . Database::table('cities') . " c ON c.id = g.city_id
             WHERE g.status = 'pending' ORDER BY g.id DESC"
        );

        echo View::render(CORE_PATH . '/Admin/Views/layouts/admin.php', [
            'pageTitle' => 'الجمعات',
            'contentView' => __DIR__ . '/../Views/index.php',
            'rows' => $rows,
            'pending' => $pending,
        ]);
    }

    /** اعتماد جمعة مقترحة من زائر ونشرها */
    public function approve(array $params): void
    {
        Auth::requirePermission('content.gatherings');
        Csrf::verifyRequestOrFail();

        $id = (int) $params['id'];
        $item = Database::fetchOne('SELECT * FROM ' . Database::table('gatherings') . " WHERE id = ? AND status = 'pending'", [$id]);
        if ($item) {
            Database::update('gatherings', ['status' => 'active', 'updated_at' => date('Y-m-d H:i:s')], ['id' => $id]);
            ActivityLog::record('gathering_approve', 'اعتماد جمعة مقترحة: ' . $item['title'] . ' (من: ' . ($item['submitted_name'] ?? '؟') . ')', 'gatherings', $id);
            Session::flash('success', 'تم اعتماد الجمعة ونشرها.');
        }
        Response::redirect(Url::admin('gatherings'));
    }

    /** رفض جمعة مقترحة وحذفها */
    public function reject(array $params): void
    {
        Auth::requirePermission('content.gatherings');
        Csrf::verifyRequestOrFail();

        $id = (int) $params['id'];
        $item = Database::fetchOne('SELECT title FROM ' . Database::table('gatherings') . " WHERE id = ? AND status = 'pending'", [$id]);
        if ($item) {
            Database::delete('gatherings', ['id' => $id]);
            ActivityLog::record('gathering_reject', 'رفض جمعة مقترحة: ' . $item['title'], 'gatherings', $id);
            Session::flash('success', 'تم رفض الاقتراح وحذفه.');
        }
        Response::redirect(Url::admin('gatherings'));
    }

    public function create(array $params): void
    {
        Auth::requirePermission('content.gatherings');
        echo View::render(CORE_PATH . '/Admin/Views/layouts/admin.php', [
            'pageTitle' => 'إضافة جمعة',
            'contentView' => __DIR__ . '/../Views/form.php',
            'gathering' => null,
            'cities' => $this->cityList(),
        ]);
    }

    public function store(array $params): void
    {
        Auth::requirePermission('content.gatherings');
        Csrf::verifyRequestOrFail();

        $title = Security::cleanText(Request::trimmed('title'));
        $startsOn = Request::trimmed('starts_on');

        if ($title === '' || $startsOn === '') {
            Session::flash('error', 'اسم الجمعة وتاريخ بداية التكرار مطلوبان.');
            Response::redirect(Url::admin('gatherings/create'));
        }

        $coverId = $this->handleCoverUpload();

        Database::insert('gatherings', $this->collectData($title, $startsOn, $coverId));

        ActivityLog::record('gatherings_create', 'إضافة جمعة: ' . $title);
        Session::flash('success', 'تمت إضافة الجمعة.');
        Response::redirect(Url::admin('gatherings'));
    }

    public function edit(array $params): void
    {
        Auth::requirePermission('content.gatherings');
        $item = Database::fetchOne('SELECT * FROM ' . Database::table('gatherings') . ' WHERE id = ?', [(int) $params['id']]);
        if (!$item) {
            Response::redirect(Url::admin('gatherings'));
        }

        echo View::render(CORE_PATH . '/Admin/Views/layouts/admin.php', [
            'pageTitle' => 'تعديل جمعة',
            'contentView' => __DIR__ . '/../Views/form.php',
            'gathering' => $item,
            'cities' => $this->cityList(),
        ]);
    }

    public function update(array $params): void
    {
        Auth::requirePermission('content.gatherings');
        Csrf::verifyRequestOrFail();

        $id = (int) $params['id'];
        $title = Security::cleanText(Request::trimmed('title'));
        $startsOn = Request::trimmed('starts_on');

        if ($title === '' || $startsOn === '') {
            Session::flash('error', 'اسم الجمعة وتاريخ بداية التكرار مطلوبان.');
            Response::redirect(Url::admin('gatherings/' . $id . '/edit'));
        }

        $data = $this->collectData($title, $startsOn, null);
        unset($data['created_at'], $data['created_by']);

        $coverId = $this->handleCoverUpload();
        if ($coverId) {
            $data['cover_media_id'] = $coverId;
        }

        Database::update('gatherings', $data, ['id' => $id]);

        ActivityLog::record('gatherings_update', 'تعديل جمعة: ' . $title, 'gatherings', $id);
        Session::flash('success', 'تم تحديث الجمعة.');
        Response::redirect(Url::admin('gatherings'));
    }

    public function delete(array $params): void
    {
        Auth::requirePermission('content.gatherings');
        Csrf::verifyRequestOrFail();

        $id = (int) $params['id'];
        Database::delete('gatherings', ['id' => $id]);
        ActivityLog::record('gatherings_delete', 'حذف جمعة رقم: ' . $id);
        Session::flash('success', 'تم حذف الجمعة.');
        Response::redirect(Url::admin('gatherings'));
    }

    private function collectData(string $title, string $startsOn, ?int $coverId): array
    {
        $type = Request::post('recurrence_type', 'weekly');
        if (!in_array($type, RecurrenceLabel::TYPES, true)) {
            $type = 'weekly';
        }

        $daysInput = Request::post('recurrence_days', []);
        $days = is_array($daysInput) ? array_map('intval', $daysInput) : [];

        $ordinal = null;
        if ($type === 'monthly') {
            $ordinal = (int) Request::post('recurrence_ordinal', 1);
            if ($ordinal < 1 || $ordinal > 5) {
                $ordinal = 1;
            }
        }

        $customText = $type === 'custom' ? Security::cleanText(Request::trimmed('recurrence_custom_text')) : null;
        $startTime = Request::trimmed('start_time') ?: null;
        $endTime = Request::trimmed('end_time') ?: null;

        $label = RecurrenceLabel::build($type, $days, $ordinal, $customText, $startTime, $endTime);

        // رابط الخريطة: من يلصقه بدون https:// يتحول لرابط داخلي مكسور عند العرض
        $mapUrl = Security::cleanText(Request::trimmed('map_url'));
        if ($mapUrl !== '' && !preg_match('~^https?://~i', $mapUrl)) {
            $mapUrl = 'https://' . $mapUrl;
        }

        $data = [
            'title' => $title,
            'description' => Security::cleanText(Request::trimmed('description')),
            'city_id' => Request::int('city_id') ?: null,
            'venue' => Security::cleanText(Request::trimmed('venue')),
            'map_url' => $mapUrl,
            'start_time' => $startTime,
            'end_time' => $endTime,
            'recurrence_type' => $type,
            'recurrence_days' => implode(',', $days),
            'recurrence_ordinal' => $ordinal,
            'recurrence_custom_text' => $customText,
            'recurrence_label' => $label,
            'starts_on' => $startsOn,
            'ends_on' => Request::trimmed('ends_on') ?: null,
            'status' => $this->resolveStatus(),
            'contact_name' => Security::cleanText(Request::trimmed('contact_name')),
            'notes' => Security::cleanText(Request::trimmed('notes')),
            'created_by' => Auth::user()['id'] ?? null,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ];

        if ($coverId) {
            $data['cover_media_id'] = $coverId;
        }

        return $data;
    }

    private function resolveStatus(): string
    {
        $status = Request::post('status', 'active');
        return in_array($status, ['active', 'paused', 'ended'], true) ? $status : 'active';
    }

    private function cityList(): array
    {
        return Database::fetchAll('SELECT * FROM ' . Database::table('cities') . " WHERE status = 'active' ORDER BY sort_order ASC, name ASC");
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
