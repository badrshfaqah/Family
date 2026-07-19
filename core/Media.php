<?php

namespace Core;

use Core\Support\Security;
use Core\Support\Str;

/**
 * مدير الوسائط المركزي: رفع، تحقق من النوع والحجم، إنشاء أحجام مصغرة،
 * ودعم WebP عند توفر مكتبة GD المناسبة على السيرفر.
 */
final class Media
{
    private const ALLOWED_IMAGE = ['jpg', 'jpeg', 'png', 'webp', 'gif'];
    private const ALLOWED_DOC = ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'mp3', 'mp4'];
    private const DANGEROUS_EXTENSIONS = [
        'php', 'php3', 'php4', 'php5', 'php7', 'php8', 'phtml', 'phar',
        'html', 'htm', 'xhtml', 'shtml', 'svg', 'xml', 'xsl',
        'js', 'mjs', 'css', 'htaccess', 'user.ini', 'cgi', 'pl', 'py', 'sh',
    ];

    public static function maxUploadBytes(): int
    {
        $mb = (int) Settings::get('media_max_size_mb', 8);
        return max(1, $mb) * 1024 * 1024;
    }

    public static function allowedExtensions(): array
    {
        $extra = Settings::get('media_allowed_extensions', '');
        $extra = is_string($extra) && $extra !== '' ? array_map(
            fn($ext) => strtolower(ltrim(trim($ext), '.')),
            explode(',', $extra)
        ) : [];
        $extra = array_filter($extra, fn($ext) => preg_match('/^[a-z0-9]{1,10}$/', $ext)
            && !in_array($ext, self::DANGEROUS_EXTENSIONS, true));
        return array_unique(array_merge(self::ALLOWED_IMAGE, self::ALLOWED_DOC, $extra));
    }

    public static function isImageExt(string $ext): bool
    {
        return in_array(strtolower($ext), self::ALLOWED_IMAGE, true);
    }

    /**
     * يعالج رفع ملف واحد ($_FILES[...]) ويعيد بيانات السجل المطلوب حفظه في قاعدة البيانات.
     * يرمي استثناء برسالة عربية واضحة عند الفشل.
     */
    public static function handleUpload(array $file, ?int $uploadedBy = null): array
    {
        if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            throw new \RuntimeException('فشل رفع الملف. الرجاء المحاولة مجددًا.');
        }

        if ($file['size'] > self::maxUploadBytes()) {
            $mb = (int) Settings::get('media_max_size_mb', 8);
            throw new \RuntimeException("حجم الملف أكبر من الحد المسموح ({$mb} ميجابايت).");
        }

        $originalName = $file['name'];
        $ext = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));

        if (!in_array($ext, self::allowedExtensions(), true)) {
            throw new \RuntimeException('نوع الملف غير مسموح به.');
        }

        if (!self::verifyRealType($file['tmp_name'], $ext)) {
            throw new \RuntimeException('تعذر التحقق من نوع الملف، الرجاء رفع ملف سليم.');
        }

        $baseSlug = Str::slug(pathinfo($originalName, PATHINFO_FILENAME), 'file');
        $storedName = date('Y/m') . '/' . $baseSlug . '-' . Str::random(6) . '.' . $ext;
        $fullPath = STORAGE_PATH . '/uploads/originals/' . $storedName;

        $dir = dirname($fullPath);
        if (!is_dir($dir) && !mkdir($dir, 0755, true) && !is_dir($dir)) {
            throw new \RuntimeException('تعذر إنشاء مجلد الرفع، الرجاء التحقق من صلاحيات المجلدات.');
        }

        if (!move_uploaded_file($file['tmp_name'], $fullPath)) {
            throw new \RuntimeException('تعذر حفظ الملف على السيرفر.');
        }

        @chmod($fullPath, 0644);

        $isImage = self::isImageExt($ext);
        $width = null;
        $height = null;
        $thumbPath = null;

        if ($isImage) {
            self::compressOriginal($fullPath, $ext);

            $info = @getimagesize($fullPath);
            if ($info) {
                [$width, $height] = $info;
                $thumbPath = self::makeThumbnail($fullPath, $storedName, $ext);
            }
        }

        return [
            'file_name' => $originalName,
            'stored_path' => $storedName,
            'thumb_path' => $thumbPath,
            'mime_type' => mime_content_type($fullPath) ?: '',
            'extension' => $ext,
            'size_bytes' => filesize($fullPath),
            'width' => $width,
            'height' => $height,
            'uploaded_by' => $uploadedBy,
        ];
    }

    private static function verifyRealType(string $tmpPath, string $ext): bool
    {
        $mime = strtolower((string) (mime_content_type($tmpPath) ?: ''));

        if (self::isImageExt($ext)) {
            $info = @getimagesize($tmpPath);
            if (!$info) {
                return false;
            }
            $expected = match ($ext) {
                'jpg', 'jpeg' => ['image/jpeg'],
                'png' => ['image/png'],
                'gif' => ['image/gif'],
                'webp' => ['image/webp'],
                default => [],
            };
            return in_array(strtolower((string) ($info['mime'] ?? $mime)), $expected, true);
        }

        $allowedMimes = match ($ext) {
            'pdf' => ['application/pdf'],
            'doc' => ['application/msword', 'application/vnd.ms-office', 'application/cdfv2', 'application/octet-stream'],
            'docx' => ['application/vnd.openxmlformats-officedocument.wordprocessingml.document', 'application/zip'],
            'xls' => ['application/vnd.ms-excel', 'application/vnd.ms-office', 'application/cdfv2', 'application/octet-stream'],
            'xlsx' => ['application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 'application/zip'],
            'mp3' => ['audio/mpeg', 'audio/mp3', 'application/octet-stream'],
            'mp4' => ['video/mp4', 'audio/mp4', 'application/mp4', 'application/octet-stream'],
            default => [],
        };

        if ($allowedMimes) {
            return in_array($mime, $allowedMimes, true);
        }

        // الامتدادات الإضافية لا يجوز أن تحمل محتوى نشطًا ينفذه المتصفح.
        return !in_array($mime, [
            'text/html', 'application/xhtml+xml', 'image/svg+xml',
            'text/xml', 'application/xml', 'text/javascript',
            'application/javascript', 'application/x-httpd-php',
        ], true);
    }

    /**
     * يضغط الصورة الأصلية المرفوعة: يصغّر الأبعاد إذا تجاوزت الحد الأقصى المسموح
     * في الإعدادات، ويعيد حفظها بجودة الضغط المحددة (لملفات jpg/webp/png).
     * لا يُطبَّق على gif حتى لا تنكسر الصور المتحركة.
     */
    private static function compressOriginal(string $path, string $ext): void
    {
        if (!extension_loaded('gd') || $ext === 'gif') {
            return;
        }

        [$width, $height] = @getimagesize($path) ?: [0, 0];
        if (!$width || !$height) {
            return;
        }

        $maxDim = (int) Settings::get('media_max_dimension', 2000);
        $quality = max(10, min(100, (int) Settings::get('media_image_quality', 85)));

        $needsResize = $maxDim > 0 && max($width, $height) > $maxDim;

        $src = self::loadImage($path, $ext);
        if (!$src) {
            return;
        }

        if ($needsResize) {
            $ratio = $maxDim / max($width, $height);
            $newWidth = max(1, (int) round($width * $ratio));
            $newHeight = max(1, (int) round($height * $ratio));

            $resized = imagecreatetruecolor($newWidth, $newHeight);
            imagealphablending($resized, false);
            imagesavealpha($resized, true);
            imagecopyresampled($resized, $src, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);
            imagedestroy($src);
            $src = $resized;
        }

        match ($ext) {
            'jpg', 'jpeg' => imagejpeg($src, $path, $quality),
            'png' => imagepng($src, $path, (int) round((100 - $quality) / 10)),
            'webp' => function_exists('imagewebp') ? imagewebp($src, $path, $quality) : null,
            default => null,
        };

        imagedestroy($src);
    }

    private static function makeThumbnail(string $sourcePath, string $storedName, string $ext): ?string
    {
        if (!extension_loaded('gd')) {
            return null;
        }

        [$origWidth, $origHeight] = @getimagesize($sourcePath) ?: [0, 0];
        if (!$origWidth || !$origHeight) {
            return null;
        }

        $maxDim = 480;
        $ratio = min(1, $maxDim / max($origWidth, $origHeight));
        $newWidth = max(1, (int) round($origWidth * $ratio));
        $newHeight = max(1, (int) round($origHeight * $ratio));

        $src = self::loadImage($sourcePath, $ext);
        if (!$src) {
            return null;
        }

        $thumb = imagecreatetruecolor($newWidth, $newHeight);
        imagealphablending($thumb, false);
        imagesavealpha($thumb, true);
        imagecopyresampled($thumb, $src, 0, 0, 0, 0, $newWidth, $newHeight, $origWidth, $origHeight);

        $thumbRelative = 'thumbs/' . preg_replace('/\.[a-zA-Z0-9]+$/', '', $storedName) . '-thumb.jpg';
        $thumbFull = STORAGE_PATH . '/uploads/' . $thumbRelative;
        $thumbDir = dirname($thumbFull);
        if (!is_dir($thumbDir)) {
            mkdir($thumbDir, 0755, true);
        }

        $white = imagecreatetruecolor($newWidth, $newHeight);
        $bg = imagecolorallocate($white, 255, 255, 255);
        imagefill($white, 0, 0, $bg);
        imagecopy($white, $thumb, 0, 0, 0, 0, $newWidth, $newHeight);

        imagejpeg($white, $thumbFull, 82);
        imagedestroy($src);
        imagedestroy($thumb);
        imagedestroy($white);

        return $thumbRelative;
    }

    private static function loadImage(string $path, string $ext)
    {
        return match ($ext) {
            'jpg', 'jpeg' => @imagecreatefromjpeg($path),
            'png' => @imagecreatefrompng($path),
            'gif' => @imagecreatefromgif($path),
            'webp' => function_exists('imagecreatefromwebp') ? @imagecreatefromwebp($path) : false,
            default => false,
        };
    }

    public static function deleteFiles(string $storedPath, ?string $thumbPath = null): void
    {
        $full = STORAGE_PATH . '/uploads/originals/' . $storedPath;
        if (is_file($full)) {
            @unlink($full);
        }
        if ($thumbPath) {
            $thumbFull = STORAGE_PATH . '/uploads/' . $thumbPath;
            if (is_file($thumbFull)) {
                @unlink($thumbFull);
            }
        }
    }

    public static function url(string $storedPath): string
    {
        return \Core\Support\Url::asset('uploads/originals/' . $storedPath);
    }

    public static function thumbUrl(?string $thumbPath, string $fallbackStoredPath = ''): string
    {
        if ($thumbPath) {
            return \Core\Support\Url::asset('uploads/' . $thumbPath);
        }
        return $fallbackStoredPath ? self::url($fallbackStoredPath) : '';
    }
}
