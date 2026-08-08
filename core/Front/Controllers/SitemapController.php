<?php

namespace Core\Front\Controllers;

use Core\Database;
use Core\ModuleManager;
use Core\Support\Url;

final class SitemapController
{
    public function index(array $params): void
    {
        header('Content-Type: application/xml; charset=utf-8');

        $urls = [Url::full('') => ['priority' => '1.0']];

        // صفحات الأقسام الرئيسية (للإضافات المفعلة فقط)
        $sections = [
            'calendar' => 'calendar', 'gatherings' => 'gatherings', 'news' => 'news',
            'gallery' => 'gallery', 'archive' => 'archive', 'poetry' => 'poetry',
            'obituaries' => 'obituaries', 'family-tree' => 'tree',
        ];
        foreach ($sections as $module => $route) {
            if (ModuleManager::isEnabled($module)) {
                $urls[Url::full($route)] = ['priority' => '0.8'];
            }
        }
        $urls[Url::full('install-app')] = ['priority' => '0.5'];
        $urls[Url::full('family')] = ['priority' => '0.5'];

        // المحتوى التفصيلي: [الإضافة، الجدول، المسار، الشرط، حقل الرابط، حقل العنوان الوصفي، بادئة العنوان]
        $sources = [
            ['news', 'news', 'news', "status = 'published'", 'slug', null, ''],
            ['events', 'events', 'events', "status = 'published'", 'slug', null, ''],
            ['pages', 'pages', 'p', "status = 'published'", 'slug', null, ''],
            ['archive', 'archive_items', 'archive', "status = 'published'", 'slug', null, ''],
            ['gallery', 'gallery_albums', 'gallery', "status = 'published'", 'slug', null, ''],
            ['calendar', 'calendar_entries', 'calendar', "status = 'published'", 'id', 'title', ''],
            ['obituaries', 'obituaries', 'obituaries', "status = 'active'", 'id', 'name', 'وفاة '],
            ['poetry', 'poets', 'poetry', "status = 'active'", 'id', 'name', 'الشاعر '],
            ['poetry', 'poems', 'poems', "status = 'published'", 'id', 'title', ''],
        ];

        foreach ($sources as [$module, $table, $route, $where, $key, $titleCol, $titlePrefix]) {
            if (!ModuleManager::isEnabled($module) || !Database::tableExists($table)) {
                continue;
            }
            $hasUpdated = (bool) Database::fetchOne('SHOW COLUMNS FROM ' . Database::table($table) . " LIKE 'updated_at'");
            $select = $key . ($titleCol ? ', ' . $titleCol : '') . ($hasUpdated ? ', updated_at' : '');
            $rows = Database::fetchAll("SELECT {$select} FROM " . Database::table($table) . " WHERE {$where} LIMIT 5000");
            foreach ($rows as $row) {
                // المسارات الرقمية تُنشر بصيغتها الوصفية (معرف-عنوان) لتطابق روابط الموقع
                $loc = $titleCol
                    ? Url::prettyFull($route, (int) $row[$key], $titlePrefix . $row[$titleCol])
                    : Url::full($route . '/' . $row[$key]);
                $urls[$loc] = [
                    'priority' => '0.7',
                    'lastmod' => $row['updated_at'] ?? null,
                ];
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
        echo "Disallow: {$base}search\n";
        echo "Disallow: {$base}push/\n";
        echo "Disallow: {$base}storage/logs/\n";
        echo "Disallow: {$base}storage/backups/\n";
        echo "Disallow: {$base}storage/cache/\n";
        echo "Allow: {$base}storage/uploads/\n";
        echo "Sitemap: " . Url::full('sitemap.xml') . "\n";
    }
}
