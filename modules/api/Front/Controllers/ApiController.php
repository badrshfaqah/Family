<?php

namespace Modules\Api\Front\Controllers;

use Core\Database;
use Core\Media;
use Core\ModuleManager;
use Core\Settings;
use Core\Support\Request;
use Core\Support\Response;
use Core\Support\Url;

/**
 * واجهة JSON عامة للقراءة فقط لتطبيق الجوال.
 * تعرض المحتوى المنشور العام فقط — لا تلمس جوال العائلة أو القائمة البريدية أو أي بيانات إدارية.
 */
final class ApiController
{
    private function ok(array $data, int $cacheSeconds = 300): void
    {
        header('Access-Control-Allow-Origin: *');
        header('Cache-Control: public, max-age=' . $cacheSeconds);
        Response::json(['ok' => true, 'data' => $data]);
    }

    private function fail(string $message, int $status = 404): void
    {
        header('Access-Control-Allow-Origin: *');
        Response::json(['ok' => false, 'error' => $message], $status);
    }

    /** يجلب روابط الوسائط المطلقة لمجموعة معرفات دفعة واحدة: id => ['url','thumb'] */
    private function mediaMap(array $ids): array
    {
        $ids = array_values(array_unique(array_filter(array_map('intval', $ids))));
        if (!$ids) {
            return [];
        }
        $origin = Url::origin();
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $rows = Database::fetchAll(
            'SELECT id, stored_path, thumb_path FROM ' . Database::table('media') . " WHERE id IN ({$placeholders})",
            $ids
        );
        $map = [];
        foreach ($rows as $row) {
            $map[(int) $row['id']] = [
                'url' => $origin . Media::url($row['stored_path']),
                'thumb' => $origin . Media::thumbUrl($row['thumb_path'], $row['stored_path']),
            ];
        }
        return $map;
    }

    private function media(?int $id, array $map): ?array
    {
        return $id && isset($map[$id]) ? $map[$id] : null;
    }

    /** يحول حقل معرفات مفصولة بفواصل "1,2,3" إلى قائمة وسائط */
    private function mediaList(?string $csv, array $map): array
    {
        if (!$csv) {
            return [];
        }
        $out = [];
        foreach (explode(',', $csv) as $id) {
            $m = $this->media((int) trim($id), $map);
            if ($m) {
                $out[] = $m;
            }
        }
        return $out;
    }

    /** يجمع كل معرفات الوسائط من صفوف متعددة (أعمدة مفردة + أعمدة CSV) */
    private function collectMediaIds(array $rows, array $singleCols, array $csvCols = []): array
    {
        $ids = [];
        foreach ($rows as $row) {
            foreach ($singleCols as $col) {
                if (!empty($row[$col])) {
                    $ids[] = (int) $row[$col];
                }
            }
            foreach ($csvCols as $col) {
                if (!empty($row[$col])) {
                    foreach (explode(',', $row[$col]) as $id) {
                        $ids[] = (int) trim($id);
                    }
                }
            }
        }
        return $ids;
    }

    private function enabled(string $slug, string $table): bool
    {
        return ModuleManager::isEnabled($slug) && Database::tableExists($table);
    }

    // ---------------------------------------------------------------- /app

    public function app(array $params): void
    {
        $logoUrl = '';
        $logoId = (int) Settings::get('identity_logo_media_id', 0);
        if ($logoId) {
            $logo = Database::fetchOne('SELECT stored_path FROM ' . Database::table('media') . ' WHERE id = ?', [$logoId]);
            if ($logo) {
                $logoUrl = Url::origin() . Media::url($logo['stored_path']);
            }
        }

        $modules = [];
        foreach (['news', 'calendar', 'gatherings', 'gallery', 'obituaries', 'poetry', 'archive', 'family-tree', 'pages', 'announcements', 'events'] as $slug) {
            if (ModuleManager::isEnabled($slug)) {
                $modules[] = $slug;
            }
        }

        $cities = Database::tableExists('cities') ? Database::fetchAll(
            'SELECT id, name FROM ' . Database::table('cities') . " WHERE status = 'active' ORDER BY sort_order ASC, name ASC"
        ) : [];

        $this->ok([
            'official_name' => (string) Settings::get('identity_official_name', ''),
            'short_name' => (string) (Settings::get('identity_short_name', '') ?: Settings::get('identity_official_name', '')),
            'brief' => (string) Settings::get('identity_brief', ''),
            'logo_url' => $logoUrl,
            'primary_color' => (string) Settings::get('theme_color_primary', '#0f6e5e'),
            'secondary_color' => (string) Settings::get('theme_color_secondary', '#c9a24b'),
            'origin' => Url::origin(),
            'modules' => $modules,
            'cities' => array_map(fn($c) => ['id' => (int) $c['id'], 'name' => $c['name']], $cities),
        ], 3600);
    }

    // ---------------------------------------------------------------- /home

