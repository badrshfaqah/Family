<?php

namespace Core\Admin\Controllers;

use Core\Auth;
use Core\ModuleManager;
use Core\Support\Csrf;
use Core\Support\Request;
use Core\Support\Response;
use Core\Support\Session;
use Core\Support\Str;
use Core\Support\Url;
use Core\View;

final class ModulesController
{
    public function index(array $params): void
    {
        Auth::requirePermission('system.modules');

        $manifests = ModuleManager::discover();
        $installed = ModuleManager::installedMap();

        $modules = [];
        foreach ($manifests as $slug => $manifest) {
            $modules[$slug] = [
                'manifest' => $manifest,
                'row' => $installed[$slug] ?? null,
            ];
        }

        echo View::render(CORE_PATH . '/Admin/Views/layouts/admin.php', [
            'pageTitle' => 'الإضافات',
            'contentView' => CORE_PATH . '/Admin/Views/modules/index.php',
            'modules' => $modules,
            'zipSupported' => class_exists('ZipArchive'),
        ]);
    }

    public function install(array $params): void
    {
        Auth::requirePermission('system.modules');
        Csrf::verifyRequestOrFail();

        $result = ModuleManager::install($params['slug']);
        Session::flash($result['ok'] ? 'success' : 'error', $result['message']);
        Response::redirect(Url::admin('modules'));
    }

    public function enable(array $params): void
    {
        Auth::requirePermission('system.modules');
        Csrf::verifyRequestOrFail();
        $result = ModuleManager::setStatus($params['slug'], 'enabled');
        Session::flash($result['ok'] ? 'success' : 'error', $result['message']);
        Response::redirect(Url::admin('modules'));
    }

    public function disable(array $params): void
    {
        Auth::requirePermission('system.modules');
        Csrf::verifyRequestOrFail();
        $result = ModuleManager::setStatus($params['slug'], 'disabled');
        Session::flash($result['ok'] ? 'success' : 'error', $result['message']);
        Response::redirect(Url::admin('modules'));
    }

    public function uninstall(array $params): void
    {
        Auth::requirePermission('system.modules');
        Csrf::verifyRequestOrFail();
        $keepData = Request::bool('keep_data');
        $result = ModuleManager::uninstall($params['slug'], $keepData);
        Session::flash($result['ok'] ? 'success' : 'error', $result['message']);
        Response::redirect(Url::admin('modules'));
    }

    public function upload(array $params): void
    {
        Auth::requirePermission('system.modules');
        Csrf::verifyRequestOrFail();

        if (!class_exists('ZipArchive')) {
            Session::flash('error', 'مكتبة ZipArchive غير متوفرة على هذا السيرفر، الرجاء رفع الإضافة عبر مدير الملفات مباشرة إلى مجلد modules.');
            Response::redirect(Url::admin('modules'));
        }

        $file = Request::file('module_zip');
        if (!$file) {
            Session::flash('error', 'الرجاء اختيار ملف ZIP للإضافة.');
            Response::redirect(Url::admin('modules'));
        }

        $tmpExtractDir = STORAGE_PATH . '/temp/module-' . Str::random(8);
        mkdir($tmpExtractDir, 0755, true);

        $zip = new \ZipArchive();
        if ($zip->open($file['tmp_name']) !== true) {
            Session::flash('error', 'تعذر فتح ملف ZIP، تأكد من أنه غير تالف.');
            Response::redirect(Url::admin('modules'));
        }
        $zip->extractTo($tmpExtractDir);
        $zip->close();

        $manifestPath = $this->findManifest($tmpExtractDir);
        if (!$manifestPath) {
            $this->rrmdir($tmpExtractDir);
            Session::flash('error', 'ملف module.json غير موجود داخل الحزمة المرفوعة.');
            Response::redirect(Url::admin('modules'));
        }

        $manifest = json_decode((string) file_get_contents($manifestPath), true);
        if (!is_array($manifest) || empty($manifest['slug'])) {
            $this->rrmdir($tmpExtractDir);
            Session::flash('error', 'ملف module.json غير صالح.');
            Response::redirect(Url::admin('modules'));
        }

        $slug = preg_replace('/[^a-z0-9\-]/', '', strtolower($manifest['slug']));
        $target = ModuleManager::modulesPath() . '/' . $slug;

        if (is_dir($target)) {
            $this->rrmdir($tmpExtractDir);
            Session::flash('error', 'توجد إضافة أخرى بنفس المعرف مسبقًا.');
            Response::redirect(Url::admin('modules'));
        }

        rename(dirname($manifestPath), $target);
        $this->rrmdir($tmpExtractDir);

        Session::flash('success', 'تم رفع الإضافة بنجاح. يمكنك الآن تثبيتها من القائمة أدناه.');
        Response::redirect(Url::admin('modules'));
    }

    private function findManifest(string $dir): ?string
    {
        $direct = $dir . '/module.json';
        if (is_file($direct)) {
            return $direct;
        }
        foreach (glob($dir . '/*', GLOB_ONLYDIR) ?: [] as $sub) {
            $candidate = $sub . '/module.json';
            if (is_file($candidate)) {
                return $candidate;
            }
        }
        return null;
    }

    private function rrmdir(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }
        $items = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($dir, \FilesystemIterator::SKIP_DOTS), \RecursiveIteratorIterator::CHILD_FIRST);
        foreach ($items as $item) {
            $item->isDir() ? @rmdir($item->getPathname()) : @unlink($item->getPathname());
        }
        @rmdir($dir);
    }
}
