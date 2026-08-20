<?php

namespace Core;

use Core\Support\Logger;

/**
 * المزامنة التلقائية بعد تحديث ملفات النظام (سحب من GitHub أو رفع يدوي):
 * تطبّق ترحيلات النواة، وترحّل الإضافات المثبتة لإصداراتها الجديدة،
 * وتثبّت وتفعّل الإضافات المرفقة الجديدة (حسب الإعداد) — دون أي تدخل يدوي.
 *
 * تعمل مرة واحدة فقط بعد كل تغيير فعلي: علامة القفل تُبنى من بصمة
 * إصدار النواة + إصدارات كل الإضافات الموجودة في الملفات، فأي سحب جديد
 * يغيّر أي إصدار يعيد تشغيلها تلقائيًا عند أول زيارة.
 */
final class SystemSync
{
    /** بصمة الحالة الحالية للملفات: إصدار النواة + إصدارات الإضافات */
    public static function fingerprint(): string
    {
        $versions = [];
        foreach (ModuleManager::discover() as $slug => $manifest) {
            $versions[$slug] = (string) ($manifest['version'] ?? '؟');
        }
        ksort($versions);
        return md5(CORE_VERSION . '|' . json_encode($versions, JSON_UNESCAPED_UNICODE));
    }

    private static function markerPath(): string
    {
        return STORAGE_PATH . '/cache/sync-' . self::fingerprint() . '.lock';
    }

    /** تعمل في كل طلب، وتنفّذ المزامنة فقط عند تغيّر بصمة الملفات */
    public static function autoRun(): void
    {
        $marker = self::markerPath();
        if (is_file($marker)) {
            return;
        }
        self::force();
    }

    /**
     * تنفيذ المزامنة الآن وكتابة علامة القفل.
     * @return string[] وصف ما طُبق (فارغة إن لم يلزم شيء)
     */
    public static function force(): array
    {
        $log = [];

        try {
            $applied = Database\Migrator::migrate();
            if ($applied) {
                $log[] = 'ترحيلات النواة: ' . count($applied) . ' (' . implode('، ', $applied) . ')';
            }
        } catch (\Throwable $e) {
            Logger::exception($e);
            $log[] = 'تعذر تطبيق ترحيلات النواة: ' . $e->getMessage();
        }

        $autoInstall = Settings::get('update_auto_install_modules', '1') === '1';
        $log = array_merge($log, ModuleManager::syncWithFilesystem($autoInstall));

        try {
            Settings::clearCacheFile();
        } catch (\Throwable $e) {
            // غير حرج
        }

        $marker = self::markerPath();
        if (!is_dir(dirname($marker))) {
            @mkdir(dirname($marker), 0775, true);
        }
        @file_put_contents($marker, date('Y-m-d H:i:s') . "\n" . implode("\n", $log));

        // إزالة علامات المزامنة والترحيل القديمة حتى لا تتراكم
        foreach ((array) glob(STORAGE_PATH . '/cache/sync-*.lock') as $old) {
            if ($old !== $marker) {
                @unlink($old);
            }
        }
        foreach ((array) glob(STORAGE_PATH . '/cache/migrated-*.lock') as $old) {
            @unlink($old);
        }

        if ($log) {
            try {
                ActivityLog::record('system_sync', 'مزامنة تلقائية بعد تحديث الملفات: ' . implode('؛ ', $log));
            } catch (\Throwable $e) {
                // لا نعطل الطلب إن تعذر التسجيل
            }
        }

        return $log;
    }

    /** ملخص ما ستفعله المزامنة القادمة (لشاشة التحديث): عناصر معلّقة */
    public static function pending(): array
    {
        $pending = [];

        try {
            $count = Database\Migrator::pendingCount();
            if ($count > 0) {
                $pending[] = "{$count} ترحيل لقاعدة بيانات النواة";
            }
        } catch (\Throwable $e) {
            // نتجاهل — الشاشة تعرض ما تيسر
        }

        $installed = ModuleManager::installedMap();
        foreach (ModuleManager::discover() as $slug => $manifest) {
            $fileVersion = (string) ($manifest['version'] ?? '1.0.0');
            if (!isset($installed[$slug])) {
                $pending[] = 'إضافة جديدة غير مثبتة: ' . ($manifest['name'] ?? $slug) . " ({$fileVersion})";
            } elseif ((string) $installed[$slug]['version'] !== $fileVersion) {
                $pending[] = 'تحديث إضافة ' . ($manifest['name'] ?? $slug) . ': ' . $installed[$slug]['version'] . " ← {$fileVersion}";
            }
        }

        return $pending;
    }
}
