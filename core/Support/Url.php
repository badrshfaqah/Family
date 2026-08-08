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
        $uri = $_SERVER['REQUEST_URI'] ?? '/';
        return self::origin() . (str_starts_with($uri, '/') ? $uri : '/');
    }

    public static function origin(): string
    {
        if (defined('APP_URL')) {
            $configured = rtrim((string) APP_URL, '/');
            if (filter_var($configured, FILTER_VALIDATE_URL)
                && in_array(strtolower((string) parse_url($configured, PHP_URL_SCHEME)), ['http', 'https'], true)) {
                return $configured;
            }
        }

        $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        // SERVER_NAME يأتي من إعداد الخادم، بخلاف Host الذي يتحكم به العميل.
        $host = strtolower((string) ($_SERVER['SERVER_NAME'] ?? 'localhost'));
        if (!filter_var($host, FILTER_VALIDATE_IP)
            && !preg_match('/^(?:[a-z0-9](?:[a-z0-9.-]{0,251}[a-z0-9])?)$/', $host)) {
            $host = 'localhost';
        }
        $port = (int) ($_SERVER['SERVER_PORT'] ?? ($scheme === 'https' ? 443 : 80));
        if (($scheme === 'https' && $port !== 443) || ($scheme === 'http' && $port !== 80)) {
            $host .= ':' . $port;
        }
        return $scheme . '://' . $host;
    }

    /** رابط مطلق كامل (بالمخطط والنطاق) لاستخدامه في sitemap.xml وبطاقات المشاركة الاجتماعية */
    public static function full(string $path = ''): string
    {
        return self::origin() . self::to($path);
    }

    /**
     * رابط وصفي صديق لمحركات البحث للمسارات الرقمية: /calendar/4-عنوان-المناسبة
     * المعرّف يبقى أول المقطع فتستمر الروابط القديمة بالعمل، والعنوان يضيف
     * كلمات مفتاحية يفهمها الزائر ومحرك البحث.
     */
    public static function pretty(string $route, int $id, string $title): string
    {
        $slug = Str::slug($title, '');
        return self::to($route . '/' . $id . ($slug !== '' ? '-' . $slug : ''));
    }

    /** النسخة المطلقة من الرابط الوصفي (للخريطة والمشاركة) */
    public static function prettyFull(string $route, int $id, string $title): string
    {
        return self::origin() . self::pretty($route, $id, $title);
    }
}
