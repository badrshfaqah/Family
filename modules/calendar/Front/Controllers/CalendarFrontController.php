<?php

namespace Modules\Calendar\Front\Controllers;

use Core\Database;
use Core\Settings;
use Core\Support\Request;
use Core\View;

final class CalendarFrontController
{
    public function index(array $params): void
    {
        $view = Request::get('view', 'list') === 'month' ? 'month' : 'list';
        $type = trim((string) Request::get('type', ''));
        $cityIds = array_filter(array_map('intval', (array) Request::get('city', [])));
        $when = Request::get('when', 'upcoming') === 'past' ? 'past' : 'upcoming';

        $where = ['ce.status = "published"'];
        $sqlParams = [];

        if ($type !== '') {
            $where[] = 'ce.entry_type = ?';
            $sqlParams[] = $type;
        }
        if ($cityIds) {
            $placeholders = implode(',', array_fill(0, count($cityIds), '?'));
            $where[] = "ce.city_id IN ($placeholders)";
            foreach ($cityIds as $cid) {
                $sqlParams[] = $cid;
            }
        }

        $month = null;
        if ($view === 'month') {
            $monthParam = Request::get('month', date('Y-m'));
            $ts = strtotime($monthParam . '-01');
            if (!$ts) {
                $ts = time();
            }
            $monthStart = date('Y-m-01 00:00:00', $ts);
            $monthEnd = date('Y-m-t 23:59:59', $ts);
            $where[] = 'ce.entry_datetime BETWEEN ? AND ?';
            $sqlParams[] = $monthStart;
            $sqlParams[] = $monthEnd;
            $month = [
                'key' => date('Y-m', $ts),
                'label' => date('Y-m', $ts),
                'prev' => date('Y-m', strtotime('-1 month', $ts)),
                'next' => date('Y-m', strtotime('+1 month', $ts)),
                'days_in_month' => (int) date('t', $ts),
                'first_weekday' => (int) date('w', strtotime(date('Y-m-01', $ts))),
                'start_ts' => strtotime($monthStart),
            ];
            $orderSql = 'ce.entry_datetime ASC';
        } else {
            if ($when === 'upcoming') {
                $where[] = 'ce.entry_datetime >= NOW()';
                $orderSql = 'ce.entry_datetime ASC';
            } else {
                $where[] = 'ce.entry_datetime < NOW()';
                $orderSql = 'ce.entry_datetime DESC';
            }
        }

        $whereSql = implode(' AND ', $where);
        $rows = Database::fetchAll(
            'SELECT ce.*, c.name AS city_name FROM ' . Database::table('calendar_entries') . ' ce
             LEFT JOIN ' . Database::table('cities') . ' c ON c.id = ce.city_id
             WHERE ' . $whereSql . '
             ORDER BY ' . $orderSql . '
             LIMIT 200',
            $sqlParams
        );

        // المناسبات التي لها تغطية (ألبوم مرتبط) لعرض شارة 📸 في القائمة
        $coverageMap = [];
        if ($rows && \Core\ModuleManager::isEnabled('gallery') && Database::tableExists('gallery_albums')) {
            $ids = array_map(fn($r) => (int) $r['id'], $rows);
            $placeholders = implode(',', array_fill(0, count($ids), '?'));
            $covered = Database::fetchAll(
                'SELECT DISTINCT related_calendar_id FROM ' . Database::table('gallery_albums') . "
                 WHERE status = 'published' AND related_calendar_id IN ({$placeholders})",
                $ids
            );
            foreach ($covered as $c) {
                $coverageMap[(int) $c['related_calendar_id']] = true;
            }
        }

        $types = Database::fetchAll(
            'SELECT DISTINCT entry_type FROM ' . Database::table('calendar_entries') . ' WHERE status = "published" ORDER BY entry_type ASC'
        );
        $cities = Database::fetchAll('SELECT * FROM ' . Database::table('cities') . ' WHERE status = "active" ORDER BY sort_order ASC, id ASC');

        $layout = CORE_PATH . '/Front/Views/layouts/main.php';
        $view_ = __DIR__ . '/../Views/index.php';

        echo View::renderLayout($layout, $view_, [
            'rows' => $rows,
            'coverageMap' => $coverageMap,
            'types' => array_column($types, 'entry_type'),
            'cities' => $cities,
            'selectedType' => $type,
            'selectedCityIds' => $cityIds,
            'viewMode' => $view,
            'when' => $when,
            'month' => $month,
            'pageTitle' => 'رزنامة المناسبات',
            'metaDescription' => Settings::get('seo_default_description', ''),
        ]);
    }

