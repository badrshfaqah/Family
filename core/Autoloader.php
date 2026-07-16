<?php
/**
 * محمل تلقائي بسيط لا يعتمد على Composer.
 * يدعم النواة (Core\) وكل إضافة تحت مساحة اسم خاصة بها.
 */

final class Autoloader
{
    /** @var array<string,string> خريطة بادئة مساحة الاسم => المجلد الفعلي */
    private static array $map = [];
    private static bool $registered = false;

    public static function isRegistered(): bool
    {
        return self::$registered;
    }

    public static function register(): void
    {
        if (self::$registered) {
            return;
        }
        spl_autoload_register([self::class, 'load']);
        self::$map['Core\\'] = CORE_PATH . '/';
        self::$registered = true;
    }

    public static function addNamespace(string $prefix, string $baseDir): void
    {
        $prefix = rtrim($prefix, '\\') . '\\';
        self::$map[$prefix] = rtrim($baseDir, '/') . '/';
    }

    private static function load(string $class): void
    {
        foreach (self::$map as $prefix => $baseDir) {
            if (strncmp($prefix, $class, strlen($prefix)) === 0) {
                $relative = substr($class, strlen($prefix));
                $file = $baseDir . str_replace('\\', '/', $relative) . '.php';
                if (is_file($file)) {
                    require_once $file;
                    return;
                }
            }
        }
    }
}
