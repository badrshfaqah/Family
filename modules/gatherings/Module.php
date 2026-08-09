<?php

namespace Modules\Gatherings;

use Core\AbstractModule;
use Core\Router;
use Core\Support\Url;
use Modules\Gatherings\Admin\Controllers\GatheringsAdminController;
use Modules\Gatherings\Front\Controllers\GatheringsFrontController;

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
            $pdo->exec('DROP TABLE IF EXISTS ' . \Core\Database::table('gatherings'));
        }
    }

    public function registerAdminRoutes(Router $router): void
    {
        $router->get('/gatherings', [GatheringsAdminController::class, 'index']);
        $router->get('/gatherings/create', [GatheringsAdminController::class, 'create']);
        $router->post('/gatherings/store', [GatheringsAdminController::class, 'store']);
        $router->get('/gatherings/{id}/edit', [GatheringsAdminController::class, 'edit']);
        $router->post('/gatherings/{id}/update', [GatheringsAdminController::class, 'update']);
        $router->post('/gatherings/{id}/delete', [GatheringsAdminController::class, 'delete']);
        $router->post('/gatherings/{id}/move', [GatheringsAdminController::class, 'move']);
        $router->post('/gatherings/{id}/approve', [GatheringsAdminController::class, 'approve']);
        $router->post('/gatherings/{id}/reject', [GatheringsAdminController::class, 'reject']);
    }

    public function registerPublicRoutes(Router $router): void
    {
        $router->get('/gatherings', [GatheringsFrontController::class, 'index']);
        $router->get('/gatherings/suggest', [GatheringsFrontController::class, 'suggestForm']);
        $router->post('/gatherings/suggest', [GatheringsFrontController::class, 'suggestSubmit']);
        $router->get('/gatherings/{id}', [GatheringsFrontController::class, 'show']);
    }

    public function adminMenu(): array
    {
        return [
            ['label' => 'الجمعات', 'url' => Url::admin('gatherings'), 'icon' => '🤝', 'perm' => 'content.gatherings'],
        ];
    }

    public function permissions(): array
    {
        return ['content.gatherings' => 'إدارة الجمعات'];
    }
}
