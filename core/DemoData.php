<?php

namespace Core;

/**
 * توليد وحذف بيانات تجريبية آمنة (بدون أسماء أو أرقام حقيقية) لتجربة النظام
 * فور تثبيته. كل سجل يُنشأ يُسجَّل في demo_data_log لضمان حذفه بضغطة واحدة لاحقًا.
 */
final class DemoData
{
    public static function hasDemoData(): bool
    {
        if (!Database::tableExists('demo_data_log')) {
            return false;
        }
        return (int) Database::fetchValue('SELECT COUNT(*) FROM ' . Database::table('demo_data_log')) > 0;
    }

    public static function seed(): array
    {
        $created = [];

        $mediaId = self::createDemoImage();
        if ($mediaId) {
            $created[] = 'صورة تجريبية';
        }

        $cityId = self::seedCity();
        if ($cityId) {
            $created[] = 'مدينة تجريبية';
        }

        if (ModuleManager::isEnabled('pages')) {
            self::seedPage();
            $created[] = 'صفحة تعريفية';
        }

        if (ModuleManager::isEnabled('news')) {
            self::seedNews($mediaId);
            $created[] = Terms::phrase('news') . ' تجريبي';
        }

        $eventId = null;
        if (ModuleManager::isEnabled('events')) {
            $eventId = self::seedEvent($mediaId, $cityId);
            $created[] = 'مناسبة زواج تجريبية';
        }

        if (ModuleManager::isEnabled('calendar')) {
            self::seedCalendarEntry($cityId, $eventId);
            $created[] = 'موعد في الرزنامة';
        }

        if (ModuleManager::isEnabled('gatherings')) {
            self::seedGathering($cityId);
            $created[] = 'جمعة دورية تجريبية';
        }

        if (ModuleManager::isEnabled('family-tree')) {
            self::seedTree();
            $created[] = 'سلسلة نسب بسيطة';
        }

        if (ModuleManager::isEnabled('gallery')) {
            self::seedGalleryAlbum($mediaId, $cityId);
            $created[] = 'ألبوم صور تجريبي';
        }

        if (ModuleManager::isEnabled('announcements')) {
            self::seedAnnouncement();
            $created[] = 'إعلان تجريبي';
        }

        ActivityLog::record('demo_data_seed', 'توليد بيانات تجريبية: ' . implode('، ', $created));

        return $created;
    }

    public static function purge(): int
    {
        if (!Database::tableExists('demo_data_log')) {
            return 0;
        }

        $rows = Database::fetchAll('SELECT * FROM ' . Database::table('demo_data_log') . ' ORDER BY id DESC');
        $count = 0;

        foreach ($rows as $row) {
            $table = $row['table_name'];
            if (!Database::tableExists($table)) {
                continue;
            }
            try {
                if ($table === 'media') {
                    $media = Database::fetchOne('SELECT stored_path, thumb_path FROM ' . Database::table('media') . ' WHERE id = ?', [$row['record_id']]);
                    if ($media) {
                        Media::deleteFiles($media['stored_path'], $media['thumb_path']);
                    }
                }
                Database::delete($table, ['id' => $row['record_id']]);
                $count++;
            } catch (\Throwable $e) {
                // سجل قد يكون حُذف مسبقًا يدويًا من قبل المدير؛ تجاهله والمتابعة
                Logger::error('demo_data purge skip', ['table' => $table, 'id' => $row['record_id']]);
            }
        }

        Database::query('DELETE FROM ' . Database::table('demo_data_log'));

        ActivityLog::record('demo_data_purge', "حذف جميع البيانات التجريبية ({$count} عنصر)");

        return $count;
    }

    private static function log(string $table, $id): void
    {
        Database::insert('demo_data_log', [
            'table_name' => $table,
            'record_id' => $id,
            'created_at' => date('Y-m-d H:i:s'),
        ]);
    }

