<?php

namespace Core\Admin\Controllers;

use Core\Auth;
use Core\ModuleManager;
use Core\Support\Markdown;
use Core\View;

/**
 * صفحة "Family" التعريفية: تعرض مميزات النظام وإضافاته وسجل تحديثاته مباشرة
 * من مصادرها الحية (module.json لكل إضافة وCHANGELOG.md)، بحيث تتحدث الصفحة
 * تلقائيًا مع أي تحديث دون الحاجة لتكرار كتابة المحتوى في مكانين.
 */
final class FamilyController
{
    public function index(array $params): void
    {
        Auth::requireLogin();

        $modules = ModuleManager::discover();
        ksort($modules);

        $installedMap = ModuleManager::installedMap();

        $changelogPath = ROOT_PATH . '/CHANGELOG.md';
        $changelogHtml = '';
        if (is_file($changelogPath)) {
            $changelogHtml = Markdown::toHtml((string) file_get_contents($changelogPath));
        }

        echo View::render(CORE_PATH . '/Admin/Views/layouts/admin.php', [
            'pageTitle' => 'Family',
            'contentView' => CORE_PATH . '/Admin/Views/family/index.php',
            'coreVersion' => defined('CORE_VERSION') ? CORE_VERSION : '',
            'modules' => $modules,
            'installedMap' => $installedMap,
            'changelogHtml' => $changelogHtml,
        ]);
    }
}