    public function home(array $params): void
    {
        $data = [
            'announcements' => [],
            'obituaries_urgent' => [],
            'next_entry' => null,
            'coming_soon' => [],
            'news' => [],
            'gatherings' => [],
            'gallery' => [],
        ];

        if ($this->enabled('obituaries', 'obituaries')) {
            $rows = Database::fetchAll(
                'SELECT o.*, c.name AS city_name FROM ' . Database::table('obituaries') . ' o
                 LEFT JOIN ' . Database::table('cities') . ' c ON c.id = o.city_id
                 WHERE o.status = "active"
                 ORDER BY o.deceased_on DESC, o.id DESC LIMIT 5'
            );
            $data['obituaries_urgent'] = array_map(fn($r) => $this->obituaryJson($r), $rows);
        }

        if ($this->enabled('announcements', 'announcements')) {
            $rows = Database::fetchAll(
                'SELECT id, title, message, announcement_type FROM ' . Database::table('announcements') . '
                 WHERE status = "active" AND placement = "home_card"
                 AND (starts_at IS NULL OR starts_at <= NOW())
                 AND (ends_at IS NULL OR ends_at >= NOW())
                 ORDER BY id DESC LIMIT 5'
            );
            $data['announcements'] = array_map(fn($r) => [
                'id' => (int) $r['id'],
                'title' => $r['title'],
                'message' => $r['message'],
                'type' => $r['announcement_type'],
            ], $rows);
        }

        if ($this->enabled('calendar', 'calendar_entries')) {
            $entries = Database::fetchAll(
                'SELECT ce.*, c.name AS city_name FROM ' . Database::table('calendar_entries') . ' ce
                 LEFT JOIN ' . Database::table('cities') . ' c ON c.id = ce.city_id
                 WHERE ce.entry_datetime >= CURDATE() AND ce.status = "published"
                 ORDER BY ce.entry_datetime ASC LIMIT 6'
            );
            $map = $this->mediaMap($this->collectMediaIds($entries, ['cover_media_id', 'person_media_id']));
            $entries = array_map(fn($r) => $this->calendarEntryJson($r, $map), $entries);
            $data['next_entry'] = $entries[0] ?? null;
            $data['coming_soon'] = $entries;
        }

        if ($this->enabled('news', 'news')) {
            $rows = Database::fetchAll(
                'SELECT id, title, slug, excerpt, cover_media_id, published_at, is_featured, is_pinned
                 FROM ' . Database::table('news') . "
                 WHERE status = 'published' AND published_at <= NOW()
                 ORDER BY is_pinned DESC, published_at DESC LIMIT 6"
            );
            $map = $this->mediaMap($this->collectMediaIds($rows, ['cover_media_id']));
            $data['news'] = array_map(fn($r) => $this->newsCardJson($r, $map), $rows);
        }

        if ($this->enabled('gatherings', 'gatherings')) {
            $rows = Database::fetchAll(
                'SELECT g.*, c.name AS city_name FROM ' . Database::table('gatherings') . ' g
                 LEFT JOIN ' . Database::table('cities') . ' c ON c.id = g.city_id
                 WHERE g.status = "active" ORDER BY RAND() LIMIT 6'
            );
            $map = $this->mediaMap($this->collectMediaIds($rows, ['cover_media_id']));
            $data['gatherings'] = array_map(fn($r) => $this->gatheringJson($r, $map), $rows);
        }

        if ($this->enabled('gallery', 'gallery_photos')) {
            $rows = Database::fetchAll(
                'SELECT p.id, p.media_id, p.caption, a.title AS album_title, a.slug AS album_slug
                 FROM ' . Database::table('gallery_photos') . ' p
                 JOIN ' . Database::table('gallery_albums') . ' a ON a.id = p.album_id
                 WHERE a.status = "published" AND a.album_type = "photo"
                 ORDER BY p.id DESC LIMIT 8'
            );
            $map = $this->mediaMap($this->collectMediaIds($rows, ['media_id']));
            $data['gallery'] = array_values(array_filter(array_map(function ($r) use ($map) {
                $m = $this->media((int) $r['media_id'], $map);
                return $m ? [
                    'id' => (int) $r['id'],
                    'caption' => $r['caption'],
                    'album_title' => $r['album_title'],
                    'album_slug' => $r['album_slug'],
                    'image' => $m,
                ] : null;
            }, $rows)));
        }

        $this->ok($data, 120);
    }

    // ---------------------------------------------------------------- الأخبار

    private function newsCardJson(array $r, array $map): array
    {
        return [
            'id' => (int) $r['id'],
            'title' => $r['title'],
            'slug' => $r['slug'],
            'excerpt' => $r['excerpt'],
            'category' => $r['category_name'] ?? null,
            'published_at' => $r['published_at'],
            'is_pinned' => (bool) ($r['is_pinned'] ?? false),
            'cover' => $this->media((int) ($r['cover_media_id'] ?? 0), $map),
        ];
    }

