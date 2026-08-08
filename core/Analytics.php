<?php

namespace Core;

/**
 * إحصائيات زوار خاصة بالموقع (بدون أي خدمة خارجية):
 * تسجّل كل زيارة صفحة عامة مع تمييز الزائر اليومي ببصمة مجهّلة
 * (لا يُخزن عنوان IP إطلاقًا — فقط ناتج تجزئة يتغير كل يوم).
 * التتبع لا يُسقط الصفحة أبدًا مهما حدث.
 */
final class Analytics
{
    public static function track(): void
    {
        try {
            if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'GET') {
                return;
            }
            if (!Database::tableExists('page_views')) {
                return;
            }

            $path = Support\Request::path();

            // استثناء الملفات الخدمية والمسارات غير الصفحية
            $skipExact = ['/sw.js', '/manifest.webmanifest', '/robots.txt', '/sitemap.xml', '/captcha.png'];
            if (in_array($path, $skipExact, true) || preg_match('~^/(push|storage|pwa-icon|admin|install)~', $path)) {
                return;
            }

            // استثناء الزواحف وبوتات المعاينة
            $ua = (string) ($_SERVER['HTTP_USER_AGENT'] ?? '');
            if ($ua === '' || preg_match('~bot|crawl|spider|slurp|curl|wget|python|httpclient|monitor|facebookexternalhit|whatsapp|telegram|preview~i', $ua)) {
                return;
            }

            // بصمة زائر يومية مجهّلة: تجزئة (IP + المتصفح + اليوم) دون تخزين أي منها
            $visitor = substr(hash('sha256', Support\Request::ip() . '|' . $ua . '|' . date('Y-m-d')), 0, 40);
            $device = preg_match('~Mobi|Android|iPhone|iPad~i', $ua) ? 'mobile' : 'desktop';

            // مصدر الدخول: النطاقات الخارجية فقط
            $referrer = null;
            $refRaw = (string) ($_SERVER['HTTP_REFERER'] ?? '');
            if ($refRaw !== '') {
                $refHost = strtolower((string) parse_url($refRaw, PHP_URL_HOST));
                $ownHost = strtolower((string) parse_url(Support\Url::origin(), PHP_URL_HOST));
                $refHost = preg_replace('~^www\.~', '', $refHost) ?? $refHost;
                $ownHost = preg_replace('~^www\.~', '', $ownHost) ?? $ownHost;
                if ($refHost !== '' && $refHost !== $ownHost) {
                    $referrer = mb_substr($refHost, 0, 120);
                }
            }

            Database::insert('page_views', [
                'path' => mb_substr($path, 0, 191),
                'visitor' => $visitor,
                'device' => $device,
                'referrer' => $referrer,
                'viewed_on' => date('Y-m-d'),
                'viewed_at' => date('Y-m-d H:i:s'),
            ]);
        } catch (\Throwable $e) {
            // الإحصائيات ثانوية: أي خلل فيها لا يمس تجربة الزائر
        }
    }
}
