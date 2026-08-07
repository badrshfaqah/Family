<?php

namespace Modules\Gallery\Admin\Controllers;

use Core\ActivityLog;
use Core\Auth;
use Core\Database;
use Core\Media;
use Core\Settings;
use Core\Support\Csrf;
use Core\Support\Request;
use Core\Support\Response;
use Core\Support\Security;
use Core\Support\Session;
use Core\Support\Str;
use Core\Support\Url;
use Core\View;

final class GalleryAdminController
{
    public function index(array $params): void
    {
        Auth::requirePermission('content.gallery');

        $rows = Database::fetchAll(
            'SELECT a.*, c.name AS city_name,
                    (SELECT COUNT(*) FROM ' . Database::table('gallery_photos') . ' gp WHERE gp.album_id = a.id) AS photos_count
             FROM ' . Database::table('gallery_albums') . ' a
             LEFT JOIN ' . Database::table('cities') . ' c ON c.id = a.city_id
             ORDER BY a.sort_order ASC, a.id DESC'
        );

        echo View::render(CORE_PATH . '/Admin/Views/layouts/admin.php', [
            'pageTitle' => 'المعرض',
            'contentView' => __DIR__ . '/../Views/index.php',
            'rows' => $rows,
        ]);
    }

    public function create(array $params): void
    {
        Auth::requirePermission('content.gallery');
        echo View::render(CORE_PATH . '/Admin/Views/layouts/admin.php', [
            'pageTitle' => 'إضافة ألبوم',
            'contentView' => __DIR__ . '/../Views/form.php',
            'item' => null,
            'photos' => [],
            'cities' => $this->cityList(),
            'branches' => $this->branchList(),
            'branchesEnabled' => Settings::get('branches_enabled', '0') === '1',
        ]);
    }