    public function newsIndex(array $params): void
    {
        if (!$this->enabled('news', 'news')) {
            $this->fail('الأخبار غير مفعّلة');
            return;
        }
        $page = max(1, Request::int('page', 1));
        $perPage = min(50, max(1, Request::int('per_page', 12)));
        $offset = ($page - 1) * $perPage;

        $categoryId = Request::int('category', 0);
        $where = "n.status = 'published' AND n.published_at <= NOW()";
        $bind = [];
        if ($categoryId > 0) {
            $where .= ' AND n.category_id = ?';
            $bind[] = $categoryId;
        }

        $total = (int) Database::fetchValue(
            'SELECT COUNT(*) FROM ' . Database::table('news') . ' n WHERE ' . $where,
            $bind
        );
        $rows = Database::fetchAll(
            'SELECT n.id, n.title, n.slug, n.excerpt, n.cover_media_id, n.published_at, n.is_pinned, c.name AS category_name
             FROM ' . Database::table('news') . ' n
             LEFT JOIN ' . Database::table('news_categories') . ' c ON c.id = n.category_id
             WHERE ' . $where . '
             ORDER BY n.is_pinned DESC, n.published_at DESC
             LIMIT ' . $perPage . ' OFFSET ' . $offset,
            $bind
        );
        $map = $this->mediaMap($this->collectMediaIds($rows, ['cover_media_id']));

        $categories = Database::tableExists('news_categories') ? Database::fetchAll(
            'SELECT id, name FROM ' . Database::table('news_categories') . ' ORDER BY sort_order ASC, name ASC'
        ) : [];

        $this->ok([
            'items' => array_map(fn($r) => $this->newsCardJson($r, $map), $rows),
            'categories' => array_map(fn($c) => ['id' => (int) $c['id'], 'name' => $c['name']], $categories),
            'page' => $page,
            'per_page' => $perPage,
            'total' => $total,
        ]);
    }

    public function newsShow(array $params): void
    {
        if (!$this->enabled('news', 'news')) {
            $this->fail('الأخبار غير مفعّلة');
            return;
        }
        $item = Database::fetchOne(
            'SELECT n.*, c.name AS category_name FROM ' . Database::table('news') . ' n
             LEFT JOIN ' . Database::table('news_categories') . ' c ON c.id = n.category_id
             WHERE n.slug = ? AND n.status = "published" AND n.published_at <= NOW()',
            [$params['slug'] ?? '']
        );
        if (!$item) {
            $this->fail('الخبر غير موجود');
            return;
        }

        $map = $this->mediaMap($this->collectMediaIds(
            [$item],
            ['cover_media_id'],
            ['gallery_media_ids', 'attachment_media_ids']
        ));

        $related = Database::fetchAll(
            'SELECT id, title, slug, excerpt, cover_media_id, published_at, is_pinned FROM ' . Database::table('news') . '
             WHERE status = "published" AND published_at <= NOW() AND id != ? ' .
            ($item['category_id'] ? 'AND category_id = ' . (int) $item['category_id'] : '') . '
             ORDER BY published_at DESC LIMIT 3',
            [$item['id']]
        );
        $relatedMap = $this->mediaMap($this->collectMediaIds($related, ['cover_media_id']));

        $this->ok([
            'id' => (int) $item['id'],
            'title' => $item['title'],
            'slug' => $item['slug'],
            'excerpt' => $item['excerpt'],
            'content_html' => $item['content'],
            'category' => $item['category_name'],
            'tags' => $item['tags'] ? array_values(array_filter(array_map('trim', explode(',', $item['tags'])))) : [],
            'author_name' => $item['author_name'],
            'published_at' => $item['published_at'],
            'video_url' => $item['video_url'],
            'cover' => $this->media((int) $item['cover_media_id'], $map),
            'gallery' => $this->mediaList($item['gallery_media_ids'], $map),
            'share_url' => Url::origin() . Url::to('news/' . $item['slug']),
            'related' => array_map(fn($r) => $this->newsCardJson($r, $relatedMap), $related),
        ]);
    }

    // ---------------------------------------------------------------- الرزنامة

    private function calendarEntryJson(array $r, array $map): array
    {
        return [
            'id' => (int) $r['id'],
            'title' => $r['title'],
            'type' => $r['entry_type'],
            'datetime' => $r['entry_datetime'],
            'city' => $r['city_name'] ?? null,
            'venue' => $r['venue_name'],
            'maps_url' => $r['maps_url'],
            'notes' => $r['notes'],
            'cover' => $this->media((int) ($r['cover_media_id'] ?? 0), $map),
            'person_image' => $this->media((int) ($r['person_media_id'] ?? 0), $map),
        ];
    }

    public function calendar(array $params): void
    {
        if (!$this->enabled('calendar', 'calendar_entries')) {
            $this->fail('الرزنامة غير مفعّلة');
            return;
        }
        $scope = Request::get('scope', 'upcoming') === 'past' ? 'past' : 'upcoming';
        $limit = min(200, max(1, Request::int('limit', 100)));

        $cond = $scope === 'upcoming'
            ? 'ce.entry_datetime >= CURDATE()'
            : 'ce.entry_datetime < CURDATE()';
        $order = $scope === 'upcoming' ? 'ASC' : 'DESC';

        $rows = Database::fetchAll(
            'SELECT ce.*, c.name AS city_name FROM ' . Database::table('calendar_entries') . ' ce
             LEFT JOIN ' . Database::table('cities') . ' c ON c.id = ce.city_id
             WHERE ' . $cond . ' AND ce.status = "published"
             ORDER BY ce.entry_datetime ' . $order . ' LIMIT ' . $limit
        );
        $map = $this->mediaMap($this->collectMediaIds($rows, ['cover_media_id', 'person_media_id']));

        $this->ok([
            'scope' => $scope,
            'items' => array_map(fn($r) => $this->calendarEntryJson($r, $map), $rows),
        ], 120);
    }

