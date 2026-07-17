<?php

namespace Core\Front\Controllers;

use Core\Database;
use Core\Settings;
use Core\Terms;
use Core\View;

final class BranchFrontController
{
    public function index(array $params): void
    {
        $branches = Database::fetchAll(
            'SELECT * FROM ' . Database::table('branches') . " WHERE status = 'active' ORDER BY sort_order ASC, id ASC"
        );

        $layout = CORE_PATH . '/Front/Views/layouts/main.php';
        $view = CORE_PATH . '/Front/Views/branches/index.php';

        echo View::renderLayout($layout, $view, [
            'branches' => $branches,
            'pageTitle' => Terms::phrase('branches'),
            'metaDescription' => Settings::get('seo_default_description', ''),
        ]);
    }

    public function show(array $params): void
    {
        $id = (int) ($params['id'] ?? 0);

        $branch = Database::fetchOne(
            'SELECT * FROM ' . Database::table('branches') . " WHERE id = ? AND status = 'active' AND show_public_page = 1",
            [$id]
        );

        if (!$branch) {
            \Core\Front\NotFound::render();
            return;
        }

        $layout = CORE_PATH . '/Front/Views/layouts/main.php';
        $view = CORE_PATH . '/Front/Views/branches/show.php';

        echo View::renderLayout($layout, $view, [
            'branch' => $branch,
            'pageTitle' => $branch['name'],
            'metaDescription' => $branch['description'] ?: Settings::get('seo_default_description', ''),
        ]);
    }
}
