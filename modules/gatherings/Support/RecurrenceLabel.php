<?php

namespace Modules\Gatherings\Support;

/**
 * يبني نصًا عربيًا مختصرًا يصف موعد تكرار الجمعة (مثل "كل يوم جمعة، من 8:00 م")
 * اعتمادًا على نوع التكرار وأيامه وأوقاته، ليُحفظ جاهزًا في عمود recurrence_label.
 */
final class RecurrenceLabel
{
    public const TYPES = ['daily', 'weekly', 'monthly', 'specific_weekdays', 'custom'];

    /** أسماء الأيام المجردة (بدون "ال") لتُستخدم بعد "كل يوم" أو "أول/ثاني.." */
    private const DAY_NAMES = [0 => 'أحد', 1 => 'اثنين', 2 => 'ثلاثاء', 3 => 'أربعاء', 4 => 'خميس', 5 => 'جمعة', 6 => 'سبت'];

    /** أسماء الأيام المعرّفة، تُستخدم عند سرد أكثر من يوم */
    private const DAY_NAMES_DEFINITE = [0 => 'الأحد', 1 => 'الاثنين', 2 => 'الثلاثاء', 3 => 'الأربعاء', 4 => 'الخميس', 5 => 'الجمعة', 6 => 'السبت'];

    private const ORDINAL_PREFIX = [1 => 'أول', 2 => 'ثاني', 3 => 'ثالث', 4 => 'رابع', 5 => 'آخر'];

    /**
     * @param int[] $days أرقام أيام الأسبوع 0..6 (0 = الأحد)، مطابقة لـ PHP date('w')
     */
    public static function build(
        string $type,
        array $days,
        ?int $ordinal,
        ?string $customText,
        ?string $startTime,
        ?string $endTime
    ): string {
        $days = array_values(array_unique(array_filter($days, fn($d) => $d >= 0 && $d <= 6)));
        sort($days);

        $label = match ($type) {
            'daily' => 'يوميًا',
            'weekly' => self::weeklyLabel($days),
            'specific_weekdays' => self::specificDaysLabel($days),
            'monthly' => self::monthlyLabel($days, $ordinal),
            'custom' => trim((string) $customText) !== '' ? trim((string) $customText) : 'بتكرار مخصص',
            default => 'غير محدد',
        };

        $timeSuffix = self::timeSuffix($startTime, $endTime);

        return $timeSuffix !== '' ? $label . '، ' . $timeSuffix : $label;
    }

    private static function weeklyLabel(array $days): string
    {
        if (empty($days)) {
            return 'أسبوعيًا';
        }
        if (count($days) === 1) {
            return 'كل يوم ' . self::DAY_NAMES[$days[0]];
        }
        return self::specificDaysLabel($days);
    }

    private static function specificDaysLabel(array $days): string
    {
        if (empty($days)) {
            return 'في أيام محددة';
        }
        if (count($days) === 1) {
            return 'كل يوم ' . self::DAY_NAMES[$days[0]];
        }
        $names = array_map(fn($d) => self::DAY_NAMES_DEFINITE[$d], $days);
        return 'أيام ' . implode('، ', $names);
    }

    private static function monthlyLabel(array $days, ?int $ordinal): string
    {
        $ordinal = $ordinal && isset(self::ORDINAL_PREFIX[$ordinal]) ? $ordinal : 1;
        $prefix = self::ORDINAL_PREFIX[$ordinal];

        if (empty($days)) {
            return 'شهريًا';
        }

        $dayName = self::DAY_NAMES[$days[0]];
        return $prefix . ' ' . $dayName . ' من كل شهر';
    }

    private static function timeSuffix(?string $startTime, ?string $endTime): string
    {
        $startTime = trim((string) $startTime);
        $endTime = trim((string) $endTime);

        if ($startTime === '') {
            return '';
        }

        $suffix = 'من ' . self::formatTime12($startTime);
        if ($endTime !== '') {
            $suffix .= ' حتى ' . self::formatTime12($endTime);
        }

        return $suffix;
    }

    private static function formatTime12(string $time): string
    {
        $parts = explode(':', $time);
        $hour = (int) ($parts[0] ?? 0);
        $minute = (int) ($parts[1] ?? 0);

        $period = $hour >= 12 ? 'م' : 'ص';
        $hour12 = $hour % 12;
        if ($hour12 === 0) {
            $hour12 = 12;
        }

        return sprintf('%d:%02d %s', $hour12, $minute, $period);
    }
}