    public function show(array $params): void
    {
        $id = (int) ($params['id'] ?? 0);

        $item = Database::fetchOne(
            'SELECT ce.*, c.name AS city_name FROM ' . Database::table('calendar_entries') . ' ce
             LEFT JOIN ' . Database::table('cities') . ' c ON c.id = ce.city_id
             WHERE ce.id = ? AND ce.status = "published"',
            [$id]
        );

        if (!$item) {
            \Core\Front\NotFound::render();
            return;
        }

        // ألبومات التغطية المرتبطة بالمناسبة (صور وفيديو من المعرض)
        $coverageAlbums = [];
        if (\Core\ModuleManager::isEnabled('gallery') && Database::tableExists('gallery_albums')) {
            $coverageAlbums = Database::fetchAll(
                'SELECT a.id, a.title, a.slug, a.video_url, a.cover_media_id, m.stored_path AS cover_path,
                        (SELECT COUNT(*) FROM ' . Database::table('gallery_photos') . ' gp WHERE gp.album_id = a.id) AS photos_count
                 FROM ' . Database::table('gallery_albums') . ' a
                 LEFT JOIN ' . Database::table('media') . " m ON m.id = a.cover_media_id
                 WHERE a.related_calendar_id = ? AND a.status = 'published'
                 ORDER BY a.id DESC",
                [(int) $params['id']]
            );
        }

        $linkedEvent = null;
        if (!empty($item['event_id']) && \Core\ModuleManager::isEnabled('events') && Database::tableExists('events')) {
            $linkedEvent = Database::fetchOne(
                'SELECT id, title, slug, status FROM ' . Database::table('events') . ' WHERE id = ? AND status = "published"',
                [$item['event_id']]
            );
        }

        $layout = CORE_PATH . '/Front/Views/layouts/main.php';
        $view = __DIR__ . '/../Views/show.php';

        // وصف ديناميكي وبيانات Event المنظمة لنتائج بحث غنية
        $descParts = ['مناسبة ' . $item['entry_type'] . ': ' . $item['title']];
        $descParts[] = 'بتاريخ ' . date('Y/m/d', strtotime($item['entry_datetime']));
        if (!empty($item['venue_name'])) {
            $descParts[] = 'في ' . $item['venue_name'];
        }
        if (!empty($item['city_name'])) {
            $descParts[] = $item['city_name'];
        }
        $metaDescription = mb_substr(implode(' — ', $descParts), 0, 160);

        $eventLd = array_filter([
            '@type' => 'Event',
            'name' => $item['title'],
            'startDate' => date('c', strtotime($item['entry_datetime'])),
            'eventStatus' => 'https://schema.org/EventScheduled',
            'eventAttendanceMode' => 'https://schema.org/OfflineEventAttendanceMode',
            'description' => $metaDescription,
            'location' => !empty($item['venue_name']) ? [
                '@type' => 'Place',
                'name' => $item['venue_name'],
                'address' => $item['city_name'] ?? '',
            ] : null,
        ]);

        echo View::renderLayout($layout, $view, [
            'item' => $item,
            'coverageAlbums' => $coverageAlbums,
            'linkedEvent' => $linkedEvent,
            'pageTitle' => $item['title'],
            'metaDescription' => $metaDescription,
            'jsonLd' => $eventLd,
        ]);
    }
}
