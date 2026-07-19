<?php

namespace Core;

use Core\Support\Session;

/** مصادقة ثنائية TOTP متوافقة مع تطبيقات Authenticator مع رموز استرداد أحادية الاستخدام. */
final class TwoFactor
{
    private const PENDING_USER_KEY = '_2fa_pending_user';
    private const PENDING_EXPIRES_KEY = '_2fa_pending_expires';
    private const BASE32_ALPHABET = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';

    public static function ensureTable(): void
    {
        $table = Database::table('admin_user_mfa');
        Database::pdo()->exec(
            "CREATE TABLE IF NOT EXISTS `{$table}` (
                `user_id` INT UNSIGNED NOT NULL,
                `secret_encrypted` TEXT NOT NULL,
                `recovery_codes` TEXT NOT NULL,
                `enabled_at` DATETIME NOT NULL,
                `updated_at` DATETIME NOT NULL,
                PRIMARY KEY (`user_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
        );
    }

    public static function enabled(int $userId): bool
    {
        self::ensureTable();
        return (bool) Database::fetchValue(
            'SELECT COUNT(*) FROM ' . Database::table('admin_user_mfa') . ' WHERE user_id = ?',
            [$userId]
        );
    }

    public static function beginChallenge(int $userId): void
    {
        Session::set(self::PENDING_USER_KEY, $userId);
        Session::set(self::PENDING_EXPIRES_KEY, time() + 300);
    }

    public static function pendingUserId(): ?int
    {
        $id = (int) Session::get(self::PENDING_USER_KEY, 0);
        $expires = (int) Session::get(self::PENDING_EXPIRES_KEY, 0);
        if ($id < 1 || $expires < time()) {
            self::clearChallenge();
            return null;
        }
        return $id;
    }

    public static function clearChallenge(): void
    {
        Session::remove(self::PENDING_USER_KEY);
        Session::remove(self::PENDING_EXPIRES_KEY);
    }

    public static function generateSecret(): string
    {
        return self::base32Encode(random_bytes(20));
    }

    public static function provisioningUri(string $secret, string $account, string $issuer): string
    {
        $issuer = trim($issuer) !== '' ? trim($issuer) : 'Family CMS';
        $label = rawurlencode($issuer . ':' . $account);
        return 'otpauth://totp/' . $label . '?secret=' . rawurlencode($secret)
            . '&issuer=' . rawurlencode($issuer) . '&algorithm=SHA1&digits=6&period=30';
    }

    /** @return string[] رموز الاسترداد الصريحة؛ تُعرض مرة واحدة فقط. */
    public static function enable(int $userId, string $secret): array
    {
        if (!self::verifyTotp($secret, '000000') && self::base32Decode($secret) === '') {
            throw new \RuntimeException('مفتاح المصادقة الثنائية غير صالح.');
        }

        self::ensureTable();
        $codes = [];
        $hashes = [];
        for ($i = 0; $i < 8; $i++) {
            $raw = strtoupper(bin2hex(random_bytes(4)));
            $code = substr($raw, 0, 4) . '-' . substr($raw, 4, 4);
            $codes[] = $code;
            $hashes[] = password_hash(self::normalizeRecoveryCode($code), PASSWORD_DEFAULT);
        }

        Database::query(
            'INSERT INTO ' . Database::table('admin_user_mfa') . '
             (user_id, secret_encrypted, recovery_codes, enabled_at, updated_at)
             VALUES (?, ?, ?, NOW(), NOW())
             ON DUPLICATE KEY UPDATE secret_encrypted = VALUES(secret_encrypted), recovery_codes = VALUES(recovery_codes), enabled_at = NOW(), updated_at = NOW()',
            [$userId, self::encrypt($secret), json_encode($hashes)]
        );
        return $codes;
    }

    public static function verifyForUser(int $userId, string $code): bool
    {
        self::ensureTable();
        $row = Database::fetchOne(
            'SELECT secret_encrypted, recovery_codes FROM ' . Database::table('admin_user_mfa') . ' WHERE user_id = ?',
            [$userId]
        );
        if (!$row) {
            return false;
        }

        $digits = preg_replace('/\D+/', '', $code) ?? '';
        if (strlen($digits) === 6 && self::verifyTotp(self::decrypt($row['secret_encrypted']), $digits)) {
            return true;
        }

        $candidate = self::normalizeRecoveryCode($code);
        $hashes = json_decode((string) $row['recovery_codes'], true);
        if ($candidate === '' || !is_array($hashes)) {
            return false;
        }
        foreach ($hashes as $index => $hash) {
            if (is_string($hash) && password_verify($candidate, $hash)) {
                unset($hashes[$index]);
                Database::update('admin_user_mfa', [
                    'recovery_codes' => json_encode(array_values($hashes)),
                    'updated_at' => date('Y-m-d H:i:s'),
                ], ['user_id' => $userId]);
                return true;
            }
        }
        return false;
    }

    public static function verifyTotp(string $secret, string $code, ?int $time = null): bool
    {
        if (!preg_match('/^\d{6}$/', $code)) {
            return false;
        }
        $key = self::base32Decode($secret);
        if ($key === '') {
            return false;
        }
        $counter = intdiv($time ?? time(), 30);
        for ($offset = -1; $offset <= 1; $offset++) {
            $high = intdiv($counter + $offset, 0x100000000);
            $low = ($counter + $offset) & 0xFFFFFFFF;
            $hash = hash_hmac('sha1', pack('N2', $high, $low), $key, true);
            $position = ord($hash[19]) & 0x0F;
            $binary = unpack('N', substr($hash, $position, 4))[1] & 0x7FFFFFFF;
            $expected = str_pad((string) ($binary % 1000000), 6, '0', STR_PAD_LEFT);
            if (hash_equals($expected, $code)) {
                return true;
            }
        }
        return false;
    }

    public static function disable(int $userId): void
    {
        self::ensureTable();
        Database::delete('admin_user_mfa', ['user_id' => $userId]);
    }

    private static function encrypt(string $plaintext): string
    {
        $key = hash('sha256', defined('SECURE_AUTH_KEY') ? SECURE_AUTH_KEY : AUTH_KEY, true);
        $iv = random_bytes(12);
        $tag = '';
        $ciphertext = openssl_encrypt($plaintext, 'aes-256-gcm', $key, OPENSSL_RAW_DATA, $iv, $tag);
        if ($ciphertext === false) {
            throw new \RuntimeException('تعذر تشفير مفتاح المصادقة الثنائية.');
        }
        return base64_encode($iv . $tag . $ciphertext);
    }

    private static function decrypt(string $encoded): string
    {
        $blob = base64_decode($encoded, true);
        if ($blob === false || strlen($blob) < 29) {
            return '';
        }
        $key = hash('sha256', defined('SECURE_AUTH_KEY') ? SECURE_AUTH_KEY : AUTH_KEY, true);
        $plain = openssl_decrypt(substr($blob, 28), 'aes-256-gcm', $key, OPENSSL_RAW_DATA, substr($blob, 0, 12), substr($blob, 12, 16));
        return $plain === false ? '' : $plain;
    }

    private static function normalizeRecoveryCode(string $code): string
    {
        return strtoupper(preg_replace('/[^A-Fa-f0-9]/', '', $code) ?? '');
    }

    private static function base32Encode(string $data): string
    {
        $bits = '';
        foreach (str_split($data) as $char) {
            $bits .= str_pad(decbin(ord($char)), 8, '0', STR_PAD_LEFT);
        }
        $out = '';
        foreach (str_split($bits, 5) as $chunk) {
            $out .= self::BASE32_ALPHABET[bindec(str_pad($chunk, 5, '0'))];
        }
        return $out;
    }

    private static function base32Decode(string $encoded): string
    {
        $encoded = strtoupper(preg_replace('/[^A-Z2-7]/i', '', $encoded) ?? '');
        if ($encoded === '') {
            return '';
        }
        $bits = '';
        foreach (str_split($encoded) as $char) {
            $value = strpos(self::BASE32_ALPHABET, $char);
            if ($value === false) {
                return '';
            }
            $bits .= str_pad(decbin($value), 5, '0', STR_PAD_LEFT);
        }
        $out = '';
        foreach (str_split($bits, 8) as $chunk) {
            if (strlen($chunk) === 8) {
                $out .= chr(bindec($chunk));
            }
        }
        return $out;
    }
}
