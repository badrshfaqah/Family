<?php

namespace Modules\Directory;

use Core\AbstractModule;
use Core\Router;
use Core\Support\Url;
use Core\Terms;
use Modules\Directory\Admin\Controllers\DirectoryAdminController;
use Modules\Directory\Admin\Controllers\RemovalRequestsAdminController;
use Modules\Directory\Admin\Controllers\TemplatesAdminController;
use Modules\Directory\Front\Controllers\DirectoryFrontController;

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
            $pdo->exec('DROP TABLE IF EXISTS ' . \Core\Database::table('directory_removal_requests'));
            $pdo->exec('DROP TABLE IF EXISTS ' . \Core\Database::table('directory_message_templates'));
            $pdo->exec('DROP TABLE IF EXISTS ' . \Core\Database::table('directory_contacts'));
        }
    }

    public function registerAdminRoutes(Router $router): void
    {
        // سجلات جوال العائلة
        $router->get('/directory', [DirectoryAdminController::class, 'index']);
        $router->get('/directory/create', [DirectoryAdminController::class, 'create']);
        $router->post('/directory/store', [DirectoryAdminController::class, 'store']);
        $router->get('/directory/{id}/edit', [DirectoryAdminController::class, 'edit']);
        $router->post('/directory/{id}/update', [DirectoryAdminController::class, 'update']);
        $router->post('/directory/{id}/delete', [DirectoryAdminController::class, 'delete']);
        $router->post('/directory/{id}/toggle-status', [DirectoryAdminController::class, 'toggleStatus']);

        // التكرارات
        $router->get('/directory/duplicates', [DirectoryAdminController::class, 'duplicates']);
        $router->post('/directory/duplicates/merge', [DirectoryAdminController::class, 'mergeDuplicates']);

        // استيراد/تصدير CSV
        $router->get('/directory/import', [DirectoryAdminController::class, 'importForm']);
        $router->post('/directory/import/preview', [DirectoryAdminController::class, 'importPreview']);
        $router->post('/directory/import/commit', [DirectoryAdminController::class, 'importCommit']);
        $router->get('/directory/template.csv', [DirectoryAdminController::class, 'templateCsv']);
        $router->get('/directory/export.csv', [DirectoryAdminController::class, 'exportCsv']);

        // طلبات إلغاء الاشتراك
        $router->get('/directory/removal-requests', [RemovalRequestsAdminController::class, 'index']);
        $router->post('/directory/removal-requests/{id}/approve', [RemovalRequestsAdminController::class, 'approve']);
        $router->post('/directory/removal-requests/{id}/reject', [RemovalRequestsAdminController::class, 'reject']);

        // قوالب الرسائل
        $router->get('/directory-templates', [TemplatesAdminController::class, 'index']);
        $router->get('/directory-templates/create', [TemplatesAdminController::class, 'create']);
        $router->post('/directory-templates/store', [TemplatesAdminController::class, 'store']);
        $router->get('/directory-templates/{id}/edit', [TemplatesAdminController::class, 'edit']);
        $router->post('/directory-templates/{id}/update', [TemplatesAdminController::class, 'update']);
        $router->post('/directory-templates/{id}/copy', [TemplatesAdminController::class, 'copy']);
        $router->post('/directory-templates/{id}/delete', [TemplatesAdminController::class, 'delete']);
        $router->get('/directory-templates/{id}/preview', [TemplatesAdminController::class, 'preview']);
    }

    public function registerPublicRoutes(Router $router): void
    {
        $router->get('/directory/register', [DirectoryFrontController::class, 'registerForm']);
        $router->post('/directory/register', [DirectoryFrontController::class, 'registerSubmit']);
        $router->get('/directory/manage-subscription', [DirectoryFrontController::class, 'manageSubscriptionForm']);
        $router->post('/directory/manage-subscription', [DirectoryFrontController::class, 'manageSubscriptionSubmit']);
    }

    public function adminMenu(): array
    {
        return [
            ['label' => Terms::phrase('family_directory'), 'url' => Url::admin('directory'), 'icon' => '📱', 'perm' => 'directory.view'],
            ['label' => 'طلبات إلغاء الاشتراك', 'url' => Url::admin('directory/removal-requests'), 'icon' => '🚫', 'perm' => 'directory.manage'],
            ['label' => 'قوالب الرسائل', 'url' => Url::admin('directory-templates'), 'icon' => '✉️', 'perm' => 'templates.manage'],
        ];
    }

    public function permissions(): array
    {
        // الصلاحيات (directory.view / directory.manage / directory.export / templates.manage)
        // مسجلة مسبقًا في كتالوج النواة (core/Permissions.php) ضمن مجموعة "التواصل"، فلا داعي لتكرارها هنا.
        return [];
    }
}
