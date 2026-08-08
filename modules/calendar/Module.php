<?php

namespace Modules\Calendar;

use Core\AbstractModule;
use Core\Router;
use Core\Support\Url;
use Modules\Calendar\Admin\Controllers\CalendarAdminController;
use Modules\Calendar\Front\Controllers\CalendarFrontController;

final class Module extends AbstractModule
{
    public function install(): void
    {
        $this->runSqlFile(__DIR__ . '/install.sql');
    }

    public function uninstall(bool $keepData): void
    {
        if (!$keepData) {
            \Core\Database::pdo()->exec('DROP TABLE IF EXISTS ' . \Core\Database::table('calendar_entries'));
        }
    }

    public function registerAdminRoutes(Router $router): void
    {
        $router->get('/calendar', [CalendarAdminController::class, 'index']);
        $router->get('/calendar/create', [CalendarAdminController::class, 'create']);
        $router->post('/calendar/store', [CalendarAdminController::class, 'store']);
        $router->get('/calendar/{id}/edit', [CalendarAdminController::class, 'edit']);
        $router->post('/calendar/{id}/update', [CalendarAdminController::class, 'update']);
        $router->post('/calendar/{id}/delete', [CalendarAdminController::class, 'delete']);
        $router->post('/calendar/{id}/approve', [CalendarAdminController::class, 'approve']);
        $router->post('/calendar/{id}/reject', [CalendarAdminController::class, 'reject']);
    }

    public function registerPublicRoutes(Router $router): void
    {
        $router->get('/calendar', [CalendarFrontController::class, 'index']);
        $router->get('/calendar/suggest', [CalendarFrontController::class, 'suggestForm']);
        $router->post('/calendar/suggest', [CalendarFrontController::class, 'suggestSubmit']);
        $router->get('/calendar/{id}', [CalendarFrontController::class, 'show']);
    }

    public function adminMenu(): array
    {
        return [
            ['label' => 'رزنامة المناسبات', 'url' => Url::admin('calendar'), 'icon' => '📅', 'perm' => 'content.calendar'],
        ];
    }

    public function permissions(): array
    {
        return ['content.calendar' => 'إدارة رزنامة المناسبات'];
    }
}
