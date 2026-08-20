<?php

namespace Modules\Api;

use Core\AbstractModule;
use Core\Router;
use Modules\Api\Front\Controllers\ApiController;

final class Module extends AbstractModule
{
    public function install(): void
    {
        // لا جداول خاصة بهذه الإضافة — تقرأ من جداول الإضافات الأخرى فقط.
    }

    public function registerPublicRoutes(Router $router): void
    {
        $router->get('/api/v1/app', [ApiController::class, 'app']);
        $router->get('/api/v1/home', [ApiController::class, 'home']);
        $router->get('/api/v1/news', [ApiController::class, 'newsIndex']);
        $router->get('/api/v1/news/{slug}', [ApiController::class, 'newsShow']);
        $router->get('/api/v1/calendar', [ApiController::class, 'calendar']);
        $router->get('/api/v1/gatherings', [ApiController::class, 'gatherings']);
        $router->get('/api/v1/gallery', [ApiController::class, 'galleryIndex']);
        $router->get('/api/v1/gallery/{slug}', [ApiController::class, 'galleryShow']);
        $router->get('/api/v1/obituaries', [ApiController::class, 'obituaries']);
        $router->get('/api/v1/poetry', [ApiController::class, 'poetryIndex']);
        $router->get('/api/v1/poetry/{id}', [ApiController::class, 'poetryShow']);
        $router->get('/api/v1/archive', [ApiController::class, 'archiveIndex']);
        $router->get('/api/v1/archive/{slug}', [ApiController::class, 'archiveShow']);
        $router->get('/api/v1/tree', [ApiController::class, 'tree']);
        $router->get('/api/v1/pages', [ApiController::class, 'pagesIndex']);
        $router->get('/api/v1/pages/{slug}', [ApiController::class, 'pagesShow']);
        $router->get('/api/v1/announcements', [ApiController::class, 'announcements']);
        $router->get('/api/v1/search', [ApiController::class, 'search']);
        $router->get('/api/v1/directory/info', [ApiController::class, 'directoryInfo']);
        $router->post('/api/v1/directory/register', [ApiController::class, 'directoryRegister']);
        $router->post('/api/v1/directory/remove', [ApiController::class, 'directoryRemove']);
    }
}
