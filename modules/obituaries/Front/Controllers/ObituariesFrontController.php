<?php

namespace Modules\Obituaries\Front\Controllers;

use Core\Database;
use Core\Settings;
use Core\Support\Response;
use Core\Support\Url;
use Core\View;

final class ObituariesFrontController
{
    public function index(array $params): void
    {
        $rows = Database::fetchAll(
            'SELECT o.*, c.name AS city_name FROM ' . Database::table('obituaries') . ' o
             LEFT JOIN ' . Database::table('cities') . " c ON c.id = o.city_id
             WHERE o.status = 'active'
             ORDER BY o.id DESC LIMIT 100"
        );

        $layout = CORE_PATH . '/Front/Views/layouts/main.php';
        $view = __DIR__ . '/../Views/index.php';

        echo View::renderLayout($layout, $view, [
            'rows' => $rows,
            'pageTitle' => 'الوفيات',
            'metaDescription' => Settings::get('seo_default_description', ''),
        ]);
    }

    public function show(array $params): void
    {
        $item = Database::fetchOne(
            'SELECT o.*, c.name AS city_name FROM ' . Database::table('obituaries') . ' o
             LEFT JOIN ' . Database::table('cities') . ' c ON c.id = o.city_id
             WHERE o.id = ?',
            [(int) $params['id']]
        );

        if (!$item) {
            Response::redirect(Url::to('obituaries'));
        }

        $layout = CORE_PATH . '/Front/Views/layouts/main.php';
        $view = __DIR__ . '/../Views/show.php';

        echo View::renderLayout($layout, $view, [
            'item' => $item,
            'pageTitle' => 'وفاة ' . $item['name'],
            'metaDescription' => Settings::get('seo_default_description', ''),
        ]);
    }
}
