<?php

namespace Modules\Events;

use Core\AbstractModule;
use Core\Router;
use Core\Support\Url;
use Modules\Events\Admin\Controllers\EventsAdminController;
use Modules\Events\Front\Controllers\EventsFrontController;

final class Module extends AbstractModule
{
    public function install(): void
    {
        $this->runSqlFile(__DIR__ . '/install.sql');
    }

    public function uninstall(bool $keepData): void
    {
        if (!$keepData) {
            \Core\Database::pdo()->exec('DROP TABLE IF EXISTS ' . \Core\Database::table('events'));
        }
    }

    public function registerAdminRoutes(Router $router): void
    {
        $router->get('/events', [EventsAdminController::class, 'index']);
        $router->get('/events/create', [EventsAdminController::class, 'create']);
        $router->post('/events/store', [EventsAdminController::class, 'store']);
        $router->get('/events/{id}/edit', [EventsAdminController::class, 'edit']);
        $router->post('/events/{id}/update', [EventsAdminController::class, 'update']);
        $router->post('/events/{id}/delete', [EventsAdminController::class, 'delete']);
    }

    public function registerPublicRoutes(Router $router): void
    {
        $router->get('/events', [EventsFrontController::class, 'index']);
        $router->get('/events/{slug}', [EventsFrontController::class, 'show']);
    }

    public function adminMenu(): array
    {
        return [
            ['label' => 'المناسبات', 'url' => Url::admin('events'), 'icon' => '🎉', 'perm' => 'content.events'],
        ];
    }

    public function permissions(): array
    {
        return ['content.events' => 'إدارة المناسبات'];
    }
}
