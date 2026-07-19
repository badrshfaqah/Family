<?php

namespace Core\Support;

/** أدوات CSV الآمنة المشتركة بين إضافات النظام. */
final class Csv
{
    public static function parse(string $content): array
    {
        if (str_starts_with($content, "\xEF\xBB\xBF")) {
            $content = substr($content, 3);
        }

        $stream = fopen('php://temp', 'r+');
        fwrite($stream, $content);
        rewind($stream);
        $rows = [];
        while (($row = fgetcsv($stream)) !== false) {
            if ($row !== [null]) {
                $rows[] = $row;
            }
        }
        fclose($stream);
        return $rows;
    }

    public static function safeCell($value): string
    {
        $value = (string) ($value ?? '');
        if (preg_match('/^[\x00-\x20]*[=+\-@]/u', $value)) {
            return "'" . $value;
        }
        return $value;
    }

    public static function writeRow($stream, array $row): void
    {
        fputcsv($stream, array_map([self::class, 'safeCell'], $row));
    }

    public static function startDownload(string $filename)
    {
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . basename($filename) . '"');
        header('Cache-Control: no-store, no-cache, must-revalidate');

        $out = fopen('php://output', 'w');
        fwrite($out, "\xEF\xBB\xBF");
        return $out;
    }
}
