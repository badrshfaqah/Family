<?php

namespace Core\Support;

final class Request
{
    public static function method(): string
    {
        return strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
    }

    public static function isPost(): bool
    {
        return self::method() === 'POST';
    }

    public static function post(string $key, $default = null)
    {
        return $_POST[$key] ?? $default;
    }

    public static function get(string $key, $default = null)
    {
        return $_GET[$key] ?? $default;
    }

    public static function input(string $key, $default = null)
    {
        return $_POST[$key] ?? $_GET[$key] ?? $default;
    }

    public static function trimmed(string $key, $default = ''): string
    {
        return trim((string) self::input($key, $default));
    }

    public static function int(string $key, int $default = 0): int
    {
        return (int) self::input($key, $default);
    }

    public static function bool(string $key): bool
    {
        $val = self::input($key);
        return in_array($val, ['1', 1, 'on', 'true', true], true);
    }

    public static function file(string $key): ?array
    {
        if (empty($_FILES[$key]) || (int) $_FILES[$key]['error'] === UPLOAD_ERR_NO_FILE) {
            return null;
        }
        return $_FILES[$key];
    }

    public static function ip(): string
    {
        return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    }

    public static function userAgent(): string
    {
        return substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 255);
    }

    /** المسار بعد نقطة دخول النظام (يدعم الجذر والمجلد الفرعي) */
    public static function path(): string
    {
        $pathInfo = $_SERVER['PATH_INFO'] ?? '';
        if ($pathInfo === '') {
            $scriptName = $_SERVER['SCRIPT_NAME'] ?? '';
            $uri = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
            $base = rtrim(str_replace(basename($scriptName), '', $scriptName), '/');
            if ($base !== '' && str_starts_with($uri, $base)) {
                $pathInfo = substr($uri, strlen($base));
            } else {
                $pathInfo = $uri;
            }
        }
        $pathInfo = '/' . trim($pathInfo, '/');
        return $pathInfo === '/' ? '/' : rtrim($pathInfo, '/');
    }
}
