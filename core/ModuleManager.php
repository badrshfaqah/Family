<?php

namespace Core;

/**
 * يكتشف الإضافات المتوفرة داخل مجلد modules، ويدير تثبيتها وتفعيلها وتعطيلها،
 * دون أن يؤدي تعطيل أي إضافة إلى تعطل بقية النظام.
 */
final class ModuleManager
{
    private static array $manifestCache = [];
    private static array $instanceCache = [];

    public static function modulesPath(): string
    {
        return ROOT_PATH . '/modules';
    }

    /** يكتشف جميع الإضافات الموجودة فعليًا في المجلد (سواء كانت مثبتة أم لا) */
    public static function discover(): array
    {
        if (self::$manifestCache) {
            return self::$manifestCache;
        }

        $modules = [];
        $dirs = glob(self::modulesPath() . '/*', GLOB_ONLYDIR) ?: [];

        foreach ($dirs as $dir) {
            $manifestFile = $dir . '/module.json';
            if (!is_file($manifestFile)) {
                continue;
            }
            $manifest = json_decode((string) file_get_contents($manifestFile), true);
            if (!is_array($manifest) || empty($manifest['slug'])) {
                continue;
            }
            $manifest['path'] = $dir;
            $modules[$manifest['slug']] = $manifest;
        }

        return self::$manifestCache = $modules;
    }

    public static function manifest(string $slug): ?array
    {
        return self::discover()[$slug] ?? null;
    }

    /** حالة الإضافات المثبتة من قاعدة البيانات: slug => row */
    public static function installedMap(): array
    {
        if (!Database::tableExists('modules')) {
            return [];
        }
        $rows = Database::fetchAll('SELECT * FROM ' . Database::table('modules'));
        $map = [];
        foreach ($rows as $row) {
            $map[$row['slug']] = $row;
        }
        return $map;
    }

    public static function isEnabled(string $slug): bool
    {
        $installed = self::installedMap();
        return isset($installed[$slug]) && $installed[$slug]['status'] === 'enabled';
    }

    public static function isInstalled(string $slug): bool
    {
        return isset(self::installedMap()[$slug]);
    }

    public static function instance(string $slug): ?ModuleContract
    {
        if (isset(self::$instanceCache[$slug])) {
            return self::$instanceCache[$slug];
        }

        $manifest = self::manifest($slug);
        if (!$manifest) {
            return null;
        }

        $namespace = $manifest['namespace'] ?? ('Modules\\' . self::studly($slug));
        \Autoloader::addNamespace($namespace, $manifest['path']);

        $class = $namespace . '\\Module';
        $moduleFile = $manifest['path'] . '/Module.php';
        if (!is_file($moduleFile)) {
            return null;
        }
        require_once $moduleFile;

        if (!class_exists($class)) {
            return null;
        }

        return self::$instanceCache[$slug] = new $class($manifest);
    }

    private static function studly(string $slug): string
    {
        return str_replace(' ', '', ucwords(str_replace(['-', '_'], ' ', $slug)));
    }

    public static function install(string $slug): array
    {
        $manifest = self::manifest($slug);
        if (!$manifest) {
            return ['ok' => false, 'message' => 'الإضافة غير موجودة.'];
        }

        if (self::isInstalled($slug)) {
            return ['ok' => false, 'message' => 'الإضافة مثبتة مسبقًا.'];
        }

        $module = self::instance($slug);
        if (!$module) {
            return ['ok' => false, 'message' => 'تعذر تحميل ملف الإضافة.'];
        }

        try {
            // لا يُلف تنفيذ install() ضمن معاملة (Transaction) لأن جمل DDL مثل
            // CREATE TABLE في MySQL تُنفذ التزامًا ضمنيًا (implicit commit) وتُفسد حالة المعاملة.
            $module->install();

            Database::insert('modules', [
                'slug' => $slug,
                'name' => $manifest['name'] ?? $slug,
                'version' => $manifest['version'] ?? '1.0.0',
                'status' => 'enabled',
                'installed_at' => date('Y-m-d H:i:s'),
            ]);

            foreach ($module->permissions() as $key => $label) {
                if (!Database::fetchValue('SELECT id FROM ' . Database::table('permissions_extra') . ' WHERE permission_key = ?', [$key])) {
                    Database::insert('permissions_extra', ['permission_key' => $key, 'label' => $label, 'module_slug' => $slug]);
                }
            }

            ActivityLog::record('module_install', 'تثبيت إضافة: ' . ($manifest['name'] ?? $slug), 'module', $slug);
            return ['ok' => true, 'message' => 'تم تثبيت الإضافة بنجاح.'];
        } catch (\Throwable $e) {
            \Core\Support\Logger::exception($e);
            return ['ok' => false, 'message' => 'حدث خطأ أثناء تثبيت الإضافة: ' . $e->getMessage()];
        }
    }

