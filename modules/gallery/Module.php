<?php

namespace Modules\Gallery;

use Core\AbstractModule;
use Core\Router;
use Core\Support\Url;
use Modules\Gallery\Admin\Controllers\GalleryAdminController;
use Modules\Gallery\Front\Controllers\GalleryFrontController;

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
            $pdo->exec('DROP TABLE IF EXISTS ' . \Core\Database::table('gallery_photos'));
            $pdo->exec('DROP TABLE IF EXISTS ' . \Core\Database::table('gallery_albums'));
        }
    }

    public function registerAdminRoutes(Router $router): void
    {
        $router->get('/gallery', [GalleryAdminController::class, 'index']);
        $router->get('/gallery/create', [GalleryAdminController::class, 'create']);
        $router->post('/gallery/store', [GalleryAdminController::class, 'store']);
        $router->get('/gallery/{id}/edit', [GalleryAdminController::class, 'edit']);
        $router->post('/gallery/{id}/update', [GalleryAdminController::class, 'update']);
        $router->post('/gallery/{id}/delete', [GalleryAdminController::class, 'delete']);
        $router->post('/gallery/{id}/photos/store', [GalleryAdminController::class, 'photosStore']);
        $router->post('/gallery-photos/{id}/update', [GalleryAdminController::class, 'photoUpdate']);
        $router->post('/gallery-photos/{id}/delete', [GalleryAdminController::class, 'photoDelete']);
    }

    public function registerPublicRoutes(Router $router): void
    {
        $router->get('/gallery', [GalleryFrontController::class, 'index']);
        $router->get('/gallery/{slug}', [GalleryFrontController::class, 'show']);
    }

    public function adminMenu(): array
    {
        return [
            ['label' => 'المعرض', 'url' => Url::admin('gallery'), 'icon' => '🖼️', 'perm' => 'content.gallery'],
        ];
    }

    public function permissions(): array
    {
        return ['content.gallery' => 'إدارة المعرض'];
    }
}
