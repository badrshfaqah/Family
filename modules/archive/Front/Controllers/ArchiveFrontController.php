<?php

namespace Modules\Archive\Front\Controllers;

use Core\Database;
use Core\Settings;
use Core\Support\Request;
use Core\Terms;
use Core\View;

final class ArchiveFrontController
{
    public function index(array $params): void
    {
        $page = max(1, Request::int('page', 1));
        $perPage = 12;
        $offset = ($page - 1) * $perPage;

        $where = ["a.status = 'published'"];
        $queryParams = [];

        $categoryId = Request::int('category_id');
        if ($categoryId) {
            $where[] = 'a.category_id = ?';
            $queryParams[] = $categoryId;
        }

        $whereSql = implode(' AND ', $where);

        $total = (int) Database::fetchValue(
            'SELECT COUNT(*) FROM ' . Database::table('archive_items') . ' a WHERE ' . $whereSql,
            $queryParams
        );

        $rows = Database::fetchAll(
            'SELECT a.*, c.name AS category_name FROM ' . Database::table('archive_items') . ' a
             LEFT JOIN ' . Database::table('archive_categories') . ' c ON c.id = a.category_id
             WHERE ' . $whereSql . '
             ORDER BY a.id DESC
             LIMIT ' . $perPage . ' OFFSET ' . $offset,
            $queryParams
        );

        // التصنيفات مع عدد المواد المنشورة في كل تصنيف لعرضها كبطاقات بارزة
        $categories = Database::fetchAll(
            'SELECT c.*, (SELECT COUNT(*) FROM ' . Database::table('archive_items') . " a2
                          WHERE a2.category_id = c.id AND a2.status = 'published') AS items_count
             FROM " . Database::table('archive_categories') . ' c
             ORDER BY c.sort_order ASC'
        );
        $allCount = (int) Database::fetchValue(
            'SELECT COUNT(*) FROM ' . Database::table('archive_items') . " WHERE status = 'published'"
        );

        $layout = CORE_PATH . '/Front/Views/layouts/main.php';
        $view = __DIR__ . '/../Views/index.php';

        echo View::renderLayout($layout, $view, [
            'rows' => $rows,
            'page' => $page,
            'perPage' => $perPage,
            'total' => $total,
            'categories' => $categories,
            'allCount' => $allCount,
            'currentCategory' => $categoryId,
            'pageTitle' => Terms::phrase('archive'),
            'metaDescription' => Settings::get('seo_default_description', ''),
        ]);
    }

    public function show(array $params): void
    {
        $slug = $params['slug'] ?? '';

        $item = Database::fetchOne(
            'SELECT a.*, c.name AS category_name FROM ' . Database::table('archive_items') . ' a
             LEFT JOIN ' . Database::table('archive_categories') . ' c ON c.id = a.category_id
             WHERE a.slug = ? AND a.status = "published"',
            [$slug]
        );

        if (!$item) {
            \Core\Front\NotFound::render();
            return;
        }

        $file = $item['file_media_id']
            ? Database::fetchOne('SELECT * FROM ' . Database::table('media') . ' WHERE id = ?', [$item['file_media_id']])
            : null;

        $layout = CORE_PATH . '/Front/Views/layouts/main.php';
        $view = __DIR__ . '/../Views/show.php';

        echo View::renderLayout($layout, $view, [
            'item' => $item,
            'file' => $file,
            'pageTitle' => $item['seo_title'] ?: $item['title'],
            'metaDescription' => $item['seo_description'] ?: (mb_substr((string) $item['description'], 0, 160)),
        ]);
    }
}
