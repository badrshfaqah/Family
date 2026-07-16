<?php

namespace Core\Support;

final class Security
{
    /** تهريب نص للعرض الآمن داخل HTML لمنع XSS */
    public static function e(?string $value): string
    {
        return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
    }

    /** تنظيف نص مدخل عام (يزيل الوسوم الخطرة، يبقي على نص عادي) */
    public static function cleanText(?string $value): string
    {
        $value = trim($value ?? '');
        $value = strip_tags($value);
        return $value;
    }

    /** تنظيف HTML قادم من محرر محتوى موثوق (لوحة التحكم فقط) بإزالة الوسوم الخطرة */
    public static function cleanHtml(?string $html): string
    {
        $html = $html ?? '';
        // إزالة السكربتات والأحداث الخطرة
        $html = (string) preg_replace('#<script\b[^>]*>.*?</script>#is', '', $html);
        $html = (string) preg_replace('#<iframe\b[^>]*>.*?</iframe>#is', '', $html);
        $html = (string) preg_replace('/\son\w+\s*=\s*("[^"]*"|\'[^\']*\'|[^\s>]+)/i', '', $html);
        $html = (string) preg_replace('/javascript\s*:/i', '', $html);
        return $html;
    }

    public static function hashPassword(string $plain): string
    {
        return password_hash($plain, PASSWORD_DEFAULT);
    }

    public static function verifyPassword(string $plain, string $hash): bool
    {
        return password_verify($plain, $hash);
    }

    public static function isSafeFilename(string $name): bool
    {
        return (bool) preg_match('/^[\p{Arabic}a-zA-Z0-9_\-\. ]+$/u', $name);
    }

    public static function sendSecurityHeaders(): void
    {
        if (headers_sent()) {
            return;
        }
        header('X-Content-Type-Options: nosniff');
        header('X-Frame-Options: SAMEORIGIN');
        header('Referrer-Policy: strict-origin-when-cross-origin');
    }
}
