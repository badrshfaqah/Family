<?php

require_once __DIR__ . '/core/bootstrap.php';

use Core\ModuleManager;
use Core\Router;
use Core\Settings;
use Core\Support\Url;

if (!APP_INSTALLED) {
    header('Location: ' . Url::siteBase() . '/install/');
    exit;
}

if (Settings::get('maintenance_mode', '0') === '1' && !\Core\Auth::check()) {
    http_response_code(503);
    require CORE_PATH . '/Front/Views/maintenance.php';
    exit;
}

// تسجيل الزيارة لإحصائيات لوحة التحكم (مجهّلة الهوية، ولا تؤثر على الصفحة)
\Core\Analytics::track();

$router = new Router();

require CORE_PATH . '/Front/routes.php';

ModuleManager::boot($router, 'public');

$router->dispatch();
