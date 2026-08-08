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
            'metaDescription' => 'شعراء العائلة وقصائدهم — دواوين موثقة بالعامية الأصيلة: وجدانيات وقصائد مناسبات ورثاء.',
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


        // توحيد الرابط على صيغته الوصفية بتحويل دائم (301) لمنع ازدواجية الفهرسة
        $slugPart = \Core\Support\Str::slug('الشاعر ' . $poet['name'], '');
        $expected = '/poetry/' . (int) $poet['id'] . ($slugPart !== '' ? '-' . $slugPart : '');
        if (\Core\Support\Request::path() !== $expected) {
            header('Location: ' . \Core\Support\Url::full(ltrim($expected, '/')), true, 301);
            exit;
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
            'metaDescription' => mb_substr(($poet['bio'] ?: 'قصائد الشاعر ' . $poet['name']) . ' — ' . count($poems) . ' قصيدة.', 0, 160),
            'jsonLd' => [
                '@type' => 'Person',
                'name' => $poet['name'],
                'description' => $poet['bio'] ?: null,
                'jobTitle' => 'شاعر',
            ],
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


        // توحيد الرابط على صيغته الوصفية بتحويل دائم (301) لمنع ازدواجية الفهرسة
        $slugPart = \Core\Support\Str::slug($poem['title'], '');
        $expected = '/poems/' . (int) $poem['id'] . ($slugPart !== '' ? '-' . $slugPart : '');
        if (\Core\Support\Request::path() !== $expected) {
            header('Location: ' . \Core\Support\Url::full(ltrim($expected, '/')), true, 301);
            exit;
        }

        $firstLine = trim((string) strtok((string) $poem['content'], "\n"));
        echo View::renderLayout($layout, $view, [
            'poem' => $poem,
            'pageTitle' => $poem['title'] . ' — ' . $poem['poet_name'],
            'metaDescription' => mb_substr('قصيدة ' . $poem['title'] . ' للشاعر ' . $poem['poet_name'] . ': ' . $firstLine, 0, 160),
        ]);
    }
}