    /**
     * يوائم الإضافات مع ملفاتها بعد تحديث الكود (سحب من GitHub أو رفع يدوي):
     * يرحّل الإضافات المثبتة التي تغيّر إصدارها في module.json، ويثبّت
     * ويفعّل الإضافات المرفقة الجديدة عند تفعيل الخيار.
     * @return string[] وصف ما طُبق
     */
    public static function syncWithFilesystem(bool $installNew): array
    {
        $log = [];
        $installed = self::installedMap();

        foreach (self::discover() as $slug => $manifest) {
            $fileVersion = (string) ($manifest['version'] ?? '1.0.0');

            if (!isset($installed[$slug])) {
                if ($installNew) {
                    $result = self::install($slug);
                    $log[] = $result['ok']
                        ? 'تثبيت إضافة جديدة: ' . ($manifest['name'] ?? $slug) . " ({$fileVersion})"
                        : "تعذر تثبيت {$slug}: " . $result['message'];
                }
                continue;
            }

            $dbVersion = (string) ($installed[$slug]['version'] ?? '1.0.0');
            if ($dbVersion === $fileVersion) {
                continue;
            }

            try {
                $module = self::instance($slug);
                if ($module && version_compare($fileVersion, $dbVersion, '>')) {
                    $module->migrate($dbVersion, $fileVersion);
                }
                Database::update('modules', ['version' => $fileVersion], ['slug' => $slug]);

                // تسجيل أي صلاحيات جديدة أضافها الإصدار الجديد
                if ($module) {
                    foreach ($module->permissions() as $key => $label) {
                        if (!Database::fetchValue('SELECT id FROM ' . Database::table('permissions_extra') . ' WHERE permission_key = ?', [$key])) {
                            Database::insert('permissions_extra', ['permission_key' => $key, 'label' => $label, 'module_slug' => $slug]);
                        }
                    }
                }

                $log[] = 'ترحيل إضافة ' . ($manifest['name'] ?? $slug) . ": {$dbVersion} ← {$fileVersion}";
            } catch (\Throwable $e) {
                \Core\Support\Logger::exception($e);
                $log[] = "تعذر ترحيل {$slug}: " . $e->getMessage();
            }
        }

        return $log;
    }

    public static function setStatus(string $slug, string $status): array
    {
        if (!self::isInstalled($slug)) {
            return ['ok' => false, 'message' => 'الإضافة غير مثبتة.'];
        }

        Database::update('modules', ['status' => $status], ['slug' => $slug]);
        ActivityLog::record($status === 'enabled' ? 'module_enable' : 'module_disable', 'الإضافة: ' . $slug, 'module', $slug);

        return ['ok' => true, 'message' => $status === 'enabled' ? 'تم تفعيل الإضافة.' : 'تم تعطيل الإضافة.'];
    }

    public static function uninstall(string $slug, bool $keepData): array
    {
        if (!self::isInstalled($slug)) {
            return ['ok' => false, 'message' => 'الإضافة غير مثبتة.'];
        }

        $module = self::instance($slug);
        try {
            if ($module) {
                $module->uninstall($keepData);
            }
            Database::delete('modules', ['slug' => $slug]);
            Database::delete('permissions_extra', ['module_slug' => $slug]);
            ActivityLog::record('module_uninstall', 'إزالة إضافة: ' . $slug . ($keepData ? ' (مع الاحتفاظ بالبيانات)' : ' (مع حذف البيانات)'), 'module', $slug);
            return ['ok' => true, 'message' => 'تمت إزالة الإضافة.'];
        } catch (\Throwable $e) {
            \Core\Support\Logger::exception($e);
            return ['ok' => false, 'message' => 'تعذرت إزالة الإضافة: ' . $e->getMessage()];
        }
    }

    /** يشغّل جميع الإضافات المفعّلة لسياق الطلب الحالي (admin أو public) */
    public static function boot(Router $router, string $context): void
    {
        foreach (self::installedMap() as $slug => $row) {
            if ($row['status'] !== 'enabled') {
                continue;
            }
            $module = self::instance($slug);
            if (!$module) {
                continue;
            }
            try {
                $module->boot();
                if ($context === 'admin') {
                    $module->registerAdminRoutes($router);
                } else {
                    $module->registerPublicRoutes($router);
                }
            } catch (\Throwable $e) {
                // لا يجب أن يؤدي خطأ في إضافة واحدة إلى تعطل بقية النظام
                \Core\Support\Logger::exception($e);
            }
        }
    }

    public static function adminMenuItems(): array
    {
        $items = [];
        foreach (self::installedMap() as $slug => $row) {
            if ($row['status'] !== 'enabled') {
                continue;
            }
            $module = self::instance($slug);
            if (!$module) {
                continue;
            }
            try {
                foreach ($module->adminMenu() as $item) {
                    $items[] = $item;
                }
            } catch (\Throwable $e) {
                \Core\Support\Logger::exception($e);
            }
        }
        return $items;
    }
}
