<?php

namespace Core\Front\Controllers;

use Core\Database;
use Core\Support\Request;
use Core\View;

final class SearchController
{
    public function index(array $params): void
    {
        $q = trim((string) Request::get('q', ''));
        $type = trim((string) Request::get('type', ''));
        $results = [];

        if (mb_strlen($q) >= 2) {
            $results = $this->search($q, $type);
        }

        $layout = CORE_PATH . '/Front/Views/layouts/main.php';
        $view = ROOT_PATH . '/themes/default/templates/search.php';

        echo View::renderLayout($layout, $view, ['q' => $q, 'type' => $type, 'results' => $results]);
    }

    private function search(string $q, string $type): array
    {
        $like = '%' . $q . '%';
        $results = [];

        $sources = [
            'news' => ['table' => 'news', 'title' => 'title', 'slug' => 'slug', 'where' => "status = 'published'", 'route' => 'news'],
            'events' => ['table' => 'events', 'title' => 'title', 'slug' => 'slug', 'where' => "status = 'published'", 'route' => 'events'],
            'pages' => ['table' => 'pages', 'title' => 'title', 'slug' => 'slug', 'where' => "status = 'published'", 'route' => 'p'],
            'archive' => ['table' => 'archive_items', 'title' => 'title', 'slug' => 'slug', 'where' => "status = 'published'", 'route' => 'archive'],
        ];

        foreach ($sources as $key => $conf) {
            if ($type !== '' && $type !== $key) {
                continue;
            }
            if (!Database::tableExists($conf['table'])) {
                continue;
            }
            $rows = Database::fetchAll(
                "SELECT id, {$conf['title']} AS title, {$conf['slug']} AS slug FROM " . Database::table($conf['table']) . "
                 WHERE ({$conf['where']}) AND {$conf['title']} LIKE ?
                 ORDER BY id DESC LIMIT 20",
                [$like]
            );
            foreach ($rows as $row) {
                $row['type'] = $key;
                $row['route'] = $conf['route'];
                $results[] = $row;
            }
        }

        return $results;
    }
}
