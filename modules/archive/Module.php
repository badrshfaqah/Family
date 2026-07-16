<?php

namespace Modules\Archive;

use Core\AbstractModule;
use Core\Router;
use Core\Support\Url;
use Modules\Archive\Admin\Controllers\ArchiveAdminController;
use Modules\Archive\Front\Controllers\ArchiveFrontController;

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
            $pdo->exec('DROP TABLE IF EXISTS ' . \Core\Database::table('archive_items'));
            $pdo->exec('DROP TABLE IF EXISTS ' . \Core\Database::table('archive_categories'));
        }
    }

    public function registerAdminRoutes(Router $router): void
    {
        $router->get('/archive', [ArchiveAdminController::class, 'index']);
        $router->get('/archive/create', [ArchiveAdminController::class, 'create']);
        $router->post('/archive/store', [ArchiveAdminController::class, 'store']);
        $router->get('/archive/{id}/edit', [ArchiveAdminController::class, 'edit']);
        $router->post('/archive/{id}/update', [ArchiveAdminController::class, 'update']);
        $router->post('/archive/{id}/delete', [ArchiveAdminController::class, 'delete']);
        $router->get('/archive-categories', [ArchiveAdminController::class, 'categories']);
        $router->post('/archive-categories/store', [ArchiveAdminController::class, 'categoryStore']);
        $router->post('/archive-categories/{id}/delete', [ArchiveAdminController::class, 'categoryDelete']);
    }

    public function registerPublicRoutes(Router $router): void
    {
        $router->get('/archive', [ArchiveFrontController::class, 'index']);
        $router->get('/archive/{slug}', [ArchiveFrontController::class, 'show']);
    }

    public function adminMenu(): array
    {
        return [
            ['label' => 'الأرشيف', 'url' => Url::admin('archive'), 'icon' => '🗄️', 'perm' => 'content.archive'],
        ];
    }

    public function permissions(): array
    {
        return ['content.archive' => 'إدارة الأرشيف'];
    }
}
