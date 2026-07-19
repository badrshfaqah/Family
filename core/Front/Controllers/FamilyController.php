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

        // صفحة مستقلة بهوية خاصة (مادة تسويقية للبرنامج) — لا تستخدم قالب موقع التركيب
        echo View::render(CORE_PATH . '/Front/Views/family-landing.php', [
            'coreVersion' => defined('CORE_VERSION') ? CORE_VERSION : '',
            'modules' => $modules,
            'installedMap' => $installedMap,
            'changelogHtml' => $changelogHtml,
            'siteBase' => \Core\Support\Url::to(''),
        ]);
    }
}