    // ---------------------------------------------------------------- الجمعات

    private function gatheringJson(array $r, array $map): array
    {
        return [
            'id' => (int) $r['id'],
            'title' => $r['title'],
            'description' => $r['description'],
            'city' => $r['city_name'] ?? null,
            'city_id' => $r['city_id'] !== null ? (int) $r['city_id'] : null,
            'venue' => $r['venue'],
            'map_url' => $r['map_url'],
            'recurrence_label' => $r['recurrence_label'],
            'time_period' => $r['time_period'],
            'start_time' => $r['start_time'],
            'end_time' => $r['end_time'],
            'contact_name' => $r['contact_name'],
            'cover' => $this->media((int) ($r['cover_media_id'] ?? 0), $map),
        ];
    }

    public function gatherings(array $params): void
    {
        if (!$this->enabled('gatherings', 'gatherings')) {
            $this->fail('الجمعات غير مفعّلة');
            return;
        }
        $cityId = Request::int('city', 0);
        $where = 'g.status = "active"';
        $bind = [];
        if ($cityId > 0) {
            $where .= ' AND g.city_id = ?';
            $bind[] = $cityId;
        }
        $rows = Database::fetchAll(
            'SELECT g.*, c.name AS city_name FROM ' . Database::table('gatherings') . ' g
             LEFT JOIN ' . Database::table('cities') . ' c ON c.id = g.city_id
             WHERE ' . $where . '
             ORDER BY g.sort_order ASC, g.id ASC',
            $bind
        );
        $map = $this->mediaMap($this->collectMediaIds($rows, ['cover_media_id']));

        $cities = Database::fetchAll(
            'SELECT c.id, c.name, COUNT(*) AS cnt FROM ' . Database::table('gatherings') . ' g
             JOIN ' . Database::table('cities') . ' c ON c.id = g.city_id
             WHERE g.status = "active"
             GROUP BY c.id, c.name, c.sort_order
             ORDER BY c.sort_order ASC, c.name ASC'
        );

        $this->ok([
            'items' => array_map(fn($r) => $this->gatheringJson($r, $map), $rows),
            'cities' => array_map(fn($c) => [
                'id' => (int) $c['id'],
                'name' => $c['name'],
                'count' => (int) $c['cnt'],
            ], $cities),
        ]);
    }

    // ---------------------------------------------------------------- المعرض

    public function galleryIndex(array $params): void
    {
        if (!$this->enabled('gallery', 'gallery_albums')) {
            $this->fail('المعرض غير مفعّل');
            return;
        }
        $page = max(1, Request::int('page', 1));
        $perPage = min(50, max(1, Request::int('per_page', 20)));
        $offset = ($page - 1) * $perPage;

        $total = (int) Database::fetchValue(
            'SELECT COUNT(*) FROM ' . Database::table('gallery_albums') . " WHERE status = 'published'"
        );
        $rows = Database::fetchAll(
            'SELECT a.*, c.name AS city_name,
                    (SELECT COUNT(*) FROM ' . Database::table('gallery_photos') . ' p WHERE p.album_id = a.id) AS photos_count
             FROM ' . Database::table('gallery_albums') . ' a
             LEFT JOIN ' . Database::table('cities') . ' c ON c.id = a.city_id
             WHERE a.status = "published"
             ORDER BY a.sort_order ASC, a.id DESC
             LIMIT ' . $perPage . ' OFFSET ' . $offset
        );

        // غلاف الألبوم: الغلاف المحدد أو أول صورة فيه
        $coverIds = [];
        foreach ($rows as &$r) {
            if (empty($r['cover_media_id'])) {
                $first = Database::fetchValue(
                    'SELECT media_id FROM ' . Database::table('gallery_photos') . ' WHERE album_id = ? ORDER BY sort_order ASC, id ASC LIMIT 1',
                    [$r['id']]
                );
                $r['cover_media_id'] = $first ?: null;
            }
            if ($r['cover_media_id']) {
                $coverIds[] = (int) $r['cover_media_id'];
            }
        }
        unset($r);
        $map = $this->mediaMap($coverIds);

        $this->ok([
            'items' => array_map(fn($r) => [
                'id' => (int) $r['id'],
                'title' => $r['title'],
                'slug' => $r['slug'],
                'description' => $r['description'],
                'type' => $r['album_type'],
                'video_url' => $r['video_url'],
                'year' => $r['year'] !== null ? (int) $r['year'] : null,
                'city' => $r['city_name'],
                'photos_count' => (int) $r['photos_count'],
                'cover' => $this->media((int) ($r['cover_media_id'] ?? 0), $map),
            ], $rows),
            'page' => $page,
            'per_page' => $perPage,
            'total' => $total,
        ]);
    }

