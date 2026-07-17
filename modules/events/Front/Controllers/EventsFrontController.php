<?php

namespace Modules\Events\Front\Controllers;

use Core\Database;
use Core\Settings;
use Core\Support\Request;
use Core\Terms;
use Core\View;

final class EventsFrontController
{
    public function index(array $params): void
    {
        $page = max(1, Request::int('page', 1));
        $perPage = 12;
        $offset = ($page - 1) * $perPage;

        $total = (int) Database::fetchValue(
            'SELECT COUNT(*) FROM ' . Database::table('events') . " WHERE status = 'published' AND published_at <= NOW()"
        );

        $rows = Database::fetchAll(
            'SELECT e.*, c.name AS city_name FROM ' . Database::table('events') . ' e
             LEFT JOIN ' . Database::table('cities') . ' c ON c.id = e.city_id
             WHERE e.status = "published" AND e.published_at <= NOW()
             ORDER BY e.is_pinned DESC, e.starts_at DESC, e.published_at DESC
             LIMIT ' . $perPage . ' OFFSET ' . $offset
        );

        $layout = CORE_PATH . '/Front/Views/layouts/main.php';
        $view = __DIR__ . '/../Views/index.php';

        echo View::renderLayout($layout, $view, [
            'rows' => $rows,
            'page' => $page,
            'perPage' => $perPage,
            'total' => $total,
            'pageTitle' => Terms::phrase('events'),
            'metaDescription' => Settings::get('seo_default_description', ''),
        ]);
    }

    public function show(array $params): void
    {
        $slug = $params['slug'] ?? '';

        $item = Database::fetchOne(
            'SELECT e.*, c.name AS city_name FROM ' . Database::table('events') . ' e
             LEFT JOIN ' . Database::table('cities') . ' c ON c.id = e.city_id
             WHERE e.slug = ? AND e.status = "published" AND e.published_at <= NOW()',
            [$slug]
        );

        if (!$item) {
            \Core\Front\NotFound::render();
            return;
        }

        $related = Database::fetchAll(
            'SELECT id, title, slug, cover_media_id, starts_at FROM ' . Database::table('events') . '
             WHERE status = "published" AND published_at <= NOW() AND id != ? ' .
            ($item['event_type'] ? 'AND event_type = ' . Database::pdo()->quote($item['event_type']) : '') . '
             ORDER BY starts_at DESC LIMIT 3',
            [$item['id']]
        );

        $layout = CORE_PATH . '/Front/Views/layouts/main.php';
        $view = __DIR__ . '/../Views/show.php';

        echo View::renderLayout($layout, $view, [
            'item' => $item,
            'related' => $related,
            'pageTitle' => $item['seo_title'] ?: $item['title'],
            'metaDescription' => $item['seo_description'] ?: $item['excerpt'],
        ]);
    }
}
