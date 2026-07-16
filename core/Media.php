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

    public static function maxUploadBytes(): int
    {
        $mb = (int) Settings::get('media_max_size_mb', 8);
        return max(1, $mb) * 1024 * 1024;
    }

    public static function allowedExtensions(): array
    {
        $extra = Settings::get('media_allowed_extensions', '');
        $extra = is_string($extra) && $extra !== '' ? array_map('trim', explode(',', $extra)) : [];
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
        if (self::isImageExt($ext) && $ext !== 'webp') {
            return (bool) @getimagesize($tmpPath);
        }
        return true;
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
