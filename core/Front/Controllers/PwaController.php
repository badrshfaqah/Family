<?php

namespace Core\Front\Controllers;

use Core\Database;
use Core\Settings;
use Core\Support\Url;

/**
 * دعم تطبيق الويب التقدمي (PWA): بيان تطبيق ديناميكي يعكس هوية الموقع الفعلية،
 * أيقونات مولّدة من شعار الموقع، وworker بسيط للعمل دون اتصال.
 */
final class PwaController
{
    public function manifest(array $params): void
    {
        $shortName = Settings::get('identity_short_name', '') ?: Settings::get('identity_official_name', 'الموقع');
        $officialName = Settings::get('identity_official_name', '') ?: $shortName;
        $primaryColor = Settings::get('theme_color_primary', '#0f6e5e');

        $manifest = [
            'name' => $officialName,
            'short_name' => mb_substr($shortName, 0, 24),
            'description' => Settings::get('identity_brief', '') ?: ('الموقع الرسمي ل' . $officialName),
            'start_url' => Url::to(''),
            'scope' => Url::to(''),
            'display' => 'standalone',
            'orientation' => 'portrait-primary',
            'background_color' => '#ffffff',
            'theme_color' => $primaryColor,
            'dir' => 'rtl',
            'lang' => 'ar',
            'icons' => [
                ['src' => Url::to('pwa-icon-192.png'), 'sizes' => '192x192', 'type' => 'image/png', 'purpose' => 'any maskable'],
                ['src' => Url::to('pwa-icon-512.png'), 'sizes' => '512x512', 'type' => 'image/png', 'purpose' => 'any maskable'],
            ],
        ];

        header('Content-Type: application/manifest+json; charset=utf-8');
        echo json_encode($manifest, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
    }

    public function serviceWorker(array $params): void
    {
        header('Content-Type: application/javascript; charset=utf-8');
        header('Service-Worker-Allowed: ' . Url::to(''));
        readfile(CORE_PATH . '/Front/Assets/sw.js');
    }

    public function icon(array $params): void
    {
        $size = (int) ($params['size'] ?? 192);
        $size = in_array($size, [192, 512], true) ? $size : 192;

        $cacheFile = STORAGE_PATH . '/cache/pwa-icon-' . $size . '.png';

        if (!is_file($cacheFile)) {
            $this->generateIcon($size, $cacheFile);
        }

        if (!is_file($cacheFile)) {
            http_response_code(404);
            return;
        }

        header('Content-Type: image/png');
        header('Cache-Control: public, max-age=86400');
        readfile($cacheFile);
    }

    private function generateIcon(int $size, string $cacheFile): void
    {
        if (!extension_loaded('gd')) {
            return;
        }

        $dir = dirname($cacheFile);
        if (!is_dir($dir)) {
            @mkdir($dir, 0755, true);
        }

        $primaryColor = Settings::get('theme_color_primary', '#0f6e5e');
        [$r, $g, $b] = $this->hexToRgb($primaryColor);

        $canvas = imagecreatetruecolor($size, $size);
        $bg = imagecolorallocate($canvas, $r, $g, $b);
        imagefilledrectangle($canvas, 0, 0, $size, $size, $bg);

        $logoId = Settings::get('identity_logo_media_id', '');
        $logoDrawn = false;

        if ($logoId) {
            $logo = Database::fetchOne('SELECT stored_path, extension FROM ' . Database::table('media') . ' WHERE id = ?', [$logoId]);
            if ($logo) {
                $logoPath = STORAGE_PATH . '/uploads/originals/' . $logo['stored_path'];
                $src = $this->loadImage($logoPath, $logo['extension']);
                if ($src) {
                    $srcWidth = imagesx($src);
                    $srcHeight = imagesy($src);
                    $padding = (int) round($size * 0.14);
                    $targetSize = $size - ($padding * 2);
                    $ratio = min($targetSize / $srcWidth, $targetSize / $srcHeight);
                    $drawWidth = max(1, (int) round($srcWidth * $ratio));
                    $drawHeight = max(1, (int) round($srcHeight * $ratio));
                    $offsetX = (int) round(($size - $drawWidth) / 2);
                    $offsetY = (int) round(($size - $drawHeight) / 2);

                    imagecopyresampled($canvas, $src, $offsetX, $offsetY, 0, 0, $drawWidth, $drawHeight, $srcWidth, $srcHeight);
                    imagedestroy($src);
                    $logoDrawn = true;
                }
            }
        }

        if (!$logoDrawn) {
            // لا يوجد شعار مرفوع بعد: شعار عام بسيط (دائرة بلون ثانوي) بدل النص لتجنب الاعتماد على خطوط TTF
            $secondaryColor = Settings::get('theme_color_secondary', '#c9a24b');
            [$sr, $sg, $sb] = $this->hexToRgb($secondaryColor);
            $accent = imagecolorallocate($canvas, $sr, $sg, $sb);
            $radius = (int) round($size * 0.42);
            $center = (int) round($size / 2);
            imagefilledellipse($canvas, $center, $center, $radius, $radius, $accent);
        }

        imagepng($canvas, $cacheFile);
        imagedestroy($canvas);
    }

    private function loadImage(string $path, string $ext)
    {
        if (!is_file($path)) {
            return false;
        }
        return match (strtolower($ext)) {
            'jpg', 'jpeg' => @imagecreatefromjpeg($path),
            'png' => @imagecreatefrompng($path),
            'gif' => @imagecreatefromgif($path),
            'webp' => function_exists('imagecreatefromwebp') ? @imagecreatefromwebp($path) : false,
            default => false,
        };
    }

    private function hexToRgb(string $hex): array
    {
        $hex = ltrim($hex, '#');
        if (strlen($hex) !== 6) {
            return [15, 110, 94];
        }
        return [
            hexdec(substr($hex, 0, 2)),
            hexdec(substr($hex, 2, 2)),
            hexdec(substr($hex, 4, 2)),
        ];
    }
}
