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
            'news' => ['table' => 'news', 'route' => 'news', 'where' => "status = 'published'"],
            'events' => ['table' => 'events', 'route' => 'events', 'where' => "status = 'published'"],
            'pages' => ['table' => 'pages', 'route' => 'p', 'where' => "status = 'published'"],
            'archive' => ['table' => 'archive_items', 'route' => 'archive', 'where' => "status = 'published'"],
            'gallery' => ['table' => 'gallery_albums', 'route' => 'gallery', 'where' => "status = 'published'"],
        ];

        foreach ($sources as $conf) {
            if (!Database::tableExists($conf['table'])) {
                continue;
            }
            $rows = Database::fetchAll("SELECT slug, updated_at FROM " . Database::table($conf['table']) . " WHERE {$conf['where']} LIMIT 5000");
            foreach ($rows as $row) {
                $urls[Url::full($conf['route'] . '/' . $row['slug'])] = ['priority' => '0.7', 'lastmod' => $row['updated_at'] ?? null];
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
