<?php

namespace Core;

/**
 * إشعارات Web Push بدون أي مكتبات خارجية:
 * توليد مفاتيح VAPID، توقيع JWT (ES256)، تشفير الحمولة (RFC 8291 / aes128gcm)،
 * والإرسال عبر cURL لخدمات الدفع (FCM / Mozilla / Apple ...).
 * يتطلب امتداد OpenSSL مع openssl_pkey_derive وhash_hkdf (PHP 7.3+).
 */
final class Push
{
    private const EC_DER_PREFIX = '3059301306072a8648ce3d020106082a8648ce3d030107034200';

    public static function isSupported(): bool
    {
        return function_exists('openssl_pkey_derive') && function_exists('hash_hkdf') && function_exists('curl_init');
    }

    /** يضمن وجود مفاتيح VAPID في الإعدادات ويعيد المفتاح العام (بترميز base64url) */
    public static function ensureVapidKeys(): string
    {
        $public = (string) Settings::get('push_vapid_public', '');
        $privatePem = (string) Settings::get('push_vapid_private_pem', '');
        if ($public !== '' && $privatePem !== '') {
            return $public;
        }

        $key = openssl_pkey_new([
            'curve_name' => 'prime256v1',
            'private_key_type' => OPENSSL_KEYTYPE_EC,
        ]);
        if (!$key) {
            throw new \RuntimeException('تعذر توليد مفاتيح VAPID (OpenSSL EC غير متاح).');
        }

        openssl_pkey_export($key, $pem);
        $details = openssl_pkey_get_details($key);
        $point = "\x04" . str_pad($details['ec']['x'], 32, "\0", STR_PAD_LEFT) . str_pad($details['ec']['y'], 32, "\0", STR_PAD_LEFT);
        $public = self::b64url($point);

        Settings::set('push_vapid_public', $public);
        Settings::set('push_vapid_private_pem', $pem);

        return $public;
    }

    public static function publicKey(): string
    {
        return (string) Settings::get('push_vapid_public', '');
    }

    public static function isSafeEndpoint(string $endpoint): bool
    {
        return self::resolveSafeEndpoint($endpoint) !== null;
    }

    /**
     * إرسال إشعار لكل المشتركين. يعيد [sent, failed, removed].
     * يحذف الاشتراكات الميتة (404/410) تلقائيًا.
     */
    public static function sendToAll(array $payload): array
    {
        $rows = Database::fetchAll('SELECT * FROM ' . Database::table('push_subscriptions'));
        $sent = 0;
        $failed = 0;
        $removed = 0;

        foreach ($rows as $row) {
            try {
                $code = self::sendToSubscription($row, $payload);
                if (in_array($code, [404, 410], true)) {
                    Database::delete('push_subscriptions', ['id' => $row['id']]);
                    $removed++;
                } elseif ($code >= 200 && $code < 300) {
                    $sent++;
                } else {
                    $failed++;
                    Support\Logger::error('push send non-2xx', ['code' => $code, 'endpoint' => substr($row['endpoint'], 0, 60)]);
                }
            } catch (\Throwable $e) {
                $failed++;
                Support\Logger::error('push send exception', ['error' => $e->getMessage()]);
            }
        }

        return ['sent' => $sent, 'failed' => $failed, 'removed' => $removed];
    }