    private static function createDemoImage(): ?int
    {
        if (!extension_loaded('gd')) {
            return null;
        }

        $dir = STORAGE_PATH . '/uploads/originals/demo';
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        $fileName = 'demo/demo-cover-' . substr(md5((string) time()), 0, 6) . '.jpg';
        $fullPath = STORAGE_PATH . '/uploads/originals/' . $fileName;

        $image = imagecreatetruecolor(800, 500);
        $bg = imagecolorallocate($image, 15, 110, 94);
        $fg = imagecolorallocate($image, 255, 255, 255);
        imagefilledrectangle($image, 0, 0, 800, 500, $bg);
        imagestring($image, 5, 300, 240, 'DEMO', $fg);
        imagejpeg($image, $fullPath, 82);
        imagedestroy($image);

        $mediaId = Database::insert('media', [
            'file_name' => 'demo-cover.jpg',
            'stored_path' => $fileName,
            'thumb_path' => null,
            'mime_type' => 'image/jpeg',
            'extension' => 'jpg',
            'size_bytes' => filesize($fullPath),
            'width' => 800,
            'height' => 500,
            'title' => 'صورة تجريبية',
            'alt_text' => 'صورة تجريبية',
            'created_at' => date('Y-m-d H:i:s'),
        ]);

        self::log('media', $mediaId);

        return (int) $mediaId;
    }

    private static function seedCity(): ?int
    {
        if (!Database::tableExists('cities')) {
            return null;
        }
        $id = Database::insert('cities', [
            'name' => 'المدينة التجريبية',
            'sort_order' => 999,
            'status' => 'active',
        ]);
        self::log('cities', $id);
        return (int) $id;
    }

