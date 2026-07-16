<?php

use Core\Front\Controllers\BranchFrontController;
use Core\Front\Controllers\CaptchaController;
use Core\Front\Controllers\HomeController;
use Core\Front\Controllers\SearchController;
use Core\Front\Controllers\SitemapController;
use Core\Settings;
use Core\Terms;
use Core\View;

View::share('terms', Terms::class);
View::share('identity', [
    'official_name' => Settings::get('identity_official_name', ''),
    'short_name' => Settings::get('identity_short_name', ''),
    'logo_media_id' => Settings::get('identity_logo_media_id', ''),
    'brief' => Settings::get('identity_brief', ''),
]);

/** @var Router $router */
$router->get('/', [HomeController::class, 'index']);
$router->get('/branches', [BranchFrontController::class, 'index']);
$router->get('/branches/{id}', [BranchFrontController::class, 'show']);
$router->get('/search', [SearchController::class, 'index']);
$router->get('/sitemap.xml', [SitemapController::class, 'index']);
$router->get('/robots.txt', [SitemapController::class, 'robots']);
$router->get('/captcha.png', [CaptchaController::class, 'image']);

$router->setNotFound(function () {
    http_response_code(404);
    require CORE_PATH . '/Front/Views/404.php';
});