    /** إرسال إشعار لاشتراك واحد ويعيد رمز حالة HTTP من خدمة الدفع */
    public static function sendToSubscription(array $sub, array $payload): int
    {
        $endpoint = $sub['endpoint'];
        $resolved = self::resolveSafeEndpoint($endpoint);
        if ($resolved === null) {
            Support\Logger::error('unsafe push endpoint rejected', ['endpoint' => substr($endpoint, 0, 100)]);
            return 410;
        }
        $userPublic = self::b64urlDecode($sub['p256dh']);
        $userAuth = self::b64urlDecode($sub['auth']);
        if (strlen($userPublic) !== 65 || strlen($userAuth) < 16) {
            return 410;
        }

        $body = self::encryptPayload(json_encode($payload, JSON_UNESCAPED_UNICODE), $userPublic, $userAuth);
        $jwt = self::vapidJwt(parse_url($endpoint, PHP_URL_SCHEME) . '://' . parse_url($endpoint, PHP_URL_HOST));

        $ch = curl_init($endpoint);
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $body,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 15,
            CURLOPT_CONNECTTIMEOUT => 5,
            CURLOPT_PROTOCOLS => CURLPROTO_HTTPS,
            CURLOPT_REDIR_PROTOCOLS => CURLPROTO_HTTPS,
            CURLOPT_FOLLOWLOCATION => false,
            CURLOPT_RESOLVE => [$resolved['host'] . ':443:' . $resolved['ip']],
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/octet-stream',
                'Content-Encoding: aes128gcm',
                'Content-Length: ' . strlen($body),
                'TTL: 86400',
                'Urgency: high',
                'Authorization: vapid t=' . $jwt . ', k=' . self::publicKey(),
            ],
        ]);
        curl_exec($ch);
        $code = (int) curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
        curl_close($ch);

        return $code;
    }

    /** يحظر loopback والشبكات الخاصة ويثبّت نتيجة DNS لمنع DNS rebinding. */
    private static function resolveSafeEndpoint(string $endpoint): ?array
    {
        $parts = parse_url($endpoint);
        if (!is_array($parts) || strtolower((string) ($parts['scheme'] ?? '')) !== 'https'
            || empty($parts['host']) || (isset($parts['port']) && (int) $parts['port'] !== 443)
            || isset($parts['user']) || isset($parts['pass'])) {
            return null;
        }

        $host = strtolower(rtrim((string) $parts['host'], '.'));
        $ips = filter_var($host, FILTER_VALIDATE_IP) ? [$host] : (gethostbynamel($host) ?: []);
        foreach ($ips as $ip) {
            if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                return ['host' => $host, 'ip' => $ip];
            }
        }
        return null;
    }

    /** تشفير الحمولة وفق RFC 8291 (aes128gcm) */
    private static function encryptPayload(string $plaintext, string $userPublic, string $userAuth): string
    {
        // مفتاح مؤقت للخادم (ephemeral)
        $asKey = openssl_pkey_new(['curve_name' => 'prime256v1', 'private_key_type' => OPENSSL_KEYTYPE_EC]);
        $asDetails = openssl_pkey_get_details($asKey);
        $asPublic = "\x04" . str_pad($asDetails['ec']['x'], 32, "\0", STR_PAD_LEFT) . str_pad($asDetails['ec']['y'], 32, "\0", STR_PAD_LEFT);

        // ECDH مع مفتاح المشترك
        $peerPem = self::pointToPem($userPublic);
        $sharedSecret = openssl_pkey_derive(openssl_pkey_get_public($peerPem), $asKey, 32);
        if ($sharedSecret === false) {
            throw new \RuntimeException('فشل اشتقاق سر ECDH');
        }

        $keyInfo = "WebPush: info\0" . $userPublic . $asPublic;
        $ikm = hash_hkdf('sha256', $sharedSecret, 32, $keyInfo, $userAuth);

        $salt = random_bytes(16);
        $cek = hash_hkdf('sha256', $ikm, 16, "Content-Encoding: aes128gcm\0", $salt);
        $nonce = hash_hkdf('sha256', $ikm, 12, "Content-Encoding: nonce\0", $salt);

        $padded = $plaintext . "\x02";
        $tag = '';
        $ciphertext = openssl_encrypt($padded, 'aes-128-gcm', $cek, OPENSSL_RAW_DATA, $nonce, $tag, '', 16);
        if ($ciphertext === false) {
            throw new \RuntimeException('فشل تشفير الحمولة');
        }

        // ترويسة السجل: salt(16) + rs(4) + idlen(1) + المفتاح العام للخادم(65)
        return $salt . pack('N', 4096) . chr(65) . $asPublic . $ciphertext . $tag;
    }

    /** توقيع JWT بخوارزمية ES256 لترويسة VAPID */
    private static function vapidJwt(string $audience): string
    {
        $privatePem = (string) Settings::get('push_vapid_private_pem', '');
        $header = self::b64url(json_encode(['typ' => 'JWT', 'alg' => 'ES256']));
        $claims = self::b64url(json_encode([
            'aud' => $audience,
            'exp' => time() + 43200,
            'sub' => 'mailto:' . (Settings::get('contact_email') ?: 'admin@' . (parse_url(Support\Url::origin(), PHP_URL_HOST) ?: 'localhost')),
        ]));

        $data = $header . '.' . $claims;
        openssl_sign($data, $derSig, $privatePem, 'sha256');

        return $data . '.' . self::b64url(self::derToRaw($derSig));
    }

    /** تحويل توقيع DER إلى الصيغة الخام r||s (64 بايت) المطلوبة في JWT */
    private static function derToRaw(string $der): string
    {
        $offset = 2;
        if (ord($der[1]) > 0x80) {
            $offset += ord($der[1]) - 0x80;
        }

        $extract = function () use ($der, &$offset): string {
            $offset++; // 0x02
            $len = ord($der[$offset++]);
            $int = substr($der, $offset, $len);
            $offset += $len;
            $int = ltrim($int, "\0");
            return str_pad($int, 32, "\0", STR_PAD_LEFT);
        };

        return $extract() . $extract();
    }

    /** تحويل نقطة EC عامة (65 بايت) إلى مفتاح PEM */
    private static function pointToPem(string $point): string
    {
        $der = hex2bin(self::EC_DER_PREFIX) . $point;
        return "-----BEGIN PUBLIC KEY-----\n" . chunk_split(base64_encode($der), 64, "\n") . "-----END PUBLIC KEY-----\n";
    }

    private static function b64url(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    private static function b64urlDecode(string $data): string
    {
        return (string) base64_decode(strtr($data, '-_', '+/'));
    }
}
