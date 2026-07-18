<?php

namespace Modules\Gatherings\Front\Controllers;

use Core\Database;
use Core\Settings;
use Core\View;

final class GatheringsFrontController
{
    public function index(array $params): void
    {
        // كل الجمعات النشطة دفعة واحدة مرتبة حسب المدينة (بدون تصفية أو ترقيم صفحات)
        $rows = Database::fetchAll(
            'SELECT g.*, c.name AS city_name FROM ' . Database::table('gatherings') . ' g
             LEFT JOIN ' . Database::table('cities') . " c ON c.id = g.city_id
             WHERE g.status = 'active'
             ORDER BY (c.name IS NULL) ASC, c.name ASC, g.id DESC
             LIMIT 200"
        );

        $layout = CORE_PATH . '/Front/Views/layouts/main.php';
        $view = __DIR__ . '/../Views/index.php';

        echo View::renderLayout($layout, $view, [
            'rows' => $rows,
            'pageTitle' => 'الجمعات',
            'metaDescription' => Settings::get('seo_default_description', ''),
        ]);
    }
}
