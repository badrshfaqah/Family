<?php

namespace Modules\Directory\Support;

/**
 * تحويل نصوص/صفوف CSV دون أي مكتبة خارجية (fgetcsv/fputcsv فقط)،
 * بحيث تُفتح الملفات الناتجة مباشرة في إكسل.
 */
final class Csv
{
    /** يحوّل محتوى CSV نصي إلى مصفوفة صفوف (كل صف = مصفوفة أعمدة) */
    public static function parse(string $content): array
    {
        // إزالة BOM إن وُجد حتى لا يظهر ضمن أول عمود
        if (str_starts_with($content, "\xEF\xBB\xBF")) {
            $content = substr($content, 3);
        }

        $stream = fopen('php://temp', 'r+');
        fwrite($stream, $content);
        rewind($stream);

        $rows = [];
        while (($row = fgetcsv($stream)) !== false) {
            if ($row === [null] || $row === false) {
                continue;
            }
            $rows[] = $row;
        }
        fclose($stream);

        return $rows;
    }

    /** يبدأ استجابة تنزيل CSV مع BOM لعرض عربي سليم في إكسل، ويعيد مؤشر الإخراج */
    public static function startDownload(string $filename)
    {
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Cache-Control: no-store, no-cache, must-revalidate');

        $out = fopen('php://output', 'w');
        fwrite($out, "\xEF\xBB\xBF");
        return $out;
    }
}
