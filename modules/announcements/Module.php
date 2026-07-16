<?php

namespace Modules\Announcements;

use Core\AbstractModule;
use Core\Router;
use Core\Support\Url;
use Modules\Announcements\Admin\Controllers\AnnouncementsAdminController;

final class Module extends AbstractModule
{
    public function install(): void
    {
        $this->runSqlFile(__DIR__ . '/install.sql');
    }

    public function uninstall(bool $keepData): void
    {
        if (!$keepData) {
            \Core\Database::pdo()->exec('DROP TABLE IF EXISTS ' . \Core\Database::table('announcements'));
        }
    }

    public function registerAdminRoutes(Router $router): void
    {
        $router->get('/announcements', [AnnouncementsAdminController::class, 'index']);
        $router->get('/announcements/create', [AnnouncementsAdminController::class, 'create']);
        $router->post('/announcements/store', [AnnouncementsAdminController::class, 'store']);
        $router->get('/announcements/{id}/edit', [AnnouncementsAdminController::class, 'edit']);
        $router->post('/announcements/{id}/update', [AnnouncementsAdminController::class, 'update']);
        $router->post('/announcements/{id}/delete', [AnnouncementsAdminController::class, 'delete']);
    }

    public function adminMenu(): array
    {
        return [
            ['label' => 'الإعلانات', 'url' => Url::admin('announcements'), 'icon' => '📢', 'perm' => 'content.announcements'],
        ];
    }

    public function permissions(): array
    {
        return ['content.announcements' => 'إدارة الإعلانات'];
    }
}
