<?php

namespace Core\Admin\Controllers;

use Core\ActivityLog;
use Core\Auth;
use Core\Database;
use Core\Push;
use Core\Support\Csrf;
use Core\Support\Request;
use Core\Support\Response;
use Core\Support\Security;
use Core\Support\Session;
use Core\Support\Url;
use Core\View;

final class NotificationsController
{
    public function index(array $params): void
    {
        Auth::requirePermission('system.settings');

        $supported = Push::isSupported();
        if ($supported) {
            try {
                Push::ensureVapidKeys();
            } catch (\Throwable $e) {
                $supported = false;
            }
        }

        $subscribers = 0;
        if (Database::tableExists('push_subscriptions')) {
            $subscribers = (int) Database::fetchValue('SELECT COUNT(*) FROM ' . Database::table('push_subscriptions'));
        }

        $history = [];
        if (Database::tableExists('push_log')) {
            $history = Database::fetchAll('SELECT * FROM ' . Database::table('push_log') . ' ORDER BY id DESC LIMIT 30');
        }

        echo View::render(CORE_PATH . '/Admin/Views/layouts/admin.php', [
            'pageTitle' => 'التنبيهات',
            'contentView' => CORE_PATH . '/Admin/Views/notifications/index.php',
            'supported' => $supported,
            'subscribers' => $subscribers,
            'history' => $history,
        ]);
    }

    public function send(array $params): void
    {
        Auth::requirePermission('system.settings');
        Csrf::verifyRequestOrFail();

        $title = Security::cleanText(Request::trimmed('title'));
        $body = Security::cleanText(Request::trimmed('body'));
        $url = Security::cleanText(Request::trimmed('url'));

        if ($title === '') {
            Session::flash('error', 'عنوان التنبيه مطلوب.');
            Response::redirect(Url::admin('notifications'));
        }

        $targetUrl = Url::origin() . Url::to('');
        if ($url !== '') {
            $targetUrl = str_starts_with($url, 'http') ? $url : Url::origin() . Url::to(ltrim($url, '/'));
        }

        $result = Push::sendToAll([
            'title' => $title,
            'body' => $body,
            'url' => $targetUrl,
            'icon' => Url::origin() . Url::to('pwa-icon-192.png'),
        ]);

        // حفظ التنبيه في السجل مع اسم من أرسله
        if (Database::tableExists('push_log')) {
            $user = Auth::user();
            Database::insert('push_log', [
                'title' => $title,
                'body' => $body !== '' ? $body : null,
                'url' => $url !== '' ? $url : null,
                'sent_by' => $user['id'] ?? null,
                'sent_by_name' => $user['name'] ?? 'النظام',
                'sent_count' => $result['sent'],
                'failed_count' => $result['failed'],
                'removed_count' => $result['removed'],
                'created_at' => date('Y-m-d H:i:s'),
            ]);
        }

        ActivityLog::record('push_send', "إرسال تنبيه: {$title} (نجح {$result['sent']}، فشل {$result['failed']}، حُذف {$result['removed']})");

        Session::flash('success', "تم الإرسال: وصل إلى {$result['sent']} جهازًا" .
            ($result['failed'] ? "، وفشل {$result['failed']}" : '') .
            ($result['removed'] ? "، وحُذف {$result['removed']} اشتراكًا منتهيًا" : '') . '.');
        Response::redirect(Url::admin('notifications'));
    }
}