    public function store(array $params): void
    {
        Auth::requirePermission('content.gallery');
        Csrf::verifyRequestOrFail();

        $title = Security::cleanText(Request::trimmed('title'));
        if ($title === '') {
            Session::flash('error', 'عنوان الألبوم مطلوب.');
            Response::redirect(Url::admin('gallery/create'));
        }

        $slug = $this->uniqueSlug(Request::trimmed('slug') !== '' ? Str::slug(Request::trimmed('slug'), 'album') : Str::slug($title, 'album'));
        $coverId = $this->handleSingleUpload('cover');

        $albumId = (int) Database::insert('gallery_albums', [
            'title' => $title,
            'slug' => $slug,
            'description' => Security::cleanText(Request::trimmed('description')),
            'cover_media_id' => $coverId,
            'album_type' => $this->resolveAlbumType(),
            'video_url' => $this->resolveVideoUrl(),
            'year' => Request::int('year') ?: null,
            'city_id' => Request::int('city_id') ?: null,
            'branch_id' => Request::int('branch_id') ?: null,
            'related_news_id' => Request::int('related_news_id') ?: null,
            'related_event_id' => Request::int('related_event_id') ?: null,
            'status' => $this->resolveStatus(),
            'sort_order' => Request::int('sort_order', 0),
            'created_by' => Auth::user()['id'] ?? null,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        $this->storePhotos($albumId, 'photos');

        ActivityLog::record('gallery_album_create', 'إضافة ألبوم: ' . $title);
        Session::flash('success', 'تمت إضافة الألبوم.');
        Response::redirect(Url::admin('gallery/' . $albumId . '/edit'));
    }

    public function edit(array $params): void
    {
        Auth::requirePermission('content.gallery');
        $item = Database::fetchOne('SELECT * FROM ' . Database::table('gallery_albums') . ' WHERE id = ?', [(int) $params['id']]);
        if (!$item) {
            Response::redirect(Url::admin('gallery'));
        }

        $photos = Database::fetchAll(
            'SELECT gp.*, m.stored_path, m.thumb_path FROM ' . Database::table('gallery_photos') . ' gp
             JOIN ' . Database::table('media') . ' m ON m.id = gp.media_id
             WHERE gp.album_id = ? ORDER BY gp.sort_order ASC, gp.id ASC',
            [$item['id']]
        );

        echo View::render(CORE_PATH . '/Admin/Views/layouts/admin.php', [
            'pageTitle' => 'تعديل ألبوم',
            'contentView' => __DIR__ . '/../Views/form.php',
            'item' => $item,
            'photos' => $photos,
            'cities' => $this->cityList(),
            'branches' => $this->branchList(),
            'branchesEnabled' => Settings::get('branches_enabled', '0') === '1',
        ]);
    }

    public function update(array $params): void
    {
        Auth::requirePermission('content.gallery');
        Csrf::verifyRequestOrFail();

        $id = (int) $params['id'];
        $existing = Database::fetchOne('SELECT slug FROM ' . Database::table('gallery_albums') . ' WHERE id = ?', [$id]);
        $title = Security::cleanText(Request::trimmed('title'));
        $slug = $this->uniqueSlug(Request::trimmed('slug') !== '' ? Str::slug(Request::trimmed('slug'), 'album') : Str::slug($title, 'album'), $id);

        if ($existing) {
            \Core\Redirects::recordSlugChange('gallery', $existing['slug'], $slug);
        }

        $data = [
            'title' => $title,
            'slug' => $slug,
            'description' => Security::cleanText(Request::trimmed('description')),
            'album_type' => $this->resolveAlbumType(),
            'video_url' => $this->resolveVideoUrl(),
            'year' => Request::int('year') ?: null,
            'city_id' => Request::int('city_id') ?: null,
            'branch_id' => Request::int('branch_id') ?: null,
            'related_news_id' => Request::int('related_news_id') ?: null,
            'related_event_id' => Request::int('related_event_id') ?: null,
            'status' => $this->resolveStatus(),
            'sort_order' => Request::int('sort_order', 0),
            'updated_at' => date('Y-m-d H:i:s'),
        ];

        $coverId = $this->handleSingleUpload('cover');
        if ($coverId) {
            $data['cover_media_id'] = $coverId;
        }

        Database::update('gallery_albums', $data, ['id' => $id]);
        $this->storePhotos($id, 'photos');

        ActivityLog::record('gallery_album_update', 'تعديل ألبوم: ' . $title, 'gallery_album', $id);
        Session::flash('success', 'تم تحديث الألبوم.');
        Response::redirect(Url::admin('gallery/' . $id . '/edit'));
    }

    public function delete(array $params): void
    {
        Auth::requirePermission('content.gallery');
        Csrf::verifyRequestOrFail();

        $id = (int) $params['id'];
        Database::delete('gallery_photos', ['album_id' => $id]);
        Database::delete('gallery_albums', ['id' => $id]);
        ActivityLog::record('gallery_album_delete', 'حذف ألبوم رقم: ' . $id);
        Session::flash('success', 'تم حذف الألبوم.');
        Response::redirect(Url::admin('gallery'));
    }

    public function photosStore(array $params): void
    {
        Auth::requirePermission('content.gallery');
        Csrf::verifyRequestOrFail();

        $albumId = (int) $params['id'];
        $this->storePhotos($albumId, 'photos');

        Session::flash('success', 'تم رفع الصور.');
        Response::redirect(Url::admin('gallery/' . $albumId . '/edit'));
    }

    public function photoUpdate(array $params): void
    {
        Auth::requirePermission('content.gallery');
        Csrf::verifyRequestOrFail();

        $id = (int) $params['id'];
        $photo = Database::fetchOne('SELECT * FROM ' . Database::table('gallery_photos') . ' WHERE id = ?', [$id]);

        if ($photo) {
            Database::update('gallery_photos', [
                'caption' => Security::cleanText(Request::trimmed('caption')),
                'sort_order' => Request::int('sort_order', 0),
            ], ['id' => $id]);
        }

        Response::redirect(Url::admin('gallery/' . ($photo['album_id'] ?? 0) . '/edit'));
    }

    public function photoDelete(array $params): void
    {
        Auth::requirePermission('content.gallery');
        Csrf::verifyRequestOrFail();

        $id = (int) $params['id'];
        $photo = Database::fetchOne('SELECT * FROM ' . Database::table('gallery_photos') . ' WHERE id = ?', [$id]);
        if ($photo) {
            Database::delete('gallery_photos', ['id' => $id]);
        }

        Response::redirect(Url::admin('gallery/' . ($photo['album_id'] ?? 0) . '/edit'));
    }

    private function storePhotos(int $albumId, string $field): void
    {
        if (empty($_FILES[$field]) || empty($_FILES[$field]['name']) || !is_array($_FILES[$field]['name'])) {
            return;
        }

        $nextOrder = (int) Database::fetchValue(
            'SELECT COALESCE(MAX(sort_order),0) FROM ' . Database::table('gallery_photos') . ' WHERE album_id = ?',
            [$albumId]
        );

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
                $mediaId = (int) Database::insert('media', array_merge($data, ['created_at' => date('Y-m-d H:i:s')]));
                $nextOrder++;
                Database::insert('gallery_photos', [
                    'album_id' => $albumId,
                    'media_id' => $mediaId,
                    'caption' => null,
                    'sort_order' => $nextOrder,
                    'created_at' => date('Y-m-d H:i:s'),
                ]);
            } catch (\Throwable $e) {
                Session::flash('error', $e->getMessage());
            }
        }
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

    private function cityList(): array
    {
        return Database::fetchAll('SELECT * FROM ' . Database::table('cities') . ' ORDER BY sort_order ASC, id ASC');
    }

    private function branchList(): array
    {
        if (!Database::tableExists('branches')) {
            return [];
        }
        return Database::fetchAll('SELECT * FROM ' . Database::table('branches') . " WHERE status = 'active' ORDER BY sort_order ASC, id ASC");
    }

    private function resolveAlbumType(): string
    {
        $type = Request::post('album_type', 'photo');
        return in_array($type, ['photo', 'video'], true) ? $type : 'photo';
    }

    /** رابط فيديو الألبوم (يوتيوب أو قوقل درايف أو أي رابط مباشر) — HTTPS فقط */
    private function resolveVideoUrl(): ?string
    {
        $url = Request::trimmed('video_url');
        if ($url === '') {
            return null;
        }
        if (!filter_var($url, FILTER_VALIDATE_URL) || !str_starts_with($url, 'https://') || strlen($url) > 255) {
            return null;
        }
        return $url;
    }

    private function resolveStatus(): string
    {
        $status = Request::post('status', 'draft');
        return in_array($status, ['draft', 'published'], true) ? $status : 'draft';
    }

    private function uniqueSlug(string $slug, ?int $excludeId = null): string
    {
        $base = $slug;
        $i = 1;
        while (true) {
            $sql = 'SELECT COUNT(*) FROM ' . Database::table('gallery_albums') . ' WHERE slug = ?';
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