    public function galleryShow(array $params): void
    {
        if (!$this->enabled('gallery', 'gallery_albums')) {
            $this->fail('المعرض غير مفعّل');
            return;
        }
        $album = Database::fetchOne(
            'SELECT a.*, c.name AS city_name FROM ' . Database::table('gallery_albums') . ' a
             LEFT JOIN ' . Database::table('cities') . ' c ON c.id = a.city_id
             WHERE a.slug = ? AND a.status = "published"',
            [$params['slug'] ?? '']
        );
        if (!$album) {
            $this->fail('الألبوم غير موجود');
            return;
        }
        $photos = Database::fetchAll(
            'SELECT id, media_id, caption FROM ' . Database::table('gallery_photos') . '
             WHERE album_id = ? ORDER BY sort_order ASC, id ASC',
            [$album['id']]
        );
        $map = $this->mediaMap($this->collectMediaIds($photos, ['media_id']));

        $this->ok([
            'id' => (int) $album['id'],
            'title' => $album['title'],
            'slug' => $album['slug'],
            'description' => $album['description'],
            'type' => $album['album_type'],
            'video_url' => $album['video_url'],
            'year' => $album['year'] !== null ? (int) $album['year'] : null,
            'city' => $album['city_name'],
            'photos' => array_values(array_filter(array_map(function ($p) use ($map) {
                $m = $this->media((int) $p['media_id'], $map);
                return $m ? ['id' => (int) $p['id'], 'caption' => $p['caption'], 'image' => $m] : null;
            }, $photos))),
        ]);
    }

    // ---------------------------------------------------------------- الوفيات

    private function obituaryJson(array $r): array
    {
        return [
            'id' => (int) $r['id'],
            'name' => $r['name'],
            'city' => $r['city_name'],
            'deceased_on' => $r['deceased_on'],
            'condolence_venue' => $r['condolence_venue'],
            'condolence_times' => $r['condolence_times'],
            'condolence_map_url' => $r['condolence_map_url'],
            'details' => $r['details'],
            'status' => $r['status'],
        ];
    }

    public function obituaries(array $params): void
    {
        if (!$this->enabled('obituaries', 'obituaries')) {
            $this->fail('الوفيات غير مفعّلة');
            return;
        }
        $rows = Database::fetchAll(
            'SELECT o.*, c.name AS city_name FROM ' . Database::table('obituaries') . ' o
             LEFT JOIN ' . Database::table('cities') . ' c ON c.id = o.city_id
             ORDER BY (o.status = "active") DESC, o.deceased_on DESC, o.id DESC LIMIT 100'
        );
        $this->ok([
            'items' => array_map(fn($r) => $this->obituaryJson($r), $rows),
        ], 120);
    }

    // ---------------------------------------------------------------- الشعر

    public function poetryIndex(array $params): void
    {
        if (!$this->enabled('poetry', 'poets')) {
            $this->fail('الشعر غير مفعّل');
            return;
        }
        $rows = Database::fetchAll(
            'SELECT p.*, (SELECT COUNT(*) FROM ' . Database::table('poems') . ' m WHERE m.poet_id = p.id AND m.status = "published") AS poems_count
             FROM ' . Database::table('poets') . ' p
             WHERE p.status = "active"
             ORDER BY p.sort_order ASC, p.name ASC'
        );
        $map = $this->mediaMap($this->collectMediaIds($rows, ['photo_media_id']));
        $this->ok([
            'items' => array_map(fn($r) => [
                'id' => (int) $r['id'],
                'name' => $r['name'],
                'bio' => $r['bio'],
                'poems_count' => (int) $r['poems_count'],
                'photo' => $this->media((int) ($r['photo_media_id'] ?? 0), $map),
            ], $rows),
        ]);
    }

    public function poetryShow(array $params): void
    {
        if (!$this->enabled('poetry', 'poets')) {
            $this->fail('الشعر غير مفعّل');
            return;
        }
        $poet = Database::fetchOne(
            'SELECT * FROM ' . Database::table('poets') . ' WHERE id = ? AND status = "active"',
            [(int) ($params['id'] ?? 0)]
        );
        if (!$poet) {
            $this->fail('الشاعر غير موجود');
            return;
        }
        $poems = Database::fetchAll(
            'SELECT id, title, content, occasion FROM ' . Database::table('poems') . '
             WHERE poet_id = ? AND status = "published"
             ORDER BY sort_order ASC, id ASC',
            [$poet['id']]
        );
        $map = $this->mediaMap($this->collectMediaIds([$poet], ['photo_media_id']));
        $this->ok([
            'id' => (int) $poet['id'],
            'name' => $poet['name'],
            'bio' => $poet['bio'],
            'photo' => $this->media((int) ($poet['photo_media_id'] ?? 0), $map),
            'poems' => array_map(fn($p) => [
                'id' => (int) $p['id'],
                'title' => $p['title'],
                'content' => $p['content'],
                'occasion' => $p['occasion'],
            ], $poems),
        ]);
    }

