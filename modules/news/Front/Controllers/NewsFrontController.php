<?php

namespace Modules\News\Front\Controllers;

use Core\Database;
use Core\Settings;
use Core\Support\Request;
use Core\View;

final class NewsFrontController
{
    public function index(array $params): void
    {
        $page = max(1, Request::int('page', 1));
        $perPage = 12;
        $offset = ($page - 1) * $perPage;

        $total = (int) Database::fetchValue(
            'SELECT COUNT(*) FROM ' . Database::table('news') . " WHERE status = 'published' AND published_at <= NOW()"
        );

        $rows = Database::fetchAll(
            'SELECT n.*, c.name AS category_name FROM ' . Database::table('news') . ' n
             LEFT JOIN ' . Database::table('news_categories') . ' c ON c.id = n.category_id
             WHERE n.status = "published" AND n.published_at <= NOW()
             ORDER BY n.is_pinned DESC, n.published_at DESC
             LIMIT ' . $perPage . ' OFFSET ' . $offset
        );

        $layout = CORE_PATH . '/Front/Views/layouts/main.php';
        $view = __DIR__ . '/../Views/index.php';

        echo View::renderLayout($layout, $view, [
            'rows' => $rows,
            'page' => $page,
            'perPage' => $perPage,
            'total' => $total,
            'pageTitle' => \Core\Terms::phrase('news'),
            'metaDescription' => Settings::get('seo_default_description', ''),
        ]);
    }

    public function show(array $params): void
    {
        $slug = $params['slug'] ?? '';

        $item = Database::fetchOne(
            'SELECT n.*, c.name AS category_name FROM ' . Database::table('news') . ' n
             LEFT JOIN ' . Database::table('news_categories') . ' c ON c.id = n.category_id
             WHERE n.slug = ? AND n.status = "published" AND n.published_at <= NOW()',
            [$slug]
        );

        if (!$item) {
            \Core\Front\NotFound::render();
            return;
        }

        $related = Database::fetchAll(
            'SELECT id, title, slug, cover_media_id FROM ' . Database::table('news') . '
             WHERE status = "published" AND published_at <= NOW() AND id != ? ' .
            ($item['category_id'] ? 'AND category_id = ' . (int) $item['category_id'] : '') . '
             ORDER BY published_at DESC LIMIT 3',
            [$item['id']]
        );

        $layout = CORE_PATH . '/Front/Views/layouts/main.php';
        $view = __DIR__ . '/../Views/show.php';

        // صورة الغلاف للمشاركة وبيانات Article المنظمة
        $ogImage = '';
        if (!empty($item['cover_media_id'])) {
            $media = Database::fetchOne('SELECT stored_path FROM ' . Database::table('media') . ' WHERE id = ?', [$item['cover_media_id']]);
            if ($media) {
                $ogImage = \Core\Support\Url::origin() . \Core\Media::url($media['stored_path']);
            }
        }

        $articleLd = array_filter([
            '@type' => 'NewsArticle',
            'headline' => $item['title'],
            'description' => $item['seo_description'] ?: $item['excerpt'],
            'datePublished' => date('c', strtotime($item['published_at'])),
            'dateModified' => date('c', strtotime($item['updated_at'] ?: $item['published_at'])),
            'image' => $ogImage ?: null,
            'author' => ['@type' => 'Organization', 'name' => $item['author_name'] ?: 'إدارة الموقع'],
            'inLanguage' => 'ar',
        ]);

        echo View::renderLayout($layout, $view, [
            'item' => $item,
            'related' => $related,
            'pageTitle' => $item['seo_title'] ?: $item['title'],
            'metaDescription' => $item['seo_description'] ?: $item['excerpt'],
            'ogImage' => $ogImage,
            'ogType' => 'article',
            'jsonLd' => $articleLd,
        ]);
    }
}
