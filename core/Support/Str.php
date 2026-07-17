<?php

namespace Core\Support;

final class Str
{
    /** يحول نصًا عربيًا أو إنجليزيًا إلى رابط مختصر آمن (slug) */
    public static function slug(string $text, string $fallbackPrefix = 'item'): string
    {
        $text = trim($text);
        $text = preg_replace('/[\x{200B}-\x{200F}\x{FEFF}]/u', '', $text) ?? $text;
        // استبدال المسافات والفواصل بشرطة
        $text = preg_replace('/[\s_]+/u', '-', $text) ?? $text;
        // إبقاء الحروف العربية والإنجليزية والأرقام والشرطة فقط
        $text = preg_replace('/[^\p{Arabic}a-zA-Z0-9\-]+/u', '', $text) ?? $text;
        $text = preg_replace('/-+/', '-', $text) ?? $text;
        $text = trim($text, '-');
        $text = mb_strtolower($text);

        if ($text === '') {
            $text = $fallbackPrefix . '-' . substr(bin2hex(random_bytes(3)), 0, 6);
        }

        return $text;
    }

    public static function limit(string $text, int $chars = 160, string $suffix = '...'): string
    {
        $text = trim(strip_tags($text));
        if (mb_strlen($text) <= $chars) {
            return $text;
        }
        return mb_substr($text, 0, $chars) . $suffix;
    }

    public static function random(int $length = 32): string
    {
        return bin2hex(random_bytes((int) ceil($length / 2)));
    }

    public static function maskPhone(string $phone): string
    {
        $len = mb_strlen($phone);
        if ($len <= 4) {
            return str_repeat('*', $len);
        }
        return mb_substr($phone, 0, 3) . str_repeat('*', $len - 6) . mb_substr($phone, -3);
    }
}
