<?php

namespace Modules\News;

use Core\AbstractModule;
use Core\Router;
use Core\Support\Url;
use Modules\News\Admin\Controllers\NewsAdminController;
use Modules\News\Front\Controllers\NewsFrontController;

final class Module extends AbstractModule
{
    public function install(): void
    {
        $this->runSqlFile(__DIR__ . '/install.sql');
    }

    public function uninstall(bool $keepData): void
    {
        if (!$keepData) {
            $pdo = \Core\Database::pdo();
            $pdo->exec('DROP TABLE IF EXISTS ' . \Core\Database::table('news'));
            $pdo->exec('DROP TABLE IF EXISTS ' . \Core\Database::table('news_categories'));
        }
    }

    public function registerAdminRoutes(Router $router): void
    {
        $router->get('/news', [NewsAdminController::class, 'index']);
        $router->get('/news/create', [NewsAdminController::class, 'create']);
        $router->post('/news/store', [NewsAdminController::class, 'store']);
        $router->get('/news/{id}/edit', [NewsAdminController::class, 'edit']);
        $router->post('/news/{id}/update', [NewsAdminController::class, 'update']);
        $router->post('/news/{id}/delete', [NewsAdminController::class, 'delete']);
        $router->get('/news-categories', [NewsAdminController::class, 'categories']);
        $router->post('/news-categories/store', [NewsAdminController::class, 'categoryStore']);
        $router->post('/news-categories/{id}/delete', [NewsAdminController::class, 'categoryDelete']);
    }

    public function registerPublicRoutes(Router $router): void
    {
        $router->get('/news', [NewsFrontController::class, 'index']);
        $router->get('/news/{slug}', [NewsFrontController::class, 'show']);
    }

    public function adminMenu(): array
    {
        return [
            ['label' => 'الأخبار', 'url' => Url::admin('news'), 'icon' => '📰', 'perm' => 'content.news'],
        ];
    }

    public function permissions(): array
    {
        return ['content.news' => 'إدارة الأخبار'];
    }
}
