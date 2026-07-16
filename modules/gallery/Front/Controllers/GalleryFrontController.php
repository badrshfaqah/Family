<?php

namespace Modules\Gallery\Front\Controllers;

use Core\Database;
use Core\Settings;
use Core\Support\Request;
use Core\View;

final class GalleryFrontController
{
    public function index(array $params): void
    {
        $page = max(1, Request::int('page', 1));
        $perPage = 12;
        $offset = ($page - 1) * $perPage;

        $where = ["a.status = 'published'"];
        $queryParams = [];

        $year = Request::int('year');
        if ($year) {
            $where[] = 'a.year = ?';
            $queryParams[] = $year;
        }

        $cityId = Request::int('city_id');
        if ($cityId) {
            $where[] = 'a.city_id = ?';
            $queryParams[] = $cityId;
        }

        $type = Request::get('type', '');
        if (in_array($type, ['photo', 'video'], true)) {
            $where[] = 'a.album_type = ?';
            $queryParams[] = $type;
        }

        $whereSql = implode(' AND ', $where);

        $total = (int) Database::fetchValue(
            'SELECT COUNT(*) FROM ' . Database::table('gallery_albums') . ' a WHERE ' . $whereSql,
            $queryParams
        );

        $rows = Database::fetchAll(
            'SELECT a.*, c.name AS city_name,
                    (SELECT COUNT(*) FROM ' . Database::table('gallery_photos') . ' gp WHERE gp.album_id = a.id) AS photos_count
             FROM ' . Database::table('gallery_albums') . ' a
             LEFT JOIN ' . Database::table('cities') . ' c ON c.id = a.city_id
             WHERE ' . $whereSql . '
             ORDER BY a.sort_order ASC, a.id DESC
             LIMIT ' . $perPage . ' OFFSET ' . $offset,
            $queryParams
        );

        $years = Database::fetchAll(
            "SELECT DISTINCT year FROM " . Database::table('gallery_albums') . " WHERE status = 'published' AND year IS NOT NULL ORDER BY year DESC"
        );

        $layout = CORE_PATH . '/Front/Views/layouts/main.php';
        $view = __DIR__ . '/../Views/index.php';

        echo View::renderLayout($layout, $view, [
            'rows' => $rows,
            'page' => $page,
            'perPage' => $perPage,
            'total' => $total,
            'years' => $years,
            'currentYear' => $year,
            'currentType' => $type,
            'pageTitle' => 'المعرض',
            'metaDescription' => Settings::get('seo_default_description', ''),
        ]);
    }

    public function show(array $params): void
    {
        $slug = $params['slug'] ?? '';

        $album = Database::fetchOne(
            'SELECT a.*, c.name AS city_name FROM ' . Database::table('gallery_albums') . ' a
             LEFT JOIN ' . Database::table('cities') . ' c ON c.id = a.city_id
             WHERE a.slug = ? AND a.status = "published"',
            [$slug]
        );

        if (!$album) {
            http_response_code(404);
            require CORE_PATH . '/Front/Views/404.php';
            return;
        }

        $photos = Database::fetchAll(
            'SELECT gp.*, m.stored_path, m.thumb_path FROM ' . Database::table('gallery_photos') . ' gp
             JOIN ' . Database::table('media') . ' m ON m.id = gp.media_id
             WHERE gp.album_id = ? ORDER BY gp.sort_order ASC, gp.id ASC',
            [$album['id']]
        );

        $layout = CORE_PATH . '/Front/Views/layouts/main.php';
        $view = __DIR__ . '/../Views/show.php';

        echo View::renderLayout($layout, $view, [
            'album' => $album,
            'photos' => $photos,
            'pageTitle' => $album['title'],
            'metaDescription' => $album['description'] ?? '',
        ]);
    }
}
