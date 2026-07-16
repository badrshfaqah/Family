<?php

namespace Modules\Directory\Support;

/**
 * تطبيع وتحقق مرن لأرقام الجوال (خليجية بصيغ متعددة) دون فرض قالب صارم.
 */
final class Phone
{
    /** يزيل كل شيء عدا الأرقام وعلامة + في البداية (إن وُجدت) */
    public static function normalize(string $phone): string
    {
        $phone = trim($phone);
        $plus = str_starts_with($phone, '+') ? '+' : '';
        $digits = preg_replace('/\D+/', '', $phone) ?? '';
        return $plus . $digits;
    }

    /** تحقق متساهل: عدد أرقام معقول فقط، دون فرض مقدمة دولة معينة */
    public static function isValid(string $normalized): bool
    {
        $digits = ltrim($normalized, '+');
        $len = strlen($digits);
        return $digits !== '' && ctype_digit($digits) && $len >= 7 && $len <= 15;
    }
}
