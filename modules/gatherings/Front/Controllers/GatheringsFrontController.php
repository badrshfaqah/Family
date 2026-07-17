<?php

namespace Modules\Gatherings\Front\Controllers;

use Core\Database;
use Core\Settings;
use Core\Support\Request;
use Core\View;

final class GatheringsFrontController
{
    public function index(array $params): void
    {
        $page = max(1, Request::int('page', 1));
        $perPage = 12;
        $offset = ($page - 1) * $perPage;

        $cityId = Request::int('city_id', 0);

        $where = "g.status = 'active'";
        $queryParams = [];
        if ($cityId > 0) {
            $where .= ' AND g.city_id = ?';
            $queryParams[] = $cityId;
        }

        $total = (int) Database::fetchValue(
            'SELECT COUNT(*) FROM ' . Database::table('gatherings') . ' g WHERE ' . $where,
            $queryParams
        );

        $rows = Database::fetchAll(
            'SELECT g.*, c.name AS city_name FROM ' . Database::table('gatherings') . ' g
             LEFT JOIN ' . Database::table('cities') . ' c ON c.id = g.city_id
             WHERE ' . $where . '
             ORDER BY g.id DESC
             LIMIT ' . $perPage . ' OFFSET ' . $offset,
            $queryParams
        );

        $cities = Database::fetchAll(
            'SELECT DISTINCT c.id, c.name FROM ' . Database::table('gatherings') . ' g
             JOIN ' . Database::table('cities') . " c ON c.id = g.city_id
             WHERE g.status = 'active'
             ORDER BY c.name ASC"
        );

        $layout = CORE_PATH . '/Front/Views/layouts/main.php';
        $view = __DIR__ . '/../Views/index.php';

        echo View::renderLayout($layout, $view, [
            'rows' => $rows,
            'cities' => $cities,
            'selectedCity' => $cityId,
            'page' => $page,
            'perPage' => $perPage,
            'total' => $total,
            'pageTitle' => 'الجمعات',
            'metaDescription' => Settings::get('seo_default_description', ''),
        ]);
    }
}
