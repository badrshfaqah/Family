<?php

namespace Core\Support;

final class Str
{
    /** يحول نصًا عربيًا أو إنجليزيًا إلى رابط مختصر آمن (slug) */
    /** نقحرة الحروف العربية إلى لاتينية لروابط قابلة للمشاركة بلا ترميز %XX */
    private const AR_TO_LATIN = [
        'ا' => 'a', 'أ' => 'a', 'إ' => 'i', 'آ' => 'a', 'ء' => '', 'ؤ' => 'o', 'ئ' => 'e',
        'ب' => 'b', 'ت' => 't', 'ث' => 'th', 'ج' => 'j', 'ح' => 'h', 'خ' => 'kh',
        'د' => 'd', 'ذ' => 'th', 'ر' => 'r', 'ز' => 'z', 'س' => 's', 'ش' => 'sh',
        'ص' => 's', 'ض' => 'd', 'ط' => 't', 'ظ' => 'th', 'ع' => 'a', 'غ' => 'gh',
        'ف' => 'f', 'ق' => 'q', 'ك' => 'k', 'ل' => 'l', 'م' => 'm', 'ن' => 'n',
        'ه' => 'h', 'ة' => 'h', 'و' => 'w', 'ي' => 'y', 'ى' => 'a',
        '٠' => '0', '١' => '1', '٢' => '2', '٣' => '3', '٤' => '4',
        '٥' => '5', '٦' => '6', '٧' => '7', '٨' => '8', '٩' => '9',
    ];

    public static function slug(string $text, string $fallbackPrefix = 'item'): string
    {
        $text = trim($text);
        $text = preg_replace('/[\x{200B}-\x{200F}\x{FEFF}]/u', '', $text) ?? $text;
        // إزالة التشكيل ثم نقحرة العربية إلى لاتينية (روابط تُنسخ وتُشارك بدون رموز مشفرة)
        $text = preg_replace('/[\x{064B}-\x{065F}\x{0640}]/u', '', $text) ?? $text;
        $text = strtr($text, self::AR_TO_LATIN);
        // استبدال المسافات والفواصل بشرطة
        $text = preg_replace('/[\s_]+/u', '-', $text) ?? $text;
        // إبقاء الحروف الإنجليزية والأرقام والشرطة فقط
        $text = preg_replace('/[^a-zA-Z0-9\-]+/u', '', $text) ?? $text;
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
