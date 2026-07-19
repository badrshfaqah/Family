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
    private const MAX_ZIP_BYTES = 20 * 1024 * 1024;
    private const MAX_EXTRACTED_BYTES = 100 * 1024 * 1024;
    private const MAX_ZIP_ENTRIES = 2000;

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
            'moduleUploadEnabled' => defined('ALLOW_MODULE_UPLOAD') && ALLOW_MODULE_UPLOAD === true,
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

        if (!defined('ALLOW_MODULE_UPLOAD') || ALLOW_MODULE_UPLOAD !== true) {
            Session::flash('error', 'رفع الإضافات معطّل في إعدادات الإنتاج. فعّله صراحةً فقط عند رفع حزمة موثوقة.');
            Response::redirect(Url::admin('modules'));
        }

        if (!class_exists('ZipArchive')) {
            Session::flash('error', 'مكتبة ZipArchive غير متوفرة على هذا السيرفر، الرجاء رفع الإضافة عبر مدير الملفات مباشرة إلى مجلد modules.');
            Response::redirect(Url::admin('modules'));
        }

        $file = Request::file('module_zip');
        if (!$file) {
            Session::flash('error', 'الرجاء اختيار ملف ZIP للإضافة.');
            Response::redirect(Url::admin('modules'));
        }

        if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK
            || (int) ($file['size'] ?? 0) < 1
            || (int) $file['size'] > self::MAX_ZIP_BYTES
            || strtolower(pathinfo((string) $file['name'], PATHINFO_EXTENSION)) !== 'zip') {
            Session::flash('error', 'حزمة الإضافة غير صالحة أو يتجاوز حجمها 20 ميجابايت.');
            Response::redirect(Url::admin('modules'));
        }

        $tmpExtractDir = STORAGE_PATH . '/temp/module-' . Str::random(8);
        mkdir($tmpExtractDir, 0755, true);

        $zip = new \ZipArchive();
        if ($zip->open($file['tmp_name']) !== true) {
            Session::flash('error', 'تعذر فتح ملف ZIP، تأكد من أنه غير تالف.');
            Response::redirect(Url::admin('modules'));
        }

        try {
            $this->validateArchive($zip);
            if (!$zip->extractTo($tmpExtractDir)) {
                throw new \RuntimeException('تعذر استخراج حزمة الإضافة.');
            }
        } catch (\Throwable $e) {
            $zip->close();
            $this->rrmdir($tmpExtractDir);
            Session::flash('error', $e->getMessage());
            Response::redirect(Url::admin('modules'));
        }
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
        if ($slug === '' || $slug !== strtolower((string) $manifest['slug'])) {
            $this->rrmdir($tmpExtractDir);
            Session::flash('error', 'معرّف الإضافة غير صالح. استخدم أحرفًا إنجليزية صغيرة وأرقامًا وشرطة فقط.');
            Response::redirect(Url::admin('modules'));
        }
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

    private function validateArchive(\ZipArchive $zip): void
    {
        if ($zip->numFiles < 1 || $zip->numFiles > self::MAX_ZIP_ENTRIES) {
            throw new \RuntimeException('عدد الملفات داخل الحزمة غير مسموح به.');
        }

        $expandedBytes = 0;
        for ($i = 0; $i < $zip->numFiles; $i++) {
            $stat = $zip->statIndex($i);
            $name = str_replace('\\', '/', (string) ($stat['name'] ?? ''));
            $parts = explode('/', $name);
            if ($name === '' || str_contains($name, "\0") || str_starts_with($name, '/')
                || preg_match('/^[a-zA-Z]:\//', $name) || in_array('..', $parts, true)) {
                throw new \RuntimeException('تحتوي الحزمة على مسار ملف غير آمن.');
            }

            $expandedBytes += (int) ($stat['size'] ?? 0);
            if ($expandedBytes > self::MAX_EXTRACTED_BYTES) {
                throw new \RuntimeException('الحجم المستخرج للحزمة يتجاوز الحد المسموح.');
            }

            $opsys = 0;
            $attributes = 0;
            if ($zip->getExternalAttributesIndex($i, $opsys, $attributes)) {
                $fileType = ($attributes >> 16) & 0170000;
                if ($fileType === 0120000) {
                    throw new \RuntimeException('لا يُسمح بالروابط الرمزية داخل حزمة الإضافة.');
                }
            }
        }
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
