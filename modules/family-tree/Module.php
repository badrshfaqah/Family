<?php

namespace Modules\FamilyTree;

use Core\AbstractModule;
use Core\Router;
use Core\Support\Url;
use Modules\FamilyTree\Admin\Controllers\TreeAdminController;
use Modules\FamilyTree\Front\Controllers\FamilyTreeFrontController;

final class Module extends AbstractModule
{
    public function install(): void
    {
        // جمل CREATE TABLE في MySQL تُنفذ التزامًا ضمنيًا لذلك لا تُلف هذه الدالة بمعاملة.
        $this->runSqlFile(__DIR__ . '/install.sql');
    }

    public function uninstall(bool $keepData): void
    {
        if (!$keepData) {
            \Core\Database::pdo()->exec('DROP TABLE IF EXISTS ' . \Core\Database::table('tree_nodes'));
        }
    }

    public function registerAdminRoutes(Router $router): void
    {
        $router->get('/tree-nodes', [TreeAdminController::class, 'index']);
        $router->post('/tree-nodes/image', [TreeAdminController::class, 'saveImage']);
        $router->get('/tree-nodes/create', [TreeAdminController::class, 'create']);
        $router->post('/tree-nodes/store', [TreeAdminController::class, 'store']);
        $router->get('/tree-nodes/{id}/edit', [TreeAdminController::class, 'edit']);
        $router->post('/tree-nodes/{id}/update', [TreeAdminController::class, 'update']);
        $router->post('/tree-nodes/{id}/delete', [TreeAdminController::class, 'delete']);
    }

    public function registerPublicRoutes(Router $router): void
    {
        $router->get('/tree', [FamilyTreeFrontController::class, 'index']);
    }

    public function adminMenu(): array
    {
        return [
            ['label' => 'شجرة النسب', 'url' => Url::admin('tree-nodes'), 'icon' => '🌳', 'perm' => 'content.tree'],
        ];
    }

    public function permissions(): array
    {
        // content.tree صلاحية أساسية مسجّلة مسبقًا في كتالوج النواة (core/Permissions.php)
        // وتُستخدم أيضًا من BranchesController، لذا لا داعي لتكرار تسجيلها هنا.
        return [];
    }
}