    // ---------------------------------------------------------------- الأرشيف

    public function archiveIndex(array $params): void
    {
        if (!$this->enabled('archive', 'archive_items')) {
            $this->fail('الأرشيف غير مفعّل');
            return;
        }
        $page = max(1, Request::int('page', 1));
        $perPage = min(50, max(1, Request::int('per_page', 20)));
        $offset = ($page - 1) * $perPage;

        $categoryId = Request::int('category', 0);
        $where = "a.status = 'published'";
        $bind = [];
        if ($categoryId > 0) {
            $where .= ' AND a.category_id = ?';
            $bind[] = $categoryId;
        }

        $total = (int) Database::fetchValue(
            'SELECT COUNT(*) FROM ' . Database::table('archive_items') . ' a WHERE ' . $where,
            $bind
        );
        $rows = Database::fetchAll(
            'SELECT a.*, c.name AS category_name FROM ' . Database::table('archive_items') . ' a
             LEFT JOIN ' . Database::table('archive_categories') . ' c ON c.id = a.category_id
             WHERE ' . $where . '
             ORDER BY a.id DESC LIMIT ' . $perPage . ' OFFSET ' . $offset,
            $bind
        );
        $map = $this->mediaMap($this->collectMediaIds($rows, ['cover_media_id']));

        $categories = Database::tableExists('archive_categories') ? Database::fetchAll(
            'SELECT id, name FROM ' . Database::table('archive_categories') . ' ORDER BY sort_order ASC, name ASC'
        ) : [];

        $this->ok([
            'items' => array_map(fn($r) => [
                'id' => (int) $r['id'],
                'title' => $r['title'],
                'slug' => $r['slug'],
                'description' => $r['description'],
                'category' => $r['category_name'],
                'period_label' => $r['period_label'],
                'cover' => $this->media((int) ($r['cover_media_id'] ?? 0), $map),
            ], $rows),
            'categories' => array_map(fn($c) => ['id' => (int) $c['id'], 'name' => $c['name']], $categories),
            'page' => $page,
            'per_page' => $perPage,
            'total' => $total,
        ]);
    }

    public function archiveShow(array $params): void
    {
        if (!$this->enabled('archive', 'archive_items')) {
            $this->fail('الأرشيف غير مفعّل');
            return;
        }
        $item = Database::fetchOne(
            'SELECT a.*, c.name AS category_name FROM ' . Database::table('archive_items') . ' a
             LEFT JOIN ' . Database::table('archive_categories') . ' c ON c.id = a.category_id
             WHERE a.slug = ? AND a.status = "published"',
            [$params['slug'] ?? '']
        );
        if (!$item) {
            $this->fail('المادة غير موجودة');
            return;
        }
        $map = $this->mediaMap($this->collectMediaIds([$item], ['cover_media_id', 'file_media_id']));
        $this->ok([
            'id' => (int) $item['id'],
            'title' => $item['title'],
            'slug' => $item['slug'],
            'description' => $item['description'],
            'category' => $item['category_name'],
            'period_label' => $item['period_label'],
            'source' => $item['source'],
            'cover' => $this->media((int) ($item['cover_media_id'] ?? 0), $map),
            'file' => $this->media((int) ($item['file_media_id'] ?? 0), $map),
        ]);
    }

    // ---------------------------------------------------------------- شجرة النسب

    public function tree(array $params): void
    {
        if (!$this->enabled('family-tree', 'tree_nodes')) {
            $this->fail('شجرة النسب غير مفعّلة');
            return;
        }
        $rows = Database::fetchAll(
            'SELECT id, name, parent_id, sort_order FROM ' . Database::table('tree_nodes') . '
             WHERE is_visible = 1 ORDER BY sort_order ASC, id ASC'
        );
        $this->ok([
            'nodes' => array_map(fn($r) => [
                'id' => (int) $r['id'],
                'name' => $r['name'],
                'parent_id' => $r['parent_id'] !== null ? (int) $r['parent_id'] : null,
            ], $rows),
        ], 3600);
    }

    // ---------------------------------------------------------------- الصفحات

    public function pagesIndex(array $params): void
    {
        if (!$this->enabled('pages', 'pages')) {
            $this->fail('الصفحات غير مفعّلة');
            return;
        }
        $rows = Database::fetchAll(
            'SELECT id, title, slug FROM ' . Database::table('pages') . "
             WHERE status = 'published' ORDER BY sort_order ASC, id ASC"
        );
        $this->ok([
            'items' => array_map(fn($r) => [
                'id' => (int) $r['id'],
                'title' => $r['title'],
                'slug' => $r['slug'],
            ], $rows),
        ], 600);
    }

