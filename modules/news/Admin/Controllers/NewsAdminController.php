<?php

namespace Modules\News\Admin\Controllers;

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

final class NewsAdminController
{
    public function index(array $params): void
    {
        Auth::requirePermission('content.news');

        $rows = Database::fetchAll(
            'SELECT n.*, c.name AS category_name FROM ' . Database::table('news') . ' n
             LEFT JOIN ' . Database::table('news_categories') . ' c ON c.id = n.category_id
             ORDER BY n.id DESC'
        );

        echo View::render(CORE_PATH . '/Admin/Views/layouts/admin.php', [
            'pageTitle' => 'الأخبار',
            'contentView' => __DIR__ . '/../Views/index.php',
            'rows' => $rows,
        ]);
    }

    public function create(array $params): void
    {
        Auth::requirePermission('content.news');
        echo View::render(CORE_PATH . '/Admin/Views/layouts/admin.php', [
            'pageTitle' => 'إضافة خبر',
            'contentView' => __DIR__ . '/../Views/form.php',
            'item' => null,
            'categories' => $this->categoryList(),
        ]);
    }

    public function store(array $params): void
    {
        Auth::requirePermission('content.news');
        Csrf::verifyRequestOrFail();

        $title = Security::cleanText(Request::trimmed('title'));
        if ($title === '') {
            Session::flash('error', 'العنوان مطلوب.');
            Response::redirect(Url::admin('news/create'));
        }

        $slug = $this->uniqueSlug(Request::trimmed('slug') !== '' ? Str::slug(Request::trimmed('slug'), 'news') : Str::slug($title, 'news'));
        $coverId = $this->handleCoverUpload();
        $status = $this->resolveStatus();

        Database::insert('news', [
            'title' => $title,
            'slug' => $slug,
            'excerpt' => Security::cleanText(Request::trimmed('excerpt')),
            'content' => Security::cleanHtml((string) Request::post('content', '')),
            'category_id' => Request::int('category_id') ?: null,
            'tags' => Security::cleanText(Request::trimmed('tags')),
            'cover_media_id' => $coverId,
            'video_url' => Security::cleanText(Request::trimmed('video_url')),
            'external_links' => Security::cleanText(Request::trimmed('external_links')),
            'author_name' => Security::cleanText(Request::trimmed('author_name')),
            'status' => $status,
            'is_pinned' => Request::bool('is_pinned') ? 1 : 0,
            'is_featured' => Request::bool('is_featured') ? 1 : 0,
            'seo_title' => Security::cleanText(Request::trimmed('seo_title')),
            'seo_description' => Security::cleanText(Request::trimmed('seo_description')),
            'published_at' => $this->resolvePublishedAt($status),
            'created_by' => Auth::user()['id'] ?? null,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        ActivityLog::record('news_create', 'إضافة خبر: ' . $title);
        Session::flash('success', 'تمت إضافة الخبر.');
        Response::redirect(Url::admin('news'));
    }

    public function edit(array $params): void
    {
        Auth::requirePermission('content.news');
        $item = Database::fetchOne('SELECT * FROM ' . Database::table('news') . ' WHERE id = ?', [(int) $params['id']]);
        if (!$item) {
            Response::redirect(Url::admin('news'));
        }

        echo View::render(CORE_PATH . '/Admin/Views/layouts/admin.php', [
            'pageTitle' => 'تعديل خبر',
            'contentView' => __DIR__ . '/../Views/form.php',
            'item' => $item,
            'categories' => $this->categoryList(),
        ]);
    }

    public function update(array $params): void
    {
        Auth::requirePermission('content.news');
        Csrf::verifyRequestOrFail();

        $id = (int) $params['id'];
        $existing = Database::fetchOne('SELECT slug FROM ' . Database::table('news') . ' WHERE id = ?', [$id]);
        $title = Security::cleanText(Request::trimmed('title'));
        $slug = $this->uniqueSlug(Request::trimmed('slug') !== '' ? Str::slug(Request::trimmed('slug'), 'news') : Str::slug($title, 'news'), $id);
        $status = $this->resolveStatus();

        if ($existing) {
            \Core\Redirects::recordSlugChange('news', $existing['slug'], $slug);
        }

        $data = [
            'title' => $title,
            'slug' => $slug,
            'excerpt' => Security::cleanText(Request::trimmed('excerpt')),
            'content' => Security::cleanHtml((string) Request::post('content', '')),
            'category_id' => Request::int('category_id') ?: null,
            'tags' => Security::cleanText(Request::trimmed('tags')),
            'video_url' => Security::cleanText(Request::trimmed('video_url')),
            'external_links' => Security::cleanText(Request::trimmed('external_links')),
            'author_name' => Security::cleanText(Request::trimmed('author_name')),
            'status' => $status,
            'is_pinned' => Request::bool('is_pinned') ? 1 : 0,
            'is_featured' => Request::bool('is_featured') ? 1 : 0,
            'seo_title' => Security::cleanText(Request::trimmed('seo_title')),
            'seo_description' => Security::cleanText(Request::trimmed('seo_description')),
            'published_at' => $this->resolvePublishedAt($status),
            'updated_at' => date('Y-m-d H:i:s'),
        ];

        $coverId = $this->handleCoverUpload();
        if ($coverId) {
            $data['cover_media_id'] = $coverId;
        }

        Database::update('news', $data, ['id' => $id]);

        ActivityLog::record('news_update', 'تعديل خبر: ' . $title, 'news', $id);
        Session::flash('success', 'تم تحديث الخبر.');
        Response::redirect(Url::admin('news'));
    }

    public function delete(array $params): void
    {
        Auth::requirePermission('content.news');
        Csrf::verifyRequestOrFail();

        $id = (int) $params['id'];
        Database::delete('news', ['id' => $id]);
        ActivityLog::record('news_delete', 'حذف خبر رقم: ' . $id);
        Session::flash('success', 'تم حذف الخبر.');
        Response::redirect(Url::admin('news'));
    }

    public function categories(array $params): void
    {
        Auth::requirePermission('content.news');
        echo View::render(CORE_PATH . '/Admin/Views/layouts/admin.php', [
            'pageTitle' => 'تصنيفات الأخبار',
            'contentView' => __DIR__ . '/../Views/categories.php',
            'categories' => $this->categoryList(),
        ]);
    }

    public function categoryStore(array $params): void
    {
        Auth::requirePermission('content.news');
        Csrf::verifyRequestOrFail();

        $name = Security::cleanText(Request::trimmed('name'));
        if ($name !== '') {
            Database::insert('news_categories', [
                'name' => $name,
                'slug' => Str::slug($name, 'cat'),
                'sort_order' => (int) Database::fetchValue('SELECT COALESCE(MAX(sort_order),0) FROM ' . Database::table('news_categories')) + 1,
            ]);
        }

        Response::redirect(Url::admin('news-categories'));
    }

    public function categoryDelete(array $params): void
    {
        Auth::requirePermission('content.news');
        Csrf::verifyRequestOrFail();

        Database::delete('news_categories', ['id' => (int) $params['id']]);
        Response::redirect(Url::admin('news-categories'));
    }

    private function categoryList(): array
    {
        return Database::fetchAll('SELECT * FROM ' . Database::table('news_categories') . ' ORDER BY sort_order ASC');
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

    private function resolveStatus(): string
    {
        $status = Request::post('status', 'draft');
        return in_array($status, ['draft', 'published', 'scheduled', 'archived'], true) ? $status : 'draft';
    }

    private function resolvePublishedAt(string $status): ?string
    {
        if ($status === 'scheduled') {
            $date = Request::trimmed('scheduled_at');
            return $date !== '' ? date('Y-m-d H:i:s', strtotime($date)) : date('Y-m-d H:i:s');
        }
        if ($status === 'published') {
            return date('Y-m-d H:i:s');
        }
        return null;
    }

    private function uniqueSlug(string $slug, ?int $excludeId = null): string
    {
        $base = $slug;
        $i = 1;
        while (true) {
            $sql = 'SELECT COUNT(*) FROM ' . Database::table('news') . ' WHERE slug = ?';
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
