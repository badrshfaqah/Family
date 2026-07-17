<?php

namespace Modules\Pages\Admin\Controllers;

use Core\ActivityLog;
use Core\Auth;
use Core\Database;
use Core\Support\Csrf;
use Core\Support\Request;
use Core\Support\Response;
use Core\Support\Security;
use Core\Support\Session;
use Core\Support\Str;
use Core\Support\Url;
use Core\View;

final class PagesAdminController
{
    public function index(array $params): void
    {
        Auth::requirePermission('content.pages');

        $pages = Database::fetchAll('SELECT * FROM ' . Database::table('pages') . ' ORDER BY sort_order ASC, id DESC');

        echo View::render(CORE_PATH . '/Admin/Views/layouts/admin.php', [
            'pageTitle' => 'الصفحات',
            'contentView' => __DIR__ . '/../Views/index.php',
            'pages' => $pages,
        ]);
    }

    public function create(array $params): void
    {
        Auth::requirePermission('content.pages');

        echo View::render(CORE_PATH . '/Admin/Views/layouts/admin.php', [
            'pageTitle' => 'إضافة صفحة',
            'contentView' => __DIR__ . '/../Views/form.php',
            'page' => null,
        ]);
    }

    public function store(array $params): void
    {
        Auth::requirePermission('content.pages');
        Csrf::verifyRequestOrFail();

        $title = Security::cleanText(Request::trimmed('title'));
        if ($title === '') {
            Session::flash('error', 'العنوان مطلوب.');
            Response::redirect(Url::admin('pages/create'));
        }

        $slug = Request::trimmed('slug') !== '' ? Str::slug(Request::trimmed('slug'), 'page') : Str::slug($title, 'page');
        $slug = $this->uniqueSlug($slug);

        Database::insert('pages', [
            'title' => $title,
            'slug' => $slug,
            'content' => Security::cleanHtml((string) Request::post('content', '')),
            'template' => 'default',
            'status' => Request::post('status') === 'published' ? 'published' : 'draft',
            'sort_order' => Request::int('sort_order', 0),
            'show_in_menu' => Request::bool('show_in_menu') ? 1 : 0,
            'seo_title' => Security::cleanText(Request::trimmed('seo_title')),
            'seo_description' => Security::cleanText(Request::trimmed('seo_description')),
            'created_by' => Auth::user()['id'] ?? null,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        ActivityLog::record('page_create', 'إضافة صفحة: ' . $title);
        Session::flash('success', 'تمت إضافة الصفحة.');
        Response::redirect(Url::admin('pages'));
    }

    public function edit(array $params): void
    {
        Auth::requirePermission('content.pages');

        $page = Database::fetchOne('SELECT * FROM ' . Database::table('pages') . ' WHERE id = ?', [(int) $params['id']]);
        if (!$page) {
            Response::redirect(Url::admin('pages'));
        }

        echo View::render(CORE_PATH . '/Admin/Views/layouts/admin.php', [
            'pageTitle' => 'تعديل صفحة',
            'contentView' => __DIR__ . '/../Views/form.php',
            'page' => $page,
        ]);
    }

    public function update(array $params): void
    {
        Auth::requirePermission('content.pages');
        Csrf::verifyRequestOrFail();

        $id = (int) $params['id'];
        $existing = Database::fetchOne('SELECT slug FROM ' . Database::table('pages') . ' WHERE id = ?', [$id]);
        $title = Security::cleanText(Request::trimmed('title'));
        $slug = Request::trimmed('slug') !== '' ? Str::slug(Request::trimmed('slug'), 'page') : Str::slug($title, 'page');
        $slug = $this->uniqueSlug($slug, $id);

        if ($existing) {
            \Core\Redirects::recordSlugChange('', $existing['slug'], $slug);
        }

        Database::update('pages', [
            'title' => $title,
            'slug' => $slug,
            'content' => Security::cleanHtml((string) Request::post('content', '')),
            'status' => Request::post('status') === 'published' ? 'published' : 'draft',
            'sort_order' => Request::int('sort_order', 0),
            'show_in_menu' => Request::bool('show_in_menu') ? 1 : 0,
            'seo_title' => Security::cleanText(Request::trimmed('seo_title')),
            'seo_description' => Security::cleanText(Request::trimmed('seo_description')),
            'updated_at' => date('Y-m-d H:i:s'),
        ], ['id' => $id]);

        ActivityLog::record('page_update', 'تعديل صفحة: ' . $title, 'page', $id);
        Session::flash('success', 'تم تحديث الصفحة.');
        Response::redirect(Url::admin('pages'));
    }

    public function delete(array $params): void
    {
        Auth::requirePermission('content.pages');
        Csrf::verifyRequestOrFail();

        $id = (int) $params['id'];
        Database::delete('pages', ['id' => $id]);
        ActivityLog::record('page_delete', 'حذف صفحة رقم: ' . $id);
        Session::flash('success', 'تم حذف الصفحة.');
        Response::redirect(Url::admin('pages'));
    }

    private function uniqueSlug(string $slug, ?int $excludeId = null): string
    {
        $base = $slug;
        $i = 1;
        while (true) {
            $sql = 'SELECT COUNT(*) FROM ' . Database::table('pages') . ' WHERE slug = ?';
            $params = [$slug];
            if ($excludeId) {
                $sql .= ' AND id != ?';
                $params[] = $excludeId;
            }
            $exists = (int) Database::fetchValue($sql, $params);
            if (!$exists) {
                return $slug;
            }
            $slug = $base . '-' . (++$i);
        }
    }
}