    public function pagesShow(array $params): void
    {
        if (!$this->enabled('pages', 'pages')) {
            $this->fail('الصفحات غير مفعّلة');
            return;
        }
        $item = Database::fetchOne(
            'SELECT * FROM ' . Database::table('pages') . ' WHERE slug = ? AND status = "published"',
            [$params['slug'] ?? '']
        );
        if (!$item) {
            $this->fail('الصفحة غير موجودة');
            return;
        }
        $map = $this->mediaMap($this->collectMediaIds([$item], ['cover_media_id']));
        $this->ok([
            'id' => (int) $item['id'],
            'title' => $item['title'],
            'slug' => $item['slug'],
            'content_html' => $item['content'],
            'cover' => $this->media((int) ($item['cover_media_id'] ?? 0), $map),
        ], 600);
    }

    // ---------------------------------------------------------------- الإعلانات

    public function announcements(array $params): void
    {
        if (!$this->enabled('announcements', 'announcements')) {
            $this->fail('الإعلانات غير مفعّلة');
            return;
        }
        $rows = Database::fetchAll(
            'SELECT id, title, message, announcement_type, placement FROM ' . Database::table('announcements') . '
             WHERE status = "active"
             AND (starts_at IS NULL OR starts_at <= NOW())
             AND (ends_at IS NULL OR ends_at >= NOW())
             ORDER BY id DESC LIMIT 20'
        );
        $this->ok([
            'items' => array_map(fn($r) => [
                'id' => (int) $r['id'],
                'title' => $r['title'],
                'message' => $r['message'],
                'type' => $r['announcement_type'],
                'placement' => $r['placement'],
            ], $rows),
        ], 120);
    }

    // ---------------------------------------------------------------- البحث

    public function search(array $params): void
    {
        $q = trim((string) Request::get('q', ''));
        if (mb_strlen($q) < 2) {
            $this->ok(['query' => $q, 'results' => []], 0);
            return;
        }
        $like = '%' . $q . '%';
        $results = [];

        if ($this->enabled('news', 'news')) {
            $rows = Database::fetchAll(
                'SELECT id, title, slug, excerpt FROM ' . Database::table('news') . "
                 WHERE status = 'published' AND published_at <= NOW() AND (title LIKE ? OR excerpt LIKE ? OR content LIKE ?)
                 ORDER BY published_at DESC LIMIT 10",
                [$like, $like, $like]
            );
            foreach ($rows as $r) {
                $results[] = ['type' => 'news', 'id' => (int) $r['id'], 'title' => $r['title'], 'slug' => $r['slug'], 'excerpt' => $r['excerpt']];
            }
        }
        if ($this->enabled('gallery', 'gallery_albums')) {
            $rows = Database::fetchAll(
                'SELECT id, title, slug, description FROM ' . Database::table('gallery_albums') . "
                 WHERE status = 'published' AND (title LIKE ? OR description LIKE ?) LIMIT 10",
                [$like, $like]
            );
            foreach ($rows as $r) {
                $results[] = ['type' => 'gallery', 'id' => (int) $r['id'], 'title' => $r['title'], 'slug' => $r['slug'], 'excerpt' => $r['description']];
            }
        }
        if ($this->enabled('archive', 'archive_items')) {
            $rows = Database::fetchAll(
                'SELECT id, title, slug, description FROM ' . Database::table('archive_items') . "
                 WHERE status = 'published' AND (title LIKE ? OR description LIKE ?) LIMIT 10",
                [$like, $like]
            );
            foreach ($rows as $r) {
                $results[] = ['type' => 'archive', 'id' => (int) $r['id'], 'title' => $r['title'], 'slug' => $r['slug'], 'excerpt' => $r['description']];
            }
        }
        if ($this->enabled('pages', 'pages')) {
            $rows = Database::fetchAll(
                'SELECT id, title, slug FROM ' . Database::table('pages') . "
                 WHERE status = 'published' AND (title LIKE ? OR content LIKE ?) LIMIT 10",
                [$like, $like]
            );
            foreach ($rows as $r) {
                $results[] = ['type' => 'page', 'id' => (int) $r['id'], 'title' => $r['title'], 'slug' => $r['slug'], 'excerpt' => null];
            }
        }

        $this->ok(['query' => $q, 'results' => $results], 60);
    }

    // ---------------------------------------------------------------- جوال القبيلة

    /** يقرأ حقلًا من جسم الطلب: يدعم JSON و form-urlencoded معًا */
    private function bodyField(string $key, $default = null)
    {
        static $json = null;
        if ($json === null) {
            $raw = file_get_contents('php://input') ?: '';
            $decoded = json_decode($raw, true);
            $json = is_array($decoded) ? $decoded : [];
        }
        return $json[$key] ?? $_POST[$key] ?? $default;
    }

    public function directoryInfo(array $params): void
    {
        if (!$this->enabled('directory', 'directory_contacts')) {
            $this->fail('جوال القبيلة غير مفعّل');
            return;
        }
        $consentText = trim((string) Settings::get('directory_consent_text', ''));
        if ($consentText === '') {
            $consentText = \Core\Terms::phrase('consent_directory');
        }
        $cityEnabled = Settings::get('directory_city_enabled', '1') === '1';
        $cities = [];
        if ($cityEnabled && Database::tableExists('cities')) {
            $cities = Database::fetchAll(
                'SELECT id, name FROM ' . Database::table('cities') . " WHERE status = 'active' ORDER BY sort_order ASC, name ASC"
            );
        }
        $this->ok([
            'consent_text' => $consentText,
            'city_enabled' => $cityEnabled,
            'cities' => array_map(fn($c) => ['id' => (int) $c['id'], 'name' => $c['name']], $cities),
        ], 600);
    }

