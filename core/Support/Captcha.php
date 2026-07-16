<?php

namespace Core\Support;

/**
 * رمز تحقق مرئي بسيط لمنع التسجيل الآلي في النماذج العامة
 * (جوال العائلة، القائمة البريدية) دون الاعتماد على أي خدمة خارجية.
 * يستخدم GD عند توفرها، ويرجع تلقائيًا لسؤال حسابي بسيط عند عدم توفرها.
 */
final class Captcha
{
    private const SESSION_KEY = '_captcha_answer';

    public static function isImageSupported(): bool
    {
        return extension_loaded('gd') && function_exists('imagecreatetruecolor');
    }

    public static function generateAnswer(): array
    {
        $a = random_int(1, 9);
        $b = random_int(1, 9);
        Session::set(self::SESSION_KEY, (string) ($a + $b));
        return ['a' => $a, 'b' => $b];
    }

    public static function verify(?string $answer): bool
    {
        $expected = Session::get(self::SESSION_KEY);
        Session::remove(self::SESSION_KEY);
        if ($expected === null || $answer === null) {
            return false;
        }
        return trim((string) $answer) === (string) $expected;
    }

    /** يُخرج صورة PNG للرمز الحالي (يُستدعى من نقطة اتصال منفصلة captcha.php) */
    public static function renderImage(int $a, int $b): void
    {
        $text = $a . ' + ' . $b . ' = ?';
        $width = 150;
        $height = 50;
        $image = imagecreatetruecolor($width, $height);
        $bg = imagecolorallocate($image, 245, 247, 250);
        $fg = imagecolorallocate($image, 40, 60, 90);
        imagefilledrectangle($image, 0, 0, $width, $height, $bg);

        for ($i = 0; $i < 6; $i++) {
            $lineColor = imagecolorallocate($image, random_int(180, 220), random_int(180, 220), random_int(180, 220));
            imageline($image, random_int(0, $width), random_int(0, $height), random_int(0, $width), random_int(0, $height), $lineColor);
        }

        imagestring($image, 5, 20, 16, $text, $fg);

        header('Content-Type: image/png');
        header('Cache-Control: no-store, no-cache, must-revalidate');
        imagepng($image);
        imagedestroy($image);
    }
}