    private static function seedPage(): void
    {
        $id = Database::insert('pages', [
            'title' => 'صفحة تعريفية (تجريبية)',
            'slug' => Support\Str::slug('صفحة تعريفية تجريبية', 'page'),
            'content' => '<p>هذه صفحة تجريبية لتوضيح شكل الصفحات التعريفية في الموقع. يمكن تعديل محتواها أو حذفها من لوحة التحكم.</p>',
            'template' => 'default',
            'status' => 'published',
            'sort_order' => 999,
            'show_in_menu' => 0,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);
        self::log('pages', $id);
    }

    private static function seedNews(?int $mediaId): void
    {
        $categoryId = null;
        if (Database::tableExists('news_categories')) {
            $categoryId = Database::insert('news_categories', [
                'name' => 'أخبار عامة (تجريبي)',
                'slug' => Support\Str::slug('أخبار عامة تجريبي', 'cat'),
                'sort_order' => 999,
            ]);
            self::log('news_categories', $categoryId);
        }

        $id = Database::insert('news', [
            'title' => 'خبر تجريبي: مثال على ظهور الأخبار في الموقع',
            'slug' => Support\Str::slug('خبر تجريبي مثال', 'news'),
            'excerpt' => 'هذا خبر تجريبي يوضح شكل عرض الأخبار في الصفحة الرئيسية وصفحة الأخبار.',
            'content' => '<p>هذا محتوى خبر تجريبي. يمكن تعديله أو حذفه بسهولة من لوحة التحكم، أو حذف كل البيانات التجريبية دفعة واحدة من شاشة "بيانات تجريبية".</p>',
            'category_id' => $categoryId,
            'cover_media_id' => $mediaId,
            'author_name' => 'إدارة الموقع',
            'status' => 'published',
            'is_pinned' => 0,
            'is_featured' => 1,
            'published_at' => date('Y-m-d H:i:s'),
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);
        self::log('news', $id);
    }

    private static function seedEvent(?int $mediaId, ?int $cityId): int
    {
        $starts = date('Y-m-d H:i:s', strtotime('+14 days 19:00'));

        $id = Database::insert('events', [
            'title' => 'مناسبة زواج تجريبية',
            'slug' => Support\Str::slug('مناسبة زواج تجريبية', 'event'),
            'event_type' => 'زواج',
            'excerpt' => 'مثال لصفحة مناسبة زواج تجريبية.',
            'content' => '<p>هذه صفحة مناسبة تجريبية لتوضيح شكل عرض المناسبات في الموقع.</p>',
            'cover_media_id' => $mediaId,
            'city_id' => $cityId,
            'venue_name' => 'قاعة تجريبية',
            'starts_at' => $starts,
            'status' => 'published',
            'is_featured' => 1,
            'is_pinned' => 0,
            'published_at' => date('Y-m-d H:i:s'),
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);
        self::log('events', $id);

        return (int) $id;
    }

    private static function seedCalendarEntry(?int $cityId, ?int $eventId): void
    {
        $id = Database::insert('calendar_entries', [
            'title' => 'مناسبة زواج تجريبية',
            'entry_type' => 'زواج',
            'entry_datetime' => date('Y-m-d H:i:s', strtotime('+14 days 19:00')),
            'city_id' => $cityId,
            'venue_name' => 'قاعة تجريبية',
            'event_id' => $eventId,
            'status' => 'published',
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);
        self::log('calendar_entries', $id);
    }

    private static function seedGathering(?int $cityId): void
    {
        $id = Database::insert('gatherings', [
            'title' => 'جمعة تجريبية',
            'description' => 'مثال لجمعة دورية تجريبية.',
            'city_id' => $cityId,
            'venue' => 'مجلس تجريبي',
            'start_time' => '20:00:00',
            'recurrence_type' => 'weekly',
            'recurrence_days' => '4',
            'recurrence_label' => 'كل يوم خميس، من 8:00 م',
            'starts_on' => date('Y-m-d'),
            'status' => 'active',
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);
        self::log('gatherings', $id);
    }

    private static function seedTree(): void
    {
        $grandfather = Database::insert('tree_nodes', [
            'name' => 'الجد الأول (تجريبي)',
            'parent_id' => null,
            'sort_order' => 1,
            'is_visible' => 1,
            'created_at' => date('Y-m-d H:i:s'),
        ]);
        self::log('tree_nodes', $grandfather);

        $father = Database::insert('tree_nodes', [
            'name' => 'الابن الأول (تجريبي)',
            'parent_id' => $grandfather,
            'sort_order' => 1,
            'is_visible' => 1,
            'created_at' => date('Y-m-d H:i:s'),
        ]);
        self::log('tree_nodes', $father);

        $son = Database::insert('tree_nodes', [
            'name' => 'الحفيد الأول (تجريبي)',
            'parent_id' => $father,
            'sort_order' => 1,
            'is_visible' => 1,
            'created_at' => date('Y-m-d H:i:s'),
        ]);
        self::log('tree_nodes', $son);
    }

    private static function seedGalleryAlbum(?int $mediaId, ?int $cityId): void
    {
        $albumId = Database::insert('gallery_albums', [
            'title' => 'ألبوم صور تجريبي',
            'slug' => Support\Str::slug('ألبوم صور تجريبي', 'album'),
            'description' => 'مثال لألبوم صور تجريبي.',
            'cover_media_id' => $mediaId,
            'album_type' => 'photo',
            'year' => (int) date('Y'),
            'city_id' => $cityId,
            'status' => 'published',
            'sort_order' => 999,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);
        self::log('gallery_albums', $albumId);

        if ($mediaId) {
            $photoId = Database::insert('gallery_photos', [
                'album_id' => $albumId,
                'media_id' => $mediaId,
                'sort_order' => 1,
                'created_at' => date('Y-m-d H:i:s'),
            ]);
            self::log('gallery_photos', $photoId);
        }
    }

    private static function seedAnnouncement(): void
    {
        $id = Database::insert('announcements', [
            'title' => 'إعلان تجريبي',
            'message' => 'هذا إعلان تجريبي لتوضيح شكل ظهور الإعلانات في الموقع.',
            'announcement_type' => 'رسالة من الإدارة',
            'placement' => 'home_card',
            'status' => 'active',
            'popup_frequency' => 'once',
            'show_on_desktop' => 1,
            'show_on_mobile' => 1,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);
        self::log('announcements', $id);
    }
}
