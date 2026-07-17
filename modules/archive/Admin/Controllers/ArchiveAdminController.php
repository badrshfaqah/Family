<?php

namespace Modules\Archive\Admin\Controllers;

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

final class ArchiveAdminController
{
    public function index(array $params): void
    {
        Auth::requirePermission('content.archive');

        $rows = Database::fetchAll(
            'SELECT a.*, c.name AS category_name FROM ' . Database::table('archive_items') . ' a
             LEFT JOIN ' . Database::table('archive_categories') . ' c ON c.id = a.category_id
             ORDER BY a.id DESC'
        );

        echo View::render(CORE_PATH . '/Admin/Views/layouts/admin.php', [
            'pageTitle' => 'الأرشيف',
            'contentView' => __DIR__ . '/../Views/index.php',
            'rows' => $rows,
        ]);
    }

    public function create(array $params): void
    {
        Auth::requirePermission('content.archive');
        echo View::render(CORE_PATH . '/Admin/Views/layouts/admin.php', [
            'pageTitle' => 'إضافة عنصر أرشيف',
            'contentView' => __DIR__ . '/../Views/form.php',
            'item' => null,
            'categories' => $this->categoryList(),
        ]);
    }

    public function store(array $params): void
    {
        Auth::requirePermission('content.archive');
        Csrf::verifyRequestOrFail();

        $title = Security::cleanText(Request::trimmed('title'));
        if ($title === '') {
            Session::flash('error', 'العنوان مطلوب.');
            Response::redirect(Url::admin('archive/create'));
        }

        $slug = $this->uniqueSlug(Request::trimmed('slug') !== '' ? Str::slug(Request::trimmed('slug'), 'archive') : Str::slug($title, 'archive'));
        $coverId = $this->handleUpload('cover');
        $fileId = $this->handleUpload('file');
        $status = $this->resolveStatus();

        Database::insert('archive_items', [
            'title' => $title,
            'slug' => $slug,
            'description' => Security::cleanText(Request::trimmed('description')),
            'cover_media_id' => $coverId,
            'file_media_id' => $fileId,
            'category_id' => Request::int('category_id') ?: null,
            'period_label' => Security::cleanText(Request::trimmed('period_label')),
            'source' => Security::cleanText(Request::trimmed('source')),
            'notes' => Security::cleanText(Request::trimmed('notes')),
            'tags' => Security::cleanText(Request::trimmed('tags')),
            'status' => $status,
            'seo_title' => Security::cleanText(Request::trimmed('seo_title')),
            'seo_description' => Security::cleanText(Request::trimmed('seo_description')),
            'created_by' => Auth::user()['id'] ?? null,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        ActivityLog::record('archive_item_create', 'إضافة عنصر أرشيف: ' . $title);
        Session::flash('success', 'تمت إضافة العنصر إلى الأرشيف.');
        Response::redirect(Url::admin('archive'));
    }

    public function edit(array $params): void
    {
        Auth::requirePermission('content.archive');
        $item = Database::fetchOne('SELECT * FROM ' . Database::table('archive_items') . ' WHERE id = ?', [(int) $params['id']]);
        if (!$item) {
            Response::redirect(Url::admin('archive'));
        }

        echo View::render(CORE_PATH . '/Admin/Views/layouts/admin.php', [
            'pageTitle' => 'تعديل عنصر أرشيف',
            'contentView' => __DIR__ . '/../Views/form.php',
            'item' => $item,
            'categories' => $this->categoryList(),
        ]);
    }

    public function update(array $params): void
    {
        Auth::requirePermission('content.archive');
        Csrf::verifyRequestOrFail();

        $id = (int) $params['id'];
        $existing = Database::fetchOne('SELECT slug FROM ' . Database::table('archive_items') . ' WHERE id = ?', [$id]);
        $title = Security::cleanText(Request::trimmed('title'));
        $slug = $this->uniqueSlug(Request::trimmed('slug') !== '' ? Str::slug(Request::trimmed('slug'), 'archive') : Str::slug($title, 'archive'), $id);
        $status = $this->resolveStatus();

        if ($existing) {
            \Core\Redirects::recordSlugChange('archive', $existing['slug'], $slug);
        }

        $data = [
            'title' => $title,
            'slug' => $slug,
            'description' => Security::cleanText(Request::trimmed('description')),
            'category_id' => Request::int('category_id') ?: null,
            'period_label' => Security::cleanText(Request::trimmed('period_label')),
            'source' => Security::cleanText(Request::trimmed('source')),
            'notes' => Security::cleanText(Request::trimmed('notes')),
            'tags' => Security::cleanText(Request::trimmed('tags')),
            'status' => $status,
            'seo_title' => Security::cleanText(Request::trimmed('seo_title')),
            'seo_description' => Security::cleanText(Request::trimmed('seo_description')),
            'updated_at' => date('Y-m-d H:i:s'),
        ];

        $coverId = $this->handleUpload('cover');
        if ($coverId) {
            $data['cover_media_id'] = $coverId;
        }
        $fileId = $this->handleUpload('file');
        if ($fileId) {
            $data['file_media_id'] = $fileId;
        }

        Database::update('archive_items', $data, ['id' => $id]);

        ActivityLog::record('archive_item_update', 'تعديل عنصر أرشيف: ' . $title, 'archive_item', $id);
        Session::flash('success', 'تم تحديث العنصر.');
        Response::redirect(Url::admin('archive'));
    }

    public function delete(array $params): void
    {
        Auth::requirePermission('content.archive');
        Csrf::verifyRequestOrFail();

        $id = (int) $params['id'];
        Database::delete('archive_items', ['id' => $id]);
        ActivityLog::record('archive_item_delete', 'حذف عنصر أرشيف رقم: ' . $id);
        Session::flash('success', 'تم حذف العنصر.');
        Response::redirect(Url::admin('archive'));
    }

    public function categories(array $params): void
    {
        Auth::requirePermission('content.archive');
        echo View::render(CORE_PATH . '/Admin/Views/layouts/admin.php', [
            'pageTitle' => 'تصنيفات الأرشيف',
            'contentView' => __DIR__ . '/../Views/categories.php',
            'categories' => $this->categoryList(),
        ]);
    }

    public function categoryStore(array $params): void
    {
        Auth::requirePermission('content.archive');
        Csrf::verifyRequestOrFail();

        $name = Security::cleanText(Request::trimmed('name'));
        if ($name !== '') {
            Database::insert('archive_categories', [
                'name' => $name,
                'slug' => Str::slug($name, 'cat'),
                'sort_order' => (int) Database::fetchValue('SELECT COALESCE(MAX(sort_order),0) FROM ' . Database::table('archive_categories')) + 1,
            ]);
        }

        Response::redirect(Url::admin('archive-categories'));
    }

    public function categoryDelete(array $params): void
    {
        Auth::requirePermission('content.archive');
        Csrf::verifyRequestOrFail();

        Database::delete('archive_categories', ['id' => (int) $params['id']]);
        Response::redirect(Url::admin('archive-categories'));
    }

    private function categoryList(): array
    {
        return Database::fetchAll('SELECT * FROM ' . Database::table('archive_categories') . ' ORDER BY sort_order ASC');
    }

    private function handleUpload(string $field): ?int
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
            $sql = 'SELECT COUNT(*) FROM ' . Database::table('archive_items') . ' WHERE slug = ?';
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
