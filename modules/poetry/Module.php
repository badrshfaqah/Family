<?php

namespace Modules\Poetry;

use Core\AbstractModule;
use Core\Router;
use Core\Support\Url;
use Modules\Poetry\Admin\Controllers\PoetryAdminController;
use Modules\Poetry\Front\Controllers\PoetryFrontController;

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
            $pdo->exec('DROP TABLE IF EXISTS ' . \Core\Database::table('poems'));
            $pdo->exec('DROP TABLE IF EXISTS ' . \Core\Database::table('poets'));
        }
    }

    public function registerAdminRoutes(Router $router): void
    {
        $router->get('/poetry', [PoetryAdminController::class, 'index']);
        $router->get('/poetry/create', [PoetryAdminController::class, 'create']);
        $router->post('/poetry/store', [PoetryAdminController::class, 'store']);
        $router->get('/poetry/{id}/edit', [PoetryAdminController::class, 'edit']);
        $router->post('/poetry/{id}/update', [PoetryAdminController::class, 'update']);
        $router->post('/poetry/{id}/delete', [PoetryAdminController::class, 'delete']);

        $router->get('/poems/create', [PoetryAdminController::class, 'poemCreate']);
        $router->post('/poems/store', [PoetryAdminController::class, 'poemStore']);
        $router->get('/poems/{id}/edit', [PoetryAdminController::class, 'poemEdit']);
        $router->post('/poems/{id}/update', [PoetryAdminController::class, 'poemUpdate']);
        $router->post('/poems/{id}/delete', [PoetryAdminController::class, 'poemDelete']);
    }

    public function registerPublicRoutes(Router $router): void
    {
        $router->get('/poetry', [PoetryFrontController::class, 'index']);
        $router->get('/poetry/{id}', [PoetryFrontController::class, 'poet']);
        $router->get('/poems/{id}', [PoetryFrontController::class, 'poem']);
    }

    public function adminMenu(): array
    {
        return [
            ['label' => 'الشعر', 'url' => Url::admin('poetry'), 'icon' => '🪶', 'perm' => 'content.poetry'],
        ];
    }

    public function permissions(): array
    {
        return ['content.poetry' => 'إدارة الشعراء والقصائد'];
    }
}
