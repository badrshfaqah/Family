<?php

namespace Modules\Events\Admin\Controllers;

use Core\ActivityLog;
use Core\Auth;
use Core\Database;
use Core\Media;
use Core\Support\Csrf;
use Core\Support\Request;
use Core\Support\Response;
use Core\Support\Security;
use Core\Support\Session;
use Core\Support\Str;
use Core\Support\Url;
use Core\View;

final class EventsAdminController
{
    public const TYPES = ['زواج', 'تخرج', 'حفل', 'وليمة', 'اجتماع', 'عزاء', 'تكريم', 'مناسبة عامة'];

    public function index(array $params): void
    {
        Auth::requirePermission('content.events');

        $rows = Database::fetchAll(
            'SELECT e.*, c.name AS city_name FROM ' . Database::table('events') . ' e
             LEFT JOIN ' . Database::table('cities') . ' c ON c.id = e.city_id
             ORDER BY e.id DESC'
        );

        echo View::render(CORE_PATH . '/Admin/Views/layouts/admin.php', [
            'pageTitle' => 'المناسبات',
            'contentView' => __DIR__ . '/../Views/index.php',
            'rows' => $rows,
        ]);
    }

    public function create(array $params): void
    {
        Auth::requirePermission('content.events');
        echo View::render(CORE_PATH . '/Admin/Views/layouts/admin.php', [
            'pageTitle' => 'إضافة مناسبة',
            'contentView' => __DIR__ . '/../Views/form.php',
            'eventItem' => null,
            'cities' => $this->cityList(),
        ]);
    }

