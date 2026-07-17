<?php

namespace Core;

/**
 * توليد وحذف بيانات تجريبية آمنة (بدون أسماء أو أرقام حقيقية) لتجربة النظام
 * فور تثبيته. يولّد محتوى غنيًا من كل جزئية (أخبار متعددة، مناسبات قادمة
 * وسابقة، مواعيد، جمعات، ألبومات، أرشيف، إعلانات بكل أماكن العرض، شجرة نسب)
 * ليظهر الموقع بشكله الحقيقي المكتمل. كل سجل يُنشأ يُسجَّل في demo_data_log
 * لضمان حذف كل شيء بضغطة واحدة لاحقًا.
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

        $images = self::createDemoImages();
        if ($images) {
            $created[] = count($images) . ' صور تجريبية';
        }

        $cityIds = self::seedCities();
        if ($cityIds) {
            $created[] = count($cityIds) . ' مدن تجريبية';
        }

        if (ModuleManager::isEnabled('pages')) {
            $count = self::seedPages();
            $created[] = $count . ' صفحات تعريفية';
        }

        if (ModuleManager::isEnabled('news')) {
            $count = self::seedNews($images);
            $created[] = $count . ' أخبار تجريبية';
        }

        $upcomingEventIds = [];
        if (ModuleManager::isEnabled('events')) {
            [$count, $upcomingEventIds] = self::seedEvents($images, $cityIds);
            $created[] = $count . ' مناسبات (قادمة وسابقة)';
        }

        if (ModuleManager::isEnabled('calendar')) {
            $count = self::seedCalendarEntries($cityIds, $upcomingEventIds);
            $created[] = $count . ' مواعيد في الرزنامة';
        }

        if (ModuleManager::isEnabled('gatherings')) {
            $count = self::seedGatherings($cityIds);
            $created[] = $count . ' جمعات دورية';
        }

        if (ModuleManager::isEnabled('family-tree')) {
            $count = self::seedTree();
            $created[] = 'سلسلة نسب (' . $count . ' أفراد)';
        }

        if (ModuleManager::isEnabled('gallery')) {
            $count = self::seedGalleryAlbums($images, $cityIds);
            $created[] = $count . ' ألبومات صور';
        }

        if (ModuleManager::isEnabled('archive')) {
            $count = self::seedArchive($images);
            $created[] = $count . ' مواد أرشيفية';
        }

        if (ModuleManager::isEnabled('announcements')) {
            $count = self::seedAnnouncements();
            $created[] = $count . ' إعلانات (بطاقة وشريط ونافذة)';
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

    /**
     * توليد مجموعة صور تجريبية بألوان وعناوين مختلفة لتبدو البطاقات متنوعة.
     * @return int[] معرفات الوسائط المولدة
     */
    private static function createDemoImages(): array
    {
        if (!extension_loaded('gd')) {
            return [];
        }

        $dir = STORAGE_PATH . '/uploads/originals/demo';
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        $palette = [
            ['label' => 'DEMO 1', 'rgb' => [15, 110, 94]],
            ['label' => 'DEMO 2', 'rgb' => [30, 77, 107]],
            ['label' => 'DEMO 3', 'rgb' => [92, 67, 38]],
            ['label' => 'DEMO 4', 'rgb' => [94, 31, 43]],
            ['label' => 'DEMO 5', 'rgb' => [75, 83, 32]],
            ['label' => 'DEMO 6', 'rgb' => [26, 43, 76]],
        ];

        $ids = [];
        foreach ($palette as $i => $spec) {
            $fileName = 'demo/demo-' . ($i + 1) . '-' . substr(md5(uniqid((string) $i, true)), 0, 6) . '.jpg';
            $fullPath = STORAGE_PATH . '/uploads/originals/' . $fileName;

            $image = imagecreatetruecolor(800, 500);
            [$r, $g, $b] = $spec['rgb'];
            $bg = imagecolorallocate($image, $r, $g, $b);
            $fg = imagecolorallocate($image, 255, 255, 255);
            imagefilledrectangle($image, 0, 0, 800, 500, $bg);
            imagestring($image, 5, 350, 240, $spec['label'], $fg);
            imagejpeg($image, $fullPath, 82);
            imagedestroy($image);

            $mediaId = Database::insert('media', [
                'file_name' => 'demo-' . ($i + 1) . '.jpg',
                'stored_path' => $fileName,
                'thumb_path' => null,
                'mime_type' => 'image/jpeg',
                'extension' => 'jpg',
                'size_bytes' => filesize($fullPath),
                'width' => 800,
                'height' => 500,
                'title' => 'صورة تجريبية ' . ($i + 1),
                'alt_text' => 'صورة تجريبية',
                'created_at' => date('Y-m-d H:i:s'),
            ]);
            self::log('media', $mediaId);
            $ids[] = (int) $mediaId;
        }

        return $ids;
    }

    /** @return int[] */
    private static function seedCities(): array
    {
        if (!Database::tableExists('cities')) {
            return [];
        }
        $ids = [];
        foreach (['المدينة التجريبية الأولى', 'المدينة التجريبية الثانية', 'المدينة التجريبية الثالثة'] as $i => $name) {
            $id = Database::insert('cities', [
                'name' => $name,
                'sort_order' => 990 + $i,
                'status' => 'active',
            ]);
            self::log('cities', $id);
            $ids[] = (int) $id;
        }
        return $ids;
    }

    private static function seedPages(): int
    {
        $pages = [
            [
                'title' => 'من نحن (تجريبية)',
                'content' => '<p>هذه صفحة تعريفية تجريبية تشرح تاريخ العائلة ونشأتها وأبرز محطاتها. يمكن استبدال هذا النص بالمحتوى الحقيقي من لوحة التحكم.</p><p>الهدف من هذه الصفحة توضيح شكل الصفحات الثابتة في الموقع وكيف تظهر للزائر.</p>',
            ],
            [
                'title' => 'أهداف الموقع (تجريبية)',
                'content' => '<p>صفحة تجريبية تعرض أهداف الموقع: التواصل بين أفراد العائلة، توثيق المناسبات والأخبار، وحفظ تاريخ العائلة وأرشيفها.</p>',
            ],
            [
                'title' => 'أسئلة شائعة (تجريبية)',
                'content' => '<p><strong>كيف أضيف خبرًا جديدًا؟</strong></p><p>من لوحة التحكم → الأخبار → إضافة خبر.</p><p><strong>كيف أحذف هذه البيانات التجريبية؟</strong></p><p>من لوحة التحكم → بيانات تجريبية → حذف جميع البيانات التجريبية.</p>',
            ],
        ];

        foreach ($pages as $i => $page) {
            $id = Database::insert('pages', [
                'title' => $page['title'],
                'slug' => Support\Str::slug($page['title'], 'page'),
                'content' => $page['content'],
                'template' => 'default',
                'status' => 'published',
                'sort_order' => 990 + $i,
                'show_in_menu' => 0,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ]);
            self::log('pages', $id);
        }

        return count($pages);
    }

    private static function seedNews(array $images): int
    {
        $categoryIds = [];
        if (Database::tableExists('news_categories')) {
            foreach (['أخبار عامة (تجريبي)', 'إنجازات (تجريبي)'] as $i => $name) {
                $catId = Database::insert('news_categories', [
                    'name' => $name,
                    'slug' => Support\Str::slug($name, 'cat'),
                    'sort_order' => 990 + $i,
                ]);
                self::log('news_categories', $catId);
                $categoryIds[] = (int) $catId;
            }
        }

        $items = [
            ['title' => 'خبر تجريبي: انطلاق الموقع الرسمي الجديد', 'days' => 2, 'pinned' => 1, 'featured' => 1, 'excerpt' => 'مثال لخبر مثبّت يظهر في مقدمة الأخبار ويعرض إطلاق الموقع.'],
            ['title' => 'خبر تجريبي: تهنئة بمناسبة نجاح أحد أبناء العائلة', 'days' => 5, 'pinned' => 0, 'featured' => 1, 'excerpt' => 'مثال لخبر تهنئة يوضح شكل الأخبار المميزة في الصفحة الرئيسية.'],
            ['title' => 'خبر تجريبي: دعوة لحضور الاجتماع السنوي القادم', 'days' => 9, 'pinned' => 0, 'featured' => 0, 'excerpt' => 'مثال لخبر دعوة عامة يظهر في قائمة الأخبار وشريط الأخبار المتحرك.'],
            ['title' => 'خبر تجريبي: تحديث بيانات التواصل في جوال العائلة', 'days' => 14, 'pinned' => 0, 'featured' => 0, 'excerpt' => 'مثال لخبر تنظيمي قصير.'],
            ['title' => 'خبر تجريبي: تغطية مصورة لفعالية سابقة', 'days' => 21, 'pinned' => 0, 'featured' => 0, 'excerpt' => 'مثال لخبر مرتبط بمعرض الصور.'],
            ['title' => 'خبر تجريبي: أرشيف جديد لوثائق العائلة', 'days' => 30, 'pinned' => 0, 'featured' => 0, 'excerpt' => 'مثال لخبر يوضح ربط الأخبار بالأرشيف التاريخي.'],
        ];

        foreach ($items as $i => $item) {
            $publishedAt = date('Y-m-d H:i:s', strtotime("-{$item['days']} days"));
            $id = Database::insert('news', [
                'title' => $item['title'],
                'slug' => Support\Str::slug($item['title'], 'news'),
                'excerpt' => $item['excerpt'],
                'content' => '<p>' . $item['excerpt'] . '</p><p>هذا محتوى تجريبي كامل للخبر. يمكن تعديله أو حذفه من لوحة التحكم، أو حذف كل البيانات التجريبية دفعة واحدة من شاشة "بيانات تجريبية".</p>',
                'category_id' => $categoryIds ? $categoryIds[$i % count($categoryIds)] : null,
                'tags' => 'تجريبي',
                'cover_media_id' => $images ? $images[$i % count($images)] : null,
                'author_name' => 'إدارة الموقع',
                'status' => 'published',
                'is_pinned' => $item['pinned'],
                'is_featured' => $item['featured'],
                'published_at' => $publishedAt,
                'created_at' => $publishedAt,
                'updated_at' => $publishedAt,
            ]);
            self::log('news', $id);
        }

        return count($items);
    }

    /** @return array{0:int,1:int[]} العدد الكلي ومعرفات المناسبات القادمة */
    private static function seedEvents(array $images, array $cityIds): array
    {
        $events = [
            ['title' => 'مناسبة زواج تجريبية', 'type' => 'زواج', 'days' => 14, 'featured' => 1, 'venue' => 'قاعة الاحتفالات التجريبية'],
            ['title' => 'حفل تخرج تجريبي', 'type' => 'تخرج', 'days' => 25, 'featured' => 0, 'venue' => 'الصالة الكبرى التجريبية'],
            ['title' => 'اجتماع العائلة السنوي (تجريبي)', 'type' => 'اجتماع', 'days' => -20, 'featured' => 0, 'venue' => 'الاستراحة التجريبية'],
            ['title' => 'حفل تكريم المتفوقين (تجريبي)', 'type' => 'تكريم', 'days' => -60, 'featured' => 0, 'venue' => 'قاعة المؤتمرات التجريبية'],
        ];

        $upcomingIds = [];
        foreach ($events as $i => $event) {
            $starts = date('Y-m-d H:i:s', strtotime(($event['days'] >= 0 ? '+' : '') . $event['days'] . ' days 19:00'));
            $createdAt = date('Y-m-d H:i:s', strtotime('-' . (abs($event['days']) + 10) . ' days'));
            $id = Database::insert('events', [
                'title' => $event['title'],
                'slug' => Support\Str::slug($event['title'], 'event'),
                'event_type' => $event['type'],
                'excerpt' => 'مثال لصفحة مناسبة (' . $event['type'] . ') يوضح شكل عرض المناسبات في الموقع.',
                'content' => '<p>هذه صفحة مناسبة تجريبية من نوع "' . $event['type'] . '". تعرض تفاصيل المكان والزمان، ويمكن إرفاق صور ومعرض وفيديو بها من لوحة التحكم.</p>',
                'cover_media_id' => $images ? $images[$i % count($images)] : null,
                'city_id' => $cityIds ? $cityIds[$i % count($cityIds)] : null,
                'venue_name' => $event['venue'],
                'starts_at' => $starts,
                'status' => 'published',
                'is_featured' => $event['featured'],
                'is_pinned' => 0,
                'published_at' => $createdAt,
                'created_at' => $createdAt,
                'updated_at' => $createdAt,
            ]);
            self::log('events', $id);
            if ($event['days'] >= 0) {
                $upcomingIds[] = (int) $id;
            }
        }

        return [count($events), $upcomingIds];
    }

    private static function seedCalendarEntries(array $cityIds, array $upcomingEventIds): int
    {
        $entries = [
            ['title' => 'مناسبة زواج تجريبية', 'type' => 'زواج', 'days' => 14, 'venue' => 'قاعة الاحتفالات التجريبية', 'event' => $upcomingEventIds[0] ?? null],
            ['title' => 'حفل تخرج تجريبي', 'type' => 'تخرج', 'days' => 25, 'venue' => 'الصالة الكبرى التجريبية', 'event' => $upcomingEventIds[1] ?? null],
            ['title' => 'وليمة عشاء تجريبية', 'type' => 'وليمة', 'days' => 7, 'venue' => 'منزل العائلة التجريبي', 'event' => null],
            ['title' => 'اجتماع لجنة الموقع (تجريبي)', 'type' => 'اجتماع', 'days' => 40, 'venue' => 'عن بُعد', 'event' => null],
        ];

        foreach ($entries as $i => $entry) {
            $id = Database::insert('calendar_entries', [
                'title' => $entry['title'],
                'entry_type' => $entry['type'],
                'entry_datetime' => date('Y-m-d H:i:s', strtotime('+' . $entry['days'] . ' days 19:00')),
                'city_id' => $cityIds ? $cityIds[$i % count($cityIds)] : null,
                'venue_name' => $entry['venue'],
                'event_id' => $entry['event'],
                'status' => 'published',
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ]);
            self::log('calendar_entries', $id);
        }

        return count($entries);
    }

    private static function seedGatherings(array $cityIds): int
    {
        $gatherings = [
            [
                'title' => 'جمعة الخميس الأسبوعية (تجريبية)',
                'description' => 'مثال لجمعة أسبوعية ثابتة يلتقي فيها أفراد العائلة.',
                'venue' => 'المجلس التجريبي الكبير',
                'time' => '20:00:00',
                'recurrence_type' => 'weekly',
                'recurrence_days' => '4',
                'label' => 'كل يوم خميس، من 8:00 م',
            ],
            [
                'title' => 'لقاء أول جمعة من الشهر (تجريبي)',
                'description' => 'مثال لجمعة شهرية تجمع العائلة في بداية كل شهر.',
                'venue' => 'الاستراحة التجريبية',
                'time' => '21:00:00',
                'recurrence_type' => 'monthly',
                'recurrence_days' => '5',
                'label' => 'أول جمعة من كل شهر، من 9:00 م',
            ],
            [
                'title' => 'ديوانية الأحد والثلاثاء (تجريبية)',
                'description' => 'مثال لجمعة بأيام محددة من الأسبوع.',
                'venue' => 'الديوانية التجريبية',
                'time' => '19:30:00',
                'recurrence_type' => 'specific_weekdays',
                'recurrence_days' => '0,2',
                'label' => 'كل أحد وثلاثاء، من 7:30 م',
            ],
        ];

        foreach ($gatherings as $i => $g) {
            $id = Database::insert('gatherings', [
                'title' => $g['title'],
                'description' => $g['description'],
                'city_id' => $cityIds ? $cityIds[$i % count($cityIds)] : null,
                'venue' => $g['venue'],
                'start_time' => $g['time'],
                'recurrence_type' => $g['recurrence_type'],
                'recurrence_days' => $g['recurrence_days'],
                'recurrence_label' => $g['label'],
                'starts_on' => date('Y-m-d'),
                'status' => 'active',
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ]);
            self::log('gatherings', $id);
        }

        return count($gatherings);
    }

    private static function seedTree(): int
    {
        $now = date('Y-m-d H:i:s');
        $count = 0;

        $insert = function (string $name, ?int $parentId, int $sort) use ($now, &$count): int {
            $id = Database::insert('tree_nodes', [
                'name' => $name,
                'parent_id' => $parentId,
                'sort_order' => $sort,
                'is_visible' => 1,
                'created_at' => $now,
            ]);
            self::log('tree_nodes', $id);
            $count++;
            return (int) $id;
        };

        $grandfather = $insert('الجد الأكبر (تجريبي)', null, 1);
        $son1 = $insert('الابن الأول (تجريبي)', $grandfather, 1);
        $son2 = $insert('الابن الثاني (تجريبي)', $grandfather, 2);
        $son3 = $insert('الابن الثالث (تجريبي)', $grandfather, 3);
        $insert('الحفيد الأول (تجريبي)', $son1, 1);
        $insert('الحفيد الثاني (تجريبي)', $son1, 2);
        $insert('الحفيد الثالث (تجريبي)', $son2, 1);
        $insert('الحفيد الرابع (تجريبي)', $son3, 1);

        return $count;
    }

    private static function seedGalleryAlbums(array $images, array $cityIds): int
    {
        $year = (int) date('Y');
        $albums = [
            ['title' => 'ألبوم الاجتماع السنوي (تجريبي)', 'year' => $year, 'desc' => 'مثال لألبوم صور فعالية حديثة.'],
            ['title' => 'ألبوم حفل التكريم (تجريبي)', 'year' => $year - 1, 'desc' => 'مثال لألبوم من سنة سابقة لتوضيح التصنيف بالسنوات.'],
            ['title' => 'ألبوم لقاءات قديمة (تجريبي)', 'year' => $year - 2, 'desc' => 'مثال لألبوم أقدم يظهر في أرشيف المعرض.'],
        ];

        foreach ($albums as $i => $album) {
            $cover = $images ? $images[$i % count($images)] : null;
            $albumId = Database::insert('gallery_albums', [
                'title' => $album['title'],
                'slug' => Support\Str::slug($album['title'], 'album'),
                'description' => $album['desc'],
                'cover_media_id' => $cover,
                'album_type' => 'photo',
                'year' => $album['year'],
                'city_id' => $cityIds ? $cityIds[$i % count($cityIds)] : null,
                'status' => 'published',
                'sort_order' => 990 + $i,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ]);
            self::log('gallery_albums', $albumId);

            foreach (array_slice($images, 0, 4) as $sort => $mediaId) {
                $photoId = Database::insert('gallery_photos', [
                    'album_id' => $albumId,
                    'media_id' => $mediaId,
                    'sort_order' => $sort + 1,
                    'created_at' => date('Y-m-d H:i:s'),
                ]);
                self::log('gallery_photos', $photoId);
            }
        }

        return count($albums);
    }

    private static function seedArchive(array $images): int
    {
        $categoryIds = [];
        if (Database::tableExists('archive_categories')) {
            foreach (['وثائق (تجريبي)', 'صور قديمة (تجريبي)'] as $i => $name) {
                $catId = Database::insert('archive_categories', [
                    'name' => $name,
                    'slug' => Support\Str::slug($name, 'arc-cat'),
                    'sort_order' => 990 + $i,
                ]);
                self::log('archive_categories', $catId);
                $categoryIds[] = (int) $catId;
            }
        }

        $items = [
            ['title' => 'وثيقة تاريخية تجريبية', 'period' => 'قبل 50 عامًا', 'source' => 'أرشيف العائلة (تجريبي)', 'desc' => 'مثال لوثيقة تاريخية محفوظة في الأرشيف مع فترة زمنية ومصدر.'],
            ['title' => 'صورة قديمة لمجلس العائلة (تجريبية)', 'period' => 'قبل 30 عامًا', 'source' => 'ألبوم خاص (تجريبي)', 'desc' => 'مثال لصورة قديمة مؤرشفة تظهر طريقة عرض الصور التاريخية.'],
            ['title' => 'قصيدة مهداة للعائلة (تجريبية)', 'period' => 'قبل 15 عامًا', 'source' => 'ديوان تجريبي', 'desc' => 'مثال لمادة أدبية محفوظة في الأرشيف.'],
            ['title' => 'دعوة اجتماع قديمة (تجريبية)', 'period' => 'قبل 10 أعوام', 'source' => 'أوراق العائلة (تجريبي)', 'desc' => 'مثال لمستند قديم يوثق نشاط العائلة.'],
        ];

        foreach ($items as $i => $item) {
            $id = Database::insert('archive_items', [
                'title' => $item['title'],
                'slug' => Support\Str::slug($item['title'], 'arc'),
                'description' => $item['desc'],
                'cover_media_id' => $images ? $images[$i % count($images)] : null,
                'category_id' => $categoryIds ? $categoryIds[$i % count($categoryIds)] : null,
                'period_label' => $item['period'],
                'source' => $item['source'],
                'tags' => 'تجريبي',
                'status' => 'published',
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ]);
            self::log('archive_items', $id);
        }

        return count($items);
    }

    private static function seedAnnouncements(): int
    {
        $announcements = [
            [
                'title' => 'إعلان تجريبي في الصفحة الرئيسية',
                'message' => 'هذا إعلان تجريبي يظهر كبطاقة في الصفحة الرئيسية لتوضيح مكان الإعلانات.',
                'type' => 'رسالة من الإدارة',
                'placement' => 'home_card',
            ],
            [
                'title' => 'شريط تنبيه تجريبي',
                'message' => 'مثال للشريط العلوي الذي يظهر أعلى كل صفحات الموقع ويمكن للزائر إغلاقه.',
                'type' => 'تنبيه عام',
                'placement' => 'top_bar',
            ],
            [
                'title' => 'نافذة ترحيبية تجريبية',
                'message' => 'مثال للنافذة المنبثقة التي تظهر للزائر مرة واحدة عند دخول الموقع.',
                'type' => 'رسالة من الإدارة',
                'placement' => 'popup',
            ],
        ];

        foreach ($announcements as $a) {
            $id = Database::insert('announcements', [
                'title' => $a['title'],
                'message' => $a['message'],
                'announcement_type' => $a['type'],
                'placement' => $a['placement'],
                'status' => 'active',
                'popup_frequency' => 'once',
                'show_on_desktop' => 1,
                'show_on_mobile' => 1,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ]);
            self::log('announcements', $id);
        }

        return count($announcements);
    }
}
