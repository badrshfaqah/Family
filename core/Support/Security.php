<?php

namespace Core\Support;

final class Security
{
    private static ?string $cspNonce = null;

    /** تهريب نص للعرض الآمن داخل HTML لمنع XSS */
    public static function e(?string $value): string
    {
        return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
    }

    /** تنظيف نص مدخل عام (يزيل الوسوم الخطرة، يبقي على نص عادي) */
    public static function cleanText(?string $value): string
    {
        $value = trim($value ?? '');
        $value = strip_tags($value);
        return $value;
    }

    /** تنظيف HTML قادم من محرر محتوى موثوق (لوحة التحكم فقط) بإزالة الوسوم الخطرة */
    public static function cleanHtml(?string $html): string
    {
        $html = $html ?? '';
        if ($html === '') {
            return '';
        }

        // لا يمكن تنقية HTML بأمان بالتعبيرات المنتظمة؛ نحلله إلى DOM ثم نسمح
        // فقط بعناصر وسمات يحتاجها محرر المحتوى.
        if (!class_exists(\DOMDocument::class)) {
            return nl2br(self::e(strip_tags($html)));
        }

        $allowed = [
            'p' => [], 'br' => [], 'div' => [], 'span' => [],
            'h2' => [], 'h3' => [], 'h4' => [], 'h5' => [], 'h6' => [],
            'strong' => [], 'b' => [], 'em' => [], 'i' => [], 'u' => [], 's' => [],
            'ul' => [], 'ol' => [], 'li' => [], 'blockquote' => [], 'hr' => [],
            'table' => [], 'thead' => [], 'tbody' => [], 'tfoot' => [], 'tr' => [],
            'th' => ['colspan', 'rowspan'], 'td' => ['colspan', 'rowspan'],
            'a' => ['href', 'title', 'target', 'rel'],
            'img' => ['src', 'alt', 'title', 'width', 'height'],
        ];

        $wrapperId = 'safe-' . bin2hex(random_bytes(8));
        $dom = new \DOMDocument('1.0', 'UTF-8');
        $previous = libxml_use_internal_errors(true);
        $loaded = $dom->loadHTML(
            '<?xml encoding="UTF-8"><div id="' . $wrapperId . '">' . $html . '</div>',
            LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD
        );
        libxml_clear_errors();
        libxml_use_internal_errors($previous);

        if (!$loaded) {
            return nl2br(self::e(strip_tags($html)));
        }

        $xpath = new \DOMXPath($dom);
        $wrapper = $xpath->query('//*[@id="' . $wrapperId . '"]')->item(0);
        if (!$wrapper instanceof \DOMElement) {
            return nl2br(self::e(strip_tags($html)));
        }

        self::sanitizeChildren($wrapper, $allowed);

        $safe = '';
        foreach ($wrapper->childNodes as $child) {
            $safe .= $dom->saveHTML($child);
        }
        return $safe;
    }

    private static function sanitizeChildren(\DOMNode $parent, array $allowed): void
    {
        foreach (iterator_to_array($parent->childNodes) as $node) {
            if ($node instanceof \DOMComment || $node instanceof \DOMProcessingInstruction) {
                $parent->removeChild($node);
                continue;
            }
            if (!$node instanceof \DOMElement) {
                continue;
            }

            $tag = strtolower($node->tagName);
            if (!array_key_exists($tag, $allowed)) {
                // نحذف العنصر ومحتواه معًا حتى لا تتحول محتويات iframe/srcdoc أو
                // script/style إلى بنية فعالة بعد فك التغليف.
                $parent->removeChild($node);
                continue;
            }

            $permittedAttributes = $allowed[$tag];
            foreach (iterator_to_array($node->attributes) as $attribute) {
                $name = strtolower($attribute->name);
                if (!in_array($name, $permittedAttributes, true)) {
                    $node->removeAttribute($attribute->name);
                    continue;
                }

                if (($name === 'href' || $name === 'src') && !self::isSafeUrl($attribute->value, $tag === 'img')) {
                    $node->removeAttribute($attribute->name);
                    continue;
                }

                if (in_array($name, ['width', 'height', 'colspan', 'rowspan'], true)) {
                    $value = (int) $attribute->value;
                    if ($value < 1 || $value > 5000) {
                        $node->removeAttribute($attribute->name);
                    } else {
                        $node->setAttribute($name, (string) $value);
                    }
                }
            }

            if ($tag === 'a' && strtolower($node->getAttribute('target')) === '_blank') {
                $node->setAttribute('rel', 'noopener noreferrer');
            }

            self::sanitizeChildren($node, $allowed);
        }
    }

    private static function isSafeUrl(string $url, bool $isImage): bool
    {
        $url = trim(html_entity_decode($url, ENT_QUOTES | ENT_HTML5, 'UTF-8'));
        $url = (string) preg_replace('/[\x00-\x20\x7F]+/u', '', $url);
        if ($url === '' || str_starts_with($url, '#') || str_starts_with($url, '/') || str_starts_with($url, './') || str_starts_with($url, '../')) {
            return true;
        }

        $scheme = strtolower((string) parse_url($url, PHP_URL_SCHEME));
        $allowedSchemes = $isImage ? ['http', 'https'] : ['http', 'https', 'mailto', 'tel'];
        return in_array($scheme, $allowedSchemes, true);
    }

    public static function hashPassword(string $plain): string
    {
        return password_hash($plain, PASSWORD_DEFAULT);
    }

    public static function cspNonce(): string
    {
        if (self::$cspNonce === null) {
            self::$cspNonce = base64_encode(random_bytes(18));
        }
        return self::$cspNonce;
    }

    public static function verifyPassword(string $plain, string $hash): bool
    {
        return password_verify($plain, $hash);
    }

    public static function isSafeFilename(string $name): bool
    {
        return (bool) preg_match('/^[\p{Arabic}a-zA-Z0-9_\-\. ]+$/u', $name);
    }

    public static function sendSecurityHeaders(): void
    {
        if (headers_sent()) {
            return;
        }
        header('X-Content-Type-Options: nosniff');
        header('X-Frame-Options: SAMEORIGIN');
        header('Referrer-Policy: strict-origin-when-cross-origin');
        header('Permissions-Policy: camera=(), microphone=(), geolocation=()');
        $nonce = self::cspNonce();
        header("Content-Security-Policy: default-src 'self'; "
            . "script-src 'self' 'nonce-{$nonce}'; "
            . "style-src 'self' 'unsafe-inline' https://fonts.googleapis.com; "
            . "font-src 'self' data: https://fonts.gstatic.com; "
            . "img-src 'self' data: https:; connect-src 'self' https:; "
            . "object-src 'none'; "
            . "frame-src 'self' https://www.youtube-nocookie.com https://www.youtube.com https://drive.google.com; "
            . "base-uri 'self'; "
            . "form-action 'self'; frame-ancestors 'self'");
        $configuredHttps = defined('APP_URL') && strtolower((string) parse_url((string) APP_URL, PHP_URL_SCHEME)) === 'https';
        if ($configuredHttps || (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')) {
            header('Strict-Transport-Security: max-age=31536000');
        }
    }
}