    public function directoryRegister(array $params): void
    {
        if (!$this->enabled('directory', 'directory_contacts')) {
            $this->fail('جوال القبيلة غير مفعّل');
            return;
        }

        $throttleKey = 'api_directory_register:' . \Core\Support\Request::ip();
        if (\Core\Support\RateLimiter::tooManyAttempts($throttleKey, 5, 15)) {
            $this->fail('تم إيقاف المحاولة مؤقتًا بسبب تكرار الطلبات. حاول بعد 15 دقيقة.', 429);
            return;
        }
        \Core\Support\RateLimiter::hit($throttleKey);

        $name = \Core\Support\Security::cleanText(trim((string) $this->bodyField('name', '')));
        $phoneRaw = trim((string) $this->bodyField('phone', ''));
        $email = \Core\Support\Security::cleanText(trim((string) $this->bodyField('email', '')));
        $consent = filter_var($this->bodyField('consent', false), FILTER_VALIDATE_BOOLEAN);

        $errors = [];
        if ($name === '') {
            $errors[] = 'الاسم مطلوب.';
        }
        $phone = \Modules\Directory\Support\Phone::normalize($phoneRaw);
        if ($phoneRaw === '' || !\Modules\Directory\Support\Phone::isValid($phone)) {
            $errors[] = 'رقم الجوال غير صالح.';
        }
        if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'صيغة البريد الإلكتروني غير صحيحة.';
        }
        if (!$consent) {
            $errors[] = 'يجب الموافقة الصريحة قبل إتمام التسجيل.';
        }
        if ($errors) {
            $this->fail(implode(' ', $errors), 422);
            return;
        }

        if (Settings::get('directory_prevent_duplicates', '1') === '1') {
            $exists = (int) Database::fetchValue(
                'SELECT COUNT(*) FROM ' . Database::table('directory_contacts') . " WHERE phone = ? AND status = 'active'",
                [$phone]
            );
            if ($exists) {
                // رسالة عامة متعمدة: لا نكشف عن كون الرقم مسجّلًا مسبقًا أم لا
                $this->fail('تعذر إتمام التسجيل، يرجى التواصل مع إدارة الموقع.', 422);
                return;
            }
        }

        $cityId = null;
        $cityIdsCsv = null;
        if (Settings::get('directory_city_enabled', '1') === '1') {
            $cityIdsInput = $this->bodyField('city_ids', []);
            if (is_string($cityIdsInput)) {
                $cityIdsInput = explode(',', $cityIdsInput);
            }
            $cityIds = array_values(array_filter(array_map('intval', (array) $cityIdsInput)));
            $cityId = $cityIds[0] ?? null;
            $cityIdsCsv = $cityIds ? implode(',', $cityIds) : null;
        }

        Database::insert('directory_contacts', [
            'name' => $name,
            'phone' => $phone,
            'city_id' => $cityId,
            'city_ids' => $cityIdsCsv,
            'branch_id' => null,
            'email' => $email !== '' ? $email : null,
            'status' => 'active',
            'source' => 'website_form',
            'consent_at' => date('Y-m-d H:i:s'),
            'registered_at' => date('Y-m-d H:i:s'),
            'created_by' => null,
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        $this->ok(['message' => 'تم استلام طلبك بنجاح.'], 0);
    }

    public function directoryRemove(array $params): void
    {
        if (!$this->enabled('directory', 'directory_removal_requests')) {
            $this->fail('جوال القبيلة غير مفعّل');
            return;
        }

        $throttleKey = 'api_directory_remove:' . \Core\Support\Request::ip();
        if (\Core\Support\RateLimiter::tooManyAttempts($throttleKey, 5, 15)) {
            $this->fail('تم إيقاف المحاولة مؤقتًا بسبب تكرار الطلبات. حاول بعد 15 دقيقة.', 429);
            return;
        }
        \Core\Support\RateLimiter::hit($throttleKey);

        $phone = \Modules\Directory\Support\Phone::normalize(trim((string) $this->bodyField('phone', '')));
        if (!\Modules\Directory\Support\Phone::isValid($phone)) {
            $this->fail('الرجاء إدخال رقم جوال صالح.', 422);
            return;
        }

        // طلب يراجعه المدير من لوحة التحكم — لا حذف تلقائي ولا كشف عن حالة الرقم
        Database::insert('directory_removal_requests', [
            'phone' => $phone,
            'requested_at' => date('Y-m-d H:i:s'),
            'status' => 'pending',
        ]);

        $this->ok(['message' => 'تم استلام طلبك، وستتم مراجعته من إدارة الموقع.'], 0);
    }
}
