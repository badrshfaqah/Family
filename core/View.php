<?php

namespace Core;

use Core\Support\Security;
use Core\Support\Url;

/**
 * محرك عرض بسيط يعتمد على قوالب PHP عادية (بدون أي مكتبة خارجية).
 */
final class View
{
    private static array $shared = [];

    public static function share(string $key, $value): void
    {
        self::$shared[$key] = $value;
    }

    public static function render(string $templatePath, array $data = []): string
    {
        if (!is_file($templatePath)) {
            throw new \RuntimeException('ملف العرض غير موجود: ' . $templatePath);
        }

        $data = array_merge(self::$shared, $data);
        extract($data, EXTR_SKIP);

        ob_start();
        include $templatePath;
        return (string) ob_get_clean();
    }

    /** يعرض ملفًا داخل قالب رئيسي (layout) عبر متغير $content */
    public static function renderLayout(string $layoutPath, string $templatePath, array $data = []): string
    {
        $content = self::render($templatePath, $data);
        return self::render($layoutPath, array_merge($data, ['content' => $content]));
    }

    public static function e(?string $value): string
    {
        return Security::e($value);
    }

    public static function url(string $path = ''): string
    {
        return Url::to($path);
    }

    public static function asset(string $path): string
    {
        return Url::asset($path);
    }
}
