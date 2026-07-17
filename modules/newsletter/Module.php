<?php

namespace Modules\Newsletter;

use Core\AbstractModule;
use Core\Router;
use Core\Support\Url;
use Modules\Newsletter\Admin\Controllers\NewsletterAdminController;
use Modules\Newsletter\Front\Controllers\NewsletterFrontController;

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
            $pdo->exec('DROP TABLE IF EXISTS ' . \Core\Database::table('newsletter_subscribers'));
        }
    }

    public function registerAdminRoutes(Router $router): void
    {
        $router->get('/newsletter', [NewsletterAdminController::class, 'index']);
        $router->get('/newsletter/create', [NewsletterAdminController::class, 'create']);
        $router->post('/newsletter/store', [NewsletterAdminController::class, 'store']);
        $router->get('/newsletter/{id}/edit', [NewsletterAdminController::class, 'edit']);
        $router->post('/newsletter/{id}/update', [NewsletterAdminController::class, 'update']);
        $router->post('/newsletter/{id}/delete', [NewsletterAdminController::class, 'delete']);
        $router->post('/newsletter/bulk-category', [NewsletterAdminController::class, 'bulkCategory']);
        $router->get('/newsletter/export', [NewsletterAdminController::class, 'exportCsv']);
        $router->get('/newsletter/import', [NewsletterAdminController::class, 'importForm']);
        $router->post('/newsletter/import/preview', [NewsletterAdminController::class, 'importPreview']);
        $router->post('/newsletter/import/commit', [NewsletterAdminController::class, 'importCommit']);
    }

    public function registerPublicRoutes(Router $router): void
    {
        $router->get('/newsletter/subscribe', [NewsletterFrontController::class, 'subscribeForm']);
        $router->post('/newsletter/subscribe', [NewsletterFrontController::class, 'subscribe']);
        $router->get('/newsletter/unsubscribe', [NewsletterFrontController::class, 'unsubscribeForm']);
        $router->post('/newsletter/unsubscribe', [NewsletterFrontController::class, 'unsubscribe']);
    }

    public function adminMenu(): array
    {
        return [
            ['label' => 'القائمة البريدية', 'url' => Url::admin('newsletter'), 'icon' => '📧', 'perm' => 'newsletter.manage'],
        ];
    }

    public function permissions(): array
    {
        return ['newsletter.manage' => 'إدارة القائمة البريدية'];
    }
}
