<?php

namespace Modules\Obituaries;

use Core\AbstractModule;
use Core\Router;
use Core\Support\Url;
use Modules\Obituaries\Admin\Controllers\ObituariesAdminController;
use Modules\Obituaries\Front\Controllers\ObituariesFrontController;

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
            $pdo->exec('DROP TABLE IF EXISTS ' . \Core\Database::table('obituaries'));
        }
    }

    public function registerAdminRoutes(Router $router): void
    {
        $router->get('/obituaries', [ObituariesAdminController::class, 'index']);
        $router->get('/obituaries/create', [ObituariesAdminController::class, 'create']);
        $router->post('/obituaries/store', [ObituariesAdminController::class, 'store']);
        $router->get('/obituaries/{id}/edit', [ObituariesAdminController::class, 'edit']);
        $router->post('/obituaries/{id}/update', [ObituariesAdminController::class, 'update']);
        $router->post('/obituaries/{id}/delete', [ObituariesAdminController::class, 'delete']);
    }

    public function registerPublicRoutes(Router $router): void
    {
        $router->get('/obituaries', [ObituariesFrontController::class, 'index']);
        $router->get('/obituaries/{id}', [ObituariesFrontController::class, 'show']);
    }

    public function adminMenu(): array
    {
        return [
            ['label' => 'الوفيات', 'url' => Url::admin('obituaries'), 'icon' => '🕊', 'perm' => 'content.obituaries'],
        ];
    }

    public function permissions(): array
    {
        return ['content.obituaries' => 'إدارة إعلانات الوفيات'];
    }
}