    public function store(array $params): void
    {
        Auth::requirePermission('content.events');
        Csrf::verifyRequestOrFail();

        $title = Security::cleanText(Request::trimmed('title'));
        if ($title === '') {
            Session::flash('error', 'العنوان مطلوب.');
            Response::redirect(Url::admin('events/create'));
        }

        $slug = $this->uniqueSlug(Request::trimmed('slug') !== '' ? Str::slug(Request::trimmed('slug'), 'event') : Str::slug($title, 'event'));
        $coverId = $this->handleSingleUpload('cover');
        $status = $this->resolveStatus();

        Database::insert('events', [
            'title' => $title,
            'slug' => $slug,
            'event_type' => $this->resolveEventType(),
            'excerpt' => Security::cleanText(Request::trimmed('excerpt')),
            'content' => Security::cleanHtml((string) Request::post('content', '')),
            'cover_media_id' => $coverId,
            'gallery_media_ids' => $this->handleMultiUpload('gallery') ?: null,
            'video_url' => Security::cleanText(Request::trimmed('video_url')),
            'external_links' => Security::cleanText(Request::trimmed('external_links')),
            'drive_url' => Security::cleanText(Request::trimmed('drive_url')),
            'attachment_media_ids' => $this->handleMultiUpload('attachments') ?: null,
            'city_id' => Request::int('city_id') ?: null,
            'venue_name' => Security::cleanText(Request::trimmed('venue_name')),
            'maps_url' => Security::cleanText(Request::trimmed('maps_url')),
            'starts_at' => $this->toDatetime(Request::trimmed('starts_at')),
            'ends_at' => $this->toDatetime(Request::trimmed('ends_at')),
            'tags' => Security::cleanText(Request::trimmed('tags')),
            'status' => $status,
            'is_featured' => Request::bool('is_featured') ? 1 : 0,
            'is_pinned' => Request::bool('is_pinned') ? 1 : 0,
            'seo_title' => Security::cleanText(Request::trimmed('seo_title')),
            'seo_description' => Security::cleanText(Request::trimmed('seo_description')),
            'published_at' => $status === 'published' ? date('Y-m-d H:i:s') : null,
            'created_by' => Auth::user()['id'] ?? null,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        ActivityLog::record('event_create', 'إضافة مناسبة: ' . $title);
        Session::flash('success', 'تمت إضافة المناسبة.');
        Response::redirect(Url::admin('events'));
    }

    public function edit(array $params): void
    {
        Auth::requirePermission('content.events');
        $item = Database::fetchOne('SELECT * FROM ' . Database::table('events') . ' WHERE id = ?', [(int) $params['id']]);
        if (!$item) {
            Response::redirect(Url::admin('events'));
        }

        echo View::render(CORE_PATH . '/Admin/Views/layouts/admin.php', [
            'pageTitle' => 'تعديل مناسبة',
            'contentView' => __DIR__ . '/../Views/form.php',
            'eventItem' => $item,
            'cities' => $this->cityList(),
        ]);
    }

    public function update(array $params): void
    {
        Auth::requirePermission('content.events');
        Csrf::verifyRequestOrFail();

        $id = (int) $params['id'];
        $existing = Database::fetchOne('SELECT * FROM ' . Database::table('events') . ' WHERE id = ?', [$id]);
        if (!$existing) {
            Response::redirect(Url::admin('events'));
        }

        $title = Security::cleanText(Request::trimmed('title'));
        $slug = $this->uniqueSlug(Request::trimmed('slug') !== '' ? Str::slug(Request::trimmed('slug'), 'event') : Str::slug($title, 'event'), $id);
        $status = $this->resolveStatus();

        \Core\Redirects::recordSlugChange('events', $existing['slug'], $slug);

        $data = [
            'title' => $title,
            'slug' => $slug,
            'event_type' => $this->resolveEventType(),
            'excerpt' => Security::cleanText(Request::trimmed('excerpt')),
            'content' => Security::cleanHtml((string) Request::post('content', '')),
            'video_url' => Security::cleanText(Request::trimmed('video_url')),
            'external_links' => Security::cleanText(Request::trimmed('external_links')),
            'drive_url' => Security::cleanText(Request::trimmed('drive_url')),
            'city_id' => Request::int('city_id') ?: null,
            'venue_name' => Security::cleanText(Request::trimmed('venue_name')),
            'maps_url' => Security::cleanText(Request::trimmed('maps_url')),
            'starts_at' => $this->toDatetime(Request::trimmed('starts_at')),
            'ends_at' => $this->toDatetime(Request::trimmed('ends_at')),
            'tags' => Security::cleanText(Request::trimmed('tags')),
            'status' => $status,
            'is_featured' => Request::bool('is_featured') ? 1 : 0,
            'is_pinned' => Request::bool('is_pinned') ? 1 : 0,
            'seo_title' => Security::cleanText(Request::trimmed('seo_title')),
            'seo_description' => Security::cleanText(Request::trimmed('seo_description')),
            'published_at' => $status === 'published' ? ($existing['published_at'] ?: date('Y-m-d H:i:s')) : $existing['published_at'],
            'updated_at' => date('Y-m-d H:i:s'),
        ];

        $coverId = $this->handleSingleUpload('cover');
        if ($coverId) {
            $data['cover_media_id'] = $coverId;
        }

        $newGallery = $this->handleMultiUpload('gallery');
        if ($newGallery) {
            $existingGallery = trim((string) ($existing['gallery_media_ids'] ?? ''));
            $data['gallery_media_ids'] = $existingGallery !== '' ? $existingGallery . ',' . $newGallery : $newGallery;
        }

        $newAttachments = $this->handleMultiUpload('attachments');
        if ($newAttachments) {
            $existingAttachments = trim((string) ($existing['attachment_media_ids'] ?? ''));
            $data['attachment_media_ids'] = $existingAttachments !== '' ? $existingAttachments . ',' . $newAttachments : $newAttachments;
        }

        Database::update('events', $data, ['id' => $id]);

        ActivityLog::record('event_update', 'تعديل مناسبة: ' . $title, 'event', $id);
        Session::flash('success', 'تم تحديث المناسبة.');
        Response::redirect(Url::admin('events'));
    }

    public function delete(array $params): void
    {
        Auth::requirePermission('content.events');
        Csrf::verifyRequestOrFail();

        $id = (int) $params['id'];
        Database::delete('events', ['id' => $id]);
        ActivityLog::record('event_delete', 'حذف مناسبة رقم: ' . $id);
        Session::flash('success', 'تم حذف المناسبة.');
        Response::redirect(Url::admin('events'));
    }

    private function cityList(): array
    {
        return Database::fetchAll('SELECT * FROM ' . Database::table('cities') . ' ORDER BY sort_order ASC, id ASC');
    }

    private function resolveEventType(): string
    {
        $type = Request::trimmed('event_type');
        if ($type === 'مخصص' || $type === 'custom' || $type === '') {
            $custom = Security::cleanText(Request::trimmed('event_type_custom'));
            return $custom !== '' ? $custom : ($type === '' ? '' : $type);
        }
        return Security::cleanText($type);
    }

    private function resolveStatus(): string
    {
        $status = Request::post('status', 'draft');
        return in_array($status, ['draft', 'published', 'archived'], true) ? $status : 'draft';
    }

    private function toDatetime(string $value): ?string
    {
        if ($value === '') {
            return null;
        }
        $ts = strtotime($value);
        return $ts ? date('Y-m-d H:i:s', $ts) : null;
    }

    private function handleSingleUpload(string $field): ?int
    {
        $file = Request::file($field);
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

    /** يرفع عدة ملفات من حقل واحد (name="field[]") ويعيد قائمة معرّفات الوسائط مفصولة بفواصل */
    private function handleMultiUpload(string $field): string
    {
        if (empty($_FILES[$field]) || empty($_FILES[$field]['name']) || !is_array($_FILES[$field]['name'])) {
            return '';
        }

        $ids = [];
        $count = count($_FILES[$field]['name']);
        for ($i = 0; $i < $count; $i++) {
            if ((int) $_FILES[$field]['error'][$i] === UPLOAD_ERR_NO_FILE) {
                continue;
            }
            $file = [
                'name' => $_FILES[$field]['name'][$i],
                'type' => $_FILES[$field]['type'][$i],
                'tmp_name' => $_FILES[$field]['tmp_name'][$i],
                'error' => $_FILES[$field]['error'][$i],
                'size' => $_FILES[$field]['size'][$i],
            ];
            try {
                $data = Media::handleUpload($file, Auth::user()['id'] ?? null);
                $ids[] = (int) Database::insert('media', array_merge($data, ['created_at' => date('Y-m-d H:i:s')]));
            } catch (\Throwable $e) {
                Session::flash('error', $e->getMessage());
            }
        }

        return implode(',', $ids);
    }

    private function uniqueSlug(string $slug, ?int $excludeId = null): string
    {
        $base = $slug;
        $i = 1;
        while (true) {
            $sql = 'SELECT COUNT(*) FROM ' . Database::table('events') . ' WHERE slug = ?';
            $params = [$slug];
            if ($excludeId) {
                $sql .= ' AND id != ?';
                $params[] = $excludeId;
            }
            if (!(int) Database::fetchValue($sql, $params)) {
                return $slug;
            }
            $slug = $base . '-' . (++$i);
        }
    }
}
