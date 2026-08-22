<?php

namespace Core\Support;

/**
 * دفاعات ضد الرسائل الآلية (سبام) للنماذج العامة، بدون أي خدمة خارجية:
 *  1. حقل فخ مخفي (honeypot) لا يراه البشر وتعبئه البوتات.
 *  2. حد زمني أدنى بين عرض النموذج وإرساله — البوتات ترسل خلال أجزاء ثانية.
 *  3. فحص محتوى: الروابط والرسائل الخالية من العربية علامة سبام شبه مؤكدة.
 */
final class SpamGuard
{
    private const HONEYPOT_FIELD = 'website_url';

    /** يُطبع داخل النموذج: حقل الفخ + تسجيل وقت العرض في الجلسة */
    public static function fields(string $form): string
    {
        Session::set('_sg_ts_' . $form, time());
        return '<div style="position:absolute;inset-inline-start:-9999px;top:-9999px" aria-hidden="true">'
            . '<label>الموقع الإلكتروني<input type="text" name="' . self::HONEYPOT_FIELD . '" tabindex="-1" autocomplete="off"></label>'
            . '</div>';
    }

    /** true إذا عبر الإرسال فحص الفخ والزمن — استدعِه بعد التحقق من CSRF */
    public static function passes(string $form, int $minSeconds = 4): bool
    {
        if (trim((string) Request::post(self::HONEYPOT_FIELD, '')) !== '') {
            return false;
        }
        $shownAt = (int) Session::get('_sg_ts_' . $form, 0);
        Session::remove('_sg_ts_' . $form);
        return $shownAt > 0 && (time() - $shownAt) >= $minSeconds;
    }

    /** يحتوي رابطًا؟ (السبام يهدف دائمًا لزرع روابط) */
    public static function hasLink(string $text): bool
    {
        return (bool) preg_match('~https?://|www\.|[a-z0-9-]+\.(com|net|org|io|ru|cn|xyz|info|top|site|online|shop|link)\b~iu', $text);
    }

    /** خالٍ من أي حرف عربي؟ (نماذج الموقع موجهة لجمهور عربي) */
    public static function lacksArabic(string $text): bool
    {
        return !preg_match('/\p{Arabic}/u', $text);
    }
}
