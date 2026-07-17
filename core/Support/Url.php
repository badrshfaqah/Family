<?php

namespace Core\Support;

/**
 * توليد الروابط بصورة نسبية لمكان التركيب دائمًا،
 * بحيث يعمل النظام في الجذر أو داخل أي مجلد فرعي دون أي إعداد إضافي.
 */
final class Url
{
    private static ?string $base = null;

    /** المسار الأساسي لنقطة الدخول الحالية (index.php أو admin/index.php) دون اسم الملف */
    public static function scriptBase(): string
    {
        $scriptName = $_SERVER['SCRIPT_NAME'] ?? '';
        return rtrim(str_replace('\\', '/', dirname($scriptName)), '/');
    }

    /** المسار الأساسي لجذر تركيب النظام (يُزال منه admin أو install إن وُجد) */
    public static function siteBase(): string
    {
        if (self::$base !== null) {
            return self::$base;
        }

        $base = self::scriptBase();
        foreach (['/admin', '/install'] as $suffix) {
            if (str_ends_with($base, $suffix)) {
                $base = substr($base, 0, -strlen($suffix));
                break;
            }
        }

        return self::$base = rtrim($base, '/');
    }

    public static function to(string $path = ''): string
    {
        $path = ltrim($path, '/');
        $base = self::siteBase();
        return ($path === '') ? ($base === '' ? '/' : $base . '/') : $base . '/' . $path;
    }

    public static function admin(string $path = ''): string
    {
        $path = ltrim($path, '/');
        return self::siteBase() . '/admin' . ($path === '' ? '' : '/' . $path);
    }

    public static function asset(string $path): string
    {
        return self::to('storage/' . ltrim($path, '/'));
    }

    public static function theme(string $path): string
    {
        return self::to('themes/default/' . ltrim($path, '/'));
    }

    public static function current(): string
    {
        $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
        $uri = $_SERVER['REQUEST_URI'] ?? '/';
        return $scheme . '://' . $host . $uri;
    }

    public static function origin(): string
    {
        $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
        return $scheme . '://' . $host;
    }

    /** رابط مطلق كامل (بالمخطط والنطاق) لاستخدامه في sitemap.xml وبطاقات المشاركة الاجتماعية */
    public static function full(string $path = ''): string
    {
        return self::origin() . self::to($path);
    }
}
