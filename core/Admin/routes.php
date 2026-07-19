<?php

use Core\Admin\Controllers\AuthController;
use Core\Admin\Controllers\BackupController;
use Core\Admin\Controllers\BranchesController;
use Core\Admin\Controllers\CitiesController;
use Core\Admin\Controllers\DashboardController;
use Core\Admin\Controllers\DemoDataController;
use Core\Admin\Controllers\LogsController;
use Core\Admin\Controllers\MediaController;
use Core\Admin\Controllers\MenusController;
use Core\Admin\Controllers\ModulesController;
use Core\Admin\Controllers\RolesController;
use Core\Admin\Controllers\SettingsController;
use Core\Admin\Controllers\UsersController;

/** @var Router $router */

$router->get('/login', [AuthController::class, 'showLogin']);
$router->post('/login', [AuthController::class, 'login']);
$router->get('/logout', [AuthController::class, 'logout']);
$router->get('/forgot-password', [AuthController::class, 'showForgotPassword']);
$router->post('/forgot-password', [AuthController::class, 'sendResetLink']);
$router->get('/reset-password/{token}', [AuthController::class, 'showResetPassword']);
$router->post('/reset-password/{token}', [AuthController::class, 'updatePassword']);

$router->get('/', [DashboardController::class, 'index']);

$router->get('/settings', [SettingsController::class, 'index']);
$router->get('/settings/{tab}', [SettingsController::class, 'index']);
$router->post('/settings/{tab}/save', [SettingsController::class, 'save']);

$router->get('/users', [UsersController::class, 'index']);
$router->get('/users/create', [UsersController::class, 'create']);
$router->post('/users/store', [UsersController::class, 'store']);
$router->post('/users/{id}/toggle', [UsersController::class, 'toggleStatus']);
$router->post('/users/{id}/delete', [UsersController::class, 'delete']);

$router->get('/roles', [RolesController::class, 'index']);
$router->get('/roles/create', [RolesController::class, 'create']);
$router->post('/roles/store', [RolesController::class, 'store']);
$router->get('/roles/{id}/edit', [RolesController::class, 'edit']);
$router->post('/roles/{id}/update', [RolesController::class, 'update']);
$router->post('/roles/{id}/delete', [RolesController::class, 'delete']);

$router->get('/modules', [ModulesController::class, 'index']);
$router->post('/modules/upload', [ModulesController::class, 'upload']);
$router->post('/modules/{slug}/install', [ModulesController::class, 'install']);
$router->post('/modules/{slug}/enable', [ModulesController::class, 'enable']);
$router->post('/modules/{slug}/disable', [ModulesController::class, 'disable']);
$router->post('/modules/{slug}/uninstall', [ModulesController::class, 'uninstall']);

$router->get('/backup', [BackupController::class, 'index']);
$router->post('/backup/database', [BackupController::class, 'createDatabase']);
$router->post('/backup/files', [BackupController::class, 'createFiles']);
$router->post('/backup/restore', [BackupController::class, 'restore']);
$router->get('/backup/{file}/download', [BackupController::class, 'download']);
$router->post('/backup/{id}/delete', [BackupController::class, 'delete']);

$router->get('/logs', [LogsController::class, 'index']);
$router->get('/logs/users', [LogsController::class, 'userActivity']);

$router->get('/media', [MediaController::class, 'index']);
$router->post('/media/upload', [MediaController::class, 'upload']);
$router->post('/media/{id}/update', [MediaController::class, 'update']);
$router->post('/media/{id}/delete', [MediaController::class, 'delete']);

$router->get('/menus', [MenusController::class, 'index']);
$router->get('/menus/{slug}', [MenusController::class, 'index']);
$router->post('/menus/{slug}/store', [MenusController::class, 'store']);
$router->post('/menus/{slug}/reorder', [MenusController::class, 'reorder']);
$router->post('/menus/{id}/update', [MenusController::class, 'update']);
$router->post('/menus/{id}/delete', [MenusController::class, 'delete']);

$router->get('/cities', [CitiesController::class, 'index']);
$router->post('/cities/store', [CitiesController::class, 'store']);
$router->post('/cities/{id}/update', [CitiesController::class, 'update']);
$router->post('/cities/{id}/delete', [CitiesController::class, 'delete']);

$router->get('/branches', [BranchesController::class, 'index']);
$router->post('/branches/store', [BranchesController::class, 'store']);
$router->post('/branches/{id}/update', [BranchesController::class, 'update']);
$router->post('/branches/{id}/delete', [BranchesController::class, 'delete']);

$router->get('/demo-data', [DemoDataController::class, 'index']);
$router->post('/demo-data/seed', [DemoDataController::class, 'seed']);
$router->post('/demo-data/purge', [DemoDataController::class, 'purge']);
$router->post('/demo-data/enable-modules', [DemoDataController::class, 'enableModules']);

$router->setNotFound(function () {
    http_response_code(404);
    echo 'الصفحة غير موجودة في لوحة التحكم.';
});
