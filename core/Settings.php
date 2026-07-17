<?php

namespace Core;

/**
 * مخزن الإعدادات العامة (مفتاح => قيمة) مع تخزين مؤقت في ملف
 * لتقليل الاستعلامات المتكررة على الاستضافات المشتركة.
 */
final class Settings
{
    private static ?array $cache = null;

    private static function cacheFile(): string
    {
        return STORAGE_PATH . '/cache/settings.php';
    }

    private static function load(): array
    {
        if (self::$cache !== null) {
            return self::$cache;
        }

        $file = self::cacheFile();
        if (is_file($file)) {
            $data = include $file;
            if (is_array($data)) {
                return self::$cache = $data;
            }
        }

        return self::$cache = self::loadFromDatabase();
    }

    private static function loadFromDatabase(): array
    {
        if (!Database::tableExists('settings')) {
            return [];
        }
        $rows = Database::fetchAll('SELECT setting_key, setting_value FROM ' . Database::table('settings'));
        $data = [];
        foreach ($rows as $row) {
            $data[$row['setting_key']] = $row['setting_value'];
        }
        self::writeCache($data);
        return $data;
    }

    private static function writeCache(array $data): void
    {
        $dir = dirname(self::cacheFile());
        if (!is_dir($dir)) {
            @mkdir($dir, 0755, true);
        }
        $export = var_export($data, true);
        @file_put_contents(self::cacheFile(), "<?php\nreturn {$export};\n");
    }

    public static function get(string $key, $default = null)
    {
        $data = self::load();
        if (!array_key_exists($key, $data)) {
            return $default;
        }
        $value = $data[$key];
        $decoded = json_decode($value, true);
        return json_last_error() === JSON_ERROR_NONE && is_array($decoded) ? $decoded : $value;
    }

    public static function set(string $key, $value): void
    {
        $stored = is_array($value) ? json_encode($value, JSON_UNESCAPED_UNICODE) : (string) $value;

        $exists = Database::fetchValue(
            'SELECT COUNT(*) FROM ' . Database::table('settings') . ' WHERE setting_key = ?',
            [$key]
        );

        if ($exists) {
            Database::update('settings', ['setting_value' => $stored], ['setting_key' => $key]);
        } else {
            Database::insert('settings', ['setting_key' => $key, 'setting_value' => $stored]);
        }

        self::$cache = null;
        self::clearCacheFile();
    }

    public static function setMany(array $values): void
    {
        foreach ($values as $key => $value) {
            self::set($key, $value);
        }
    }

    public static function clearCacheFile(): void
    {
        $file = self::cacheFile();
        if (is_file($file)) {
            @unlink($file);
        }
    }

    public static function all(): array
    {
        return self::load();
    }
}
