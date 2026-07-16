<?php

namespace Modules\Pages\Front\Controllers;

use Core\Database;
use Core\Settings;
use Core\View;

final class PageFrontController
{
    public function show(array $params): void
    {
        $slug = $params['slug'] ?? '';

        $page = Database::fetchOne(
            'SELECT * FROM ' . Database::table('pages') . " WHERE slug = ? AND status = 'published'",
            [$slug]
        );

        if (!$page) {
            \Core\Front\NotFound::render();
            return;
        }

        $layout = CORE_PATH . '/Front/Views/layouts/main.php';
        $view = __DIR__ . '/../Views/show.php';

        echo View::renderLayout($layout, $view, [
            'page' => $page,
            'pageTitle' => $page['seo_title'] ?: $page['title'],
            'metaDescription' => $page['seo_description'] ?: Settings::get('seo_default_description', ''),
        ]);
    }
}
