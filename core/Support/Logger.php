<?php

namespace Core\Support;

/**
 * تسجيل الأخطاء في ملفات منظمة داخل storage/logs
 * دون كشف أي تفاصيل حساسة للزوار في بيئة الإنتاج.
 */
final class Logger
{
    public static function error(string $message, array $context = []): void
    {
        self::write('error', $message, $context);
    }

    public static function info(string $message, array $context = []): void
    {
        self::write('info', $message, $context);
    }

    private static function write(string $level, string $message, array $context): void
    {
        $dir = defined('STORAGE_PATH') ? STORAGE_PATH . '/logs' : __DIR__;
        if (!is_dir($dir)) {
            @mkdir($dir, 0755, true);
        }
        $file = $dir . '/' . date('Y-m-d') . '.log';
        $line = sprintf(
            '[%s] %s: %s %s' . PHP_EOL,
            date('Y-m-d H:i:s'),
            strtoupper($level),
            $message,
            $context ? json_encode($context, JSON_UNESCAPED_UNICODE) : ''
        );
        @file_put_contents($file, $line, FILE_APPEND | LOCK_EX);
    }

    public static function exception(\Throwable $e): void
    {
        self::error($e->getMessage(), [
            'file' => $e->getFile(),
            'line' => $e->getLine(),
            'trace' => $e->getTraceAsString(),
        ]);
    }
}
