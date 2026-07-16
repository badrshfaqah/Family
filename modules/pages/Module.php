<?php

namespace Modules\Pages;

use Core\AbstractModule;
use Core\Router;
use Core\Support\Url;
use Modules\Pages\Admin\Controllers\PagesAdminController;
use Modules\Pages\Front\Controllers\PageFrontController;

final class Module extends AbstractModule
{
    public function install(): void
    {
        $this->runSqlFile(__DIR__ . '/install.sql');
    }

    public function uninstall(bool $keepData): void
    {
        if (!$keepData) {
            \Core\Database::pdo()->exec('DROP TABLE IF EXISTS ' . \Core\Database::table('pages'));
        }
    }

    public function registerAdminRoutes(Router $router): void
    {
        $router->get('/pages', [PagesAdminController::class, 'index']);
        $router->get('/pages/create', [PagesAdminController::class, 'create']);
        $router->post('/pages/store', [PagesAdminController::class, 'store']);
        $router->get('/pages/{id}/edit', [PagesAdminController::class, 'edit']);
        $router->post('/pages/{id}/update', [PagesAdminController::class, 'update']);
        $router->post('/pages/{id}/delete', [PagesAdminController::class, 'delete']);
    }

    public function registerPublicRoutes(Router $router): void
    {
        $router->get('/{slug}', [PageFrontController::class, 'show']);
    }

    public function adminMenu(): array
    {
        return [
            ['label' => 'الصفحات', 'url' => Url::admin('pages'), 'icon' => '📄', 'perm' => 'content.pages'],
        ];
    }

    public function permissions(): array
    {
        // صلاحية content.pages معرّفة ضمن كتالوج الصلاحيات الأساسي في النواة (تُستخدم أيضًا للقوائم والمدن والفروع)
        return [];
    }
}
