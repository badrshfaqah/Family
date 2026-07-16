<?php

require_once __DIR__ . '/../core/bootstrap.php';

use Core\ModuleManager;
use Core\Router;
use Core\Support\Url;

if (!APP_INSTALLED) {
    header('Location: ' . Url::siteBase() . '/install/');
    exit;
}

$router = new Router();

require CORE_PATH . '/Admin/routes.php';

ModuleManager::boot($router, 'admin');

$router->dispatch();
