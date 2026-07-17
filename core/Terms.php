<?php

namespace Core;

/**
 * نظام تخصيص المصطلح الأساسي (عائلة / قبيلة / رابطة / ديوانية / جماعة / مخصص)
 * بحيث يتم تطبيقه تلقائيًا في كل نصوص الواجهة دون أي تعديل برمجي.
 */
final class Terms
{
    public const PRESETS = [
        'family' => 'العائلة',
        'tribe' => 'القبيلة',
        'league' => 'الرابطة',
        'diwaniyah' => 'الديوانية',
        'group' => 'الجماعة',
    ];

    private static ?string $name = null;

    /** الاسم المعرف الأساسي للمصطلح، مثل: العائلة / القبيلة / اسم مخصص */
    public static function name(): string
    {
        if (self::$name !== null) {
            return self::$name;
        }

        $mode = Settings::get('term_mode', 'family');
        if ($mode === 'custom') {
            $custom = trim((string) Settings::get('term_custom', ''));
            return self::$name = ($custom !== '' ? $custom : self::PRESETS['family']);
        }

        return self::$name = self::PRESETS[$mode] ?? self::PRESETS['family'];
    }

    /** صيغة الجر المقترنة (لـ.../لل...) */
    public static function forPreposition(): string
    {
        $name = self::name();
        if (mb_substr($name, 0, 2) === 'ال') {
            return 'لل' . mb_substr($name, 2);
        }
        return 'لـ' . $name;
    }

    /** صيغة الظرفية (في العائلة / في اسم مخصص) */
    public static function inPreposition(): string
    {
        return 'في ' . self::name();
    }

    /** تركيب إضافي: [كلمة] + المصطلح، مثل: جوال + العائلة = جوال العائلة */
    public static function of(string $word): string
    {
        return $word . ' ' . self::name();
    }

    public static function phrase(string $key): string
    {
        $templates = [
            'family_directory' => self::of('جوال'),
            'family_directory_register' => 'التسجيل في ' . self::of('جوال'),
            'news' => self::of('أخبار'),
            'events' => self::of('مناسبات'),
            'tree' => self::of('شجرة النسب'),
            'branches' => self::of('فروع'),
            'gatherings' => 'الجمعات',
            'archive' => self::of('الأرشيف التاريخي'),
            'welcome' => 'مرحبًا بكم في موقع ' . self::name() . ' الرسمي',
            'about' => 'عن ' . self::name(),
            'official_site' => 'الموقع الرسمي ' . self::forPreposition(),
            'contact_admin' => 'التواصل مع إدارة موقع ' . self::name(),
            'consent_directory' => 'أوافق على حفظ رقم الجوال واستخدامه من قبل إدارة الموقع لإرسال أخبار ومناسبات '
                . self::name() . '، مثل الزواج والعزاء والاجتماعات والولائم والإعلانات المهمة.',
        ];

        return $templates[$key] ?? self::name();
    }

    public static function reset(): void
    {
        self::$name = null;
    }
}
