<?php

namespace Modules\Directory\Support;

/**
 * تطبيع وتحقق مرن لأرقام الجوال (خليجية بصيغ متعددة) دون فرض قالب صارم.
 */
final class Phone
{
    /** يزيل كل شيء عدا الأرقام وعلامة + في البداية (إن وُجدت)، ويحوّل الأرقام العربية/الفارسية للاتينية */
    public static function normalize(string $phone): string
    {
        $phone = trim($phone);
        // من يكتب رقمه بلوحة مفاتيح عربية يُدخل أرقامًا هندية (٠١٢٣...) كانت تُحذف كليًا
        // فيُرفض الرقم بأنه "غير صالح" — نحوّلها أولًا إلى أرقام لاتينية.
        $phone = strtr($phone, [
            '٠' => '0', '١' => '1', '٢' => '2', '٣' => '3', '٤' => '4',
            '٥' => '5', '٦' => '6', '٧' => '7', '٨' => '8', '٩' => '9',
            '۰' => '0', '۱' => '1', '۲' => '2', '۳' => '3', '۴' => '4',
            '۵' => '5', '۶' => '6', '۷' => '7', '۸' => '8', '۹' => '9',
        ]);
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
