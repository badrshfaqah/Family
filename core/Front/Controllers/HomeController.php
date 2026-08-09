<?php

namespace Core\Front\Controllers;

use Core\Database;
use Core\ModuleManager;
use Core\Settings;
use Core\View;

final class HomeController
{
    public function index(array $params): void
    {
        $sectionsOrder = Settings::get('home_sections_order', []);
        $sectionsVisible = Settings::get('home_sections_visible', []);
        $itemsCount = (int) Settings::get('home_items_count', 6);

        $data = [
            'sectionsOrder' => is_array($sectionsOrder) ? $sectionsOrder : [],
            'sectionsVisible' => is_array($sectionsVisible) ? $sectionsVisible : [],
            'news' => ModuleManager::isEnabled('news') ? $this->latestNews($itemsCount) : [],
            'nextEvent' => ModuleManager::isEnabled('calendar') ? $this->nextCalendarEntry() : null,
            'comingSoon' => ModuleManager::isEnabled('calendar') ? $this->comingSoon($itemsCount) : [],
            'gatherings' => ModuleManager::isEnabled('gatherings') ? $this->activeGatherings($itemsCount) : [],
            'announcements' => ModuleManager::isEnabled('announcements') ? $this->activeAnnouncements() : [],
            'gallery' => ModuleManager::isEnabled('gallery') ? $this->galleryPicks(8) : [],
            'stats' => $this->stats(),
        ];

        $layout = CORE_PATH . '/Front/Views/layouts/main.php';
        $view = ROOT_PATH . '/themes/default/templates/home.php';

        echo View::renderLayout($layout, $view, $data);
    }

    private function latestNews(int $limit): array
    {
        if (!Database::tableExists('news')) {
            return [];
        }
        return Database::fetchAll(
            'SELECT id, title, slug, excerpt, cover_media_id, published_at, is_featured
             FROM ' . Database::table('news') . "
             WHERE status = 'published' AND published_at <= NOW()
             ORDER BY is_pinned DESC, published_at DESC LIMIT {$limit}"
        );
    }

    private function nextCalendarEntry(): ?array
    {
        if (!Database::tableExists('calendar_entries')) {
            return null;
        }
        return Database::fetchOne(
            'SELECT ce.*, c.name AS city_name FROM ' . Database::table('calendar_entries') . ' ce
             LEFT JOIN ' . Database::table('cities') . ' c ON c.id = ce.city_id
             WHERE ce.entry_datetime >= NOW() AND ce.status = "published"
             ORDER BY ce.entry_datetime ASC LIMIT 1'
        );
    }

    private function comingSoon(int $limit): array
    {
        if (!Database::tableExists('calendar_entries')) {
            return [];
        }
        return Database::fetchAll(
            'SELECT ce.*, c.name AS city_name FROM ' . Database::table('calendar_entries') . ' ce
             LEFT JOIN ' . Database::table('cities') . ' c ON c.id = ce.city_id
             WHERE ce.entry_datetime >= NOW() AND ce.status = "published"
             ORDER BY ce.entry_datetime ASC LIMIT ' . (int) $limit
        );
    }

    private function activeGatherings(int $limit): array
    {
        if (!Database::tableExists('gatherings')) {
            return [];
        }
        return Database::fetchAll(
            'SELECT g.*, c.name AS city_name FROM ' . Database::table('gatherings') . ' g
             LEFT JOIN ' . Database::table('cities') . ' c ON c.id = g.city_id
             WHERE g.status = "active" ORDER BY g.sort_order ASC, g.id DESC LIMIT ' . (int) $limit
        );
    }

    private function activeAnnouncements(): array
    {
        if (!Database::tableExists('announcements')) {
            return [];
        }
        return Database::fetchAll(
            'SELECT * FROM ' . Database::table('announcements') . '
             WHERE status = "active" AND placement IN ("home_card")
             AND (starts_at IS NULL OR starts_at <= NOW())
             AND (ends_at IS NULL OR ends_at >= NOW())
             ORDER BY id DESC LIMIT 5'
        );
    }

    private function galleryPicks(int $limit): array
    {
        if (!Database::tableExists('gallery_photos')) {
            return [];
        }
        return Database::fetchAll(
            'SELECT gp.*, m.stored_path, m.thumb_path FROM ' . Database::table('gallery_photos') . ' gp
             JOIN ' . Database::table('media') . ' m ON m.id = gp.media_id
             ORDER BY gp.id DESC LIMIT ' . (int) $limit
        );
    }

    private function stats(): array
    {
        $stats = [];
        $map = [
            'news' => ['table' => 'news', 'where' => "status = 'published'"],
            'events' => ['table' => 'events', 'where' => "status = 'published'"],
            'gatherings' => ['table' => 'gatherings', 'where' => "status = 'active'"],
            'gallery' => ['table' => 'gallery_photos', 'where' => '1=1'],
            'archive' => ['table' => 'archive_items', 'where' => "status = 'published'"],
            'directory_count' => ['table' => 'directory_contacts', 'where' => "status = 'active'"],
            'push_subscribers' => ['table' => 'push_subscriptions', 'where' => '1=1'],
        ];

        foreach ($map as $key => $conf) {
            if (!Database::tableExists($conf['table'])) {
                continue;
            }
            $stats[$key] = (int) Database::fetchValue(
                'SELECT COUNT(*) FROM ' . Database::table($conf['table']) . ' WHERE ' . $conf['where']
            );
        }

        return $stats;
    }
}
