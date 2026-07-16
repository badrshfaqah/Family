<?php

namespace Core\Front\Controllers;

use Core\Database;
use Core\Support\Url;

final class SitemapController
{
    public function index(array $params): void
    {
        header('Content-Type: application/xml; charset=utf-8');

        $urls = [Url::full('') => ['priority' => '1.0']];

        $sources = [
            'news' => ['slug' => 'slug', 'where' => "status = 'published'"],
            'events' => ['slug' => 'slug', 'where' => "status = 'published'"],
            'pages' => ['slug' => 'slug', 'where' => "status = 'published'"],
            'archive' => ['slug' => 'slug', 'where' => "status = 'published'"],
        ];

        $routeMap = ['news' => 'news', 'events' => 'events', 'pages' => 'p', 'archive' => 'archive'];

        foreach ($sources as $table => $conf) {
            if (!Database::tableExists($table)) {
                continue;
            }
            $rows = Database::fetchAll("SELECT slug, updated_at FROM " . Database::table($table) . " WHERE {$conf['where']} LIMIT 5000");
            foreach ($rows as $row) {
                $urls[Url::full($routeMap[$table] . '/' . $row['slug'])] = ['priority' => '0.7', 'lastmod' => $row['updated_at'] ?? null];
            }
        }

        echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";
        foreach ($urls as $loc => $meta) {
            echo '<url><loc>' . htmlspecialchars($loc, ENT_XML1) . '</loc>';
            if (!empty($meta['lastmod'])) {
                echo '<lastmod>' . date('c', strtotime($meta['lastmod'])) . '</lastmod>';
            }
            echo '<priority>' . $meta['priority'] . '</priority></url>' . "\n";
        }
        echo '</urlset>';
    }

    public function robots(array $params): void
    {
        header('Content-Type: text/plain; charset=utf-8');
        $base = Url::to('');
        echo "User-agent: *\n";
        echo "Disallow: {$base}admin/\n";
        echo "Disallow: {$base}install/\n";
        echo "Disallow: {$base}storage/logs/\n";
        echo "Disallow: {$base}storage/backups/\n";
        echo "Disallow: {$base}storage/cache/\n";
        echo "Allow: {$base}storage/uploads/\n";
        echo "Sitemap: " . Url::full('sitemap.xml') . "\n";
    }
}
