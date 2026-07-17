<?php

namespace Core\Front\Controllers;

use Core\ModuleManager;
use Core\Support\Markdown;
use Core\View;

/**
 * صفحة "Family" العامة: تعريف بالبرنامج ومميزاته وسجل تحديثاته، متاحة للجميع
 * دون تسجيل دخول. المحتوى مقروء مباشرة من مصادره الحية (module.json لكل
 * إضافة وCHANGELOG.md) بحيث تتحدث الصفحة تلقائيًا مع أي تحديث جديد.
 */
final class FamilyController
{
    public function index(array $params): void
    {
        $modules = ModuleManager::discover();
        ksort($modules);

        $installedMap = ModuleManager::installedMap();

        $changelogPath = ROOT_PATH . '/CHANGELOG.md';
        $changelogHtml = '';
        if (is_file($changelogPath)) {
            $changelogHtml = Markdown::toHtml((string) file_get_contents($changelogPath));
        }

        $layout = CORE_PATH . '/Front/Views/layouts/main.php';
        $view = ROOT_PATH . '/themes/default/templates/family.php';

        echo View::renderLayout($layout, $view, [
            'pageTitle' => 'Family — عن البرنامج',
            'metaDescription' => 'نبذة عن نظام إدارة محتوى العوائل والقبائل: مميزاته وإضافاته وسجل تحديثاته.',
            'coreVersion' => defined('CORE_VERSION') ? CORE_VERSION : '',
            'modules' => $modules,
            'installedMap' => $installedMap,
            'changelogHtml' => $changelogHtml,
        ]);
    }
}
