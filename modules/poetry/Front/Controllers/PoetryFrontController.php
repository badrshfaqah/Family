<?php

namespace Modules\Poetry\Front\Controllers;

use Core\Database;
use Core\Settings;
use Core\Support\Response;
use Core\Support\Url;
use Core\View;

final class PoetryFrontController
{
    public function index(array $params): void
    {
        $poets = Database::fetchAll(
            'SELECT p.*, m.stored_path AS photo_path,
                    (SELECT COUNT(*) FROM ' . Database::table('poems') . " po WHERE po.poet_id = p.id AND po.status = 'published') AS poems_count
             FROM " . Database::table('poets') . ' p
             LEFT JOIN ' . Database::table('media') . " m ON m.id = p.photo_media_id
             WHERE p.status = 'active'
             ORDER BY p.sort_order ASC, p.name ASC"
        );

        $layout = CORE_PATH . '/Front/Views/layouts/main.php';
        $view = __DIR__ . '/../Views/index.php';

        echo View::renderLayout($layout, $view, [
            'poets' => $poets,
            'pageTitle' => 'سماء الشعراء',
            'metaDescription' => Settings::get('seo_default_description', ''),
        ]);
    }

    public function poet(array $params): void
    {
        $poet = Database::fetchOne(
            'SELECT p.*, m.stored_path AS photo_path FROM ' . Database::table('poets') . ' p
             LEFT JOIN ' . Database::table('media') . " m ON m.id = p.photo_media_id
             WHERE p.id = ? AND p.status = 'active'",
            [(int) $params['id']]
        );

        if (!$poet) {
            Response::redirect(Url::to('poetry'));
        }

        $poems = Database::fetchAll(
            'SELECT id, title, occasion, content FROM ' . Database::table('poems') . "
             WHERE poet_id = ? AND status = 'published'
             ORDER BY sort_order ASC, id DESC",
            [$poet['id']]
        );

        $layout = CORE_PATH . '/Front/Views/layouts/main.php';
        $view = __DIR__ . '/../Views/poet.php';

        echo View::renderLayout($layout, $view, [
            'poet' => $poet,
            'poems' => $poems,
            'pageTitle' => 'الشاعر ' . $poet['name'],
            'metaDescription' => Settings::get('seo_default_description', ''),
        ]);
    }

    public function poem(array $params): void
    {
        $poem = Database::fetchOne(
            'SELECT po.*, p.name AS poet_name, p.id AS poet_ref FROM ' . Database::table('poems') . ' po
             JOIN ' . Database::table('poets') . " p ON p.id = po.poet_id
             WHERE po.id = ? AND po.status = 'published' AND p.status = 'active'",
            [(int) $params['id']]
        );

        if (!$poem) {
            Response::redirect(Url::to('poetry'));
        }

        $layout = CORE_PATH . '/Front/Views/layouts/main.php';
        $view = __DIR__ . '/../Views/poem.php';

        echo View::renderLayout($layout, $view, [
            'poem' => $poem,
            'pageTitle' => $poem['title'] . ' — ' . $poem['poet_name'],
            'metaDescription' => Settings::get('seo_default_description', ''),
        ]);
    }
}
