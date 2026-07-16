<?php

namespace Core\Admin\Controllers;

use Core\ActivityLog;
use Core\Auth;
use Core\Database;
use Core\Media;
use Core\Settings;
use Core\Support\Csrf;
use Core\Support\Request;
use Core\Support\Response;
use Core\Support\Security;
use Core\Support\Url;
use Core\Terms;
use Core\View;

final class SettingsController
{
    private array $tabs = ['identity', 'terms', 'homepage', 'directory', 'contact', 'mail', 'files', 'seo', 'maintenance'];

    public function index(array $params): void
    {
        Auth::requirePermission('system.settings');

        $tab = $params['tab'] ?? 'identity';
        if (!in_array($tab, $this->tabs, true)) {
            $tab = 'identity';
        }

        echo View::render(CORE_PATH . '/Admin/Views/layouts/admin.php', [
            'pageTitle' => 'الإعدادات',
            'contentView' => CORE_PATH . '/Admin/Views/settings/' . $tab . '.php',
            'tab' => $tab,
            'tabs' => $this->tabs,
            'settings' => Settings::all(),
        ]);
    }

    public function save(array $params): void
    {
        Auth::requirePermission('system.settings');
        Csrf::verifyRequestOrFail();

        $tab = $params['tab'] ?? 'identity';

        $fieldsByTab = [
            'identity' => ['identity_official_name', 'identity_short_name', 'identity_brief', 'identity_extended_bio', 'identity_lineage_origin', 'identity_lineage_sequence', 'identity_welcome_message', 'identity_documentation_note'],
            'terms' => ['term_mode', 'term_custom'],
            'directory' => ['directory_consent_text', 'directory_show_public_count', 'directory_city_enabled', 'directory_prevent_duplicates', 'branches_enabled'],
            'contact' => ['contact_phone', 'contact_email', 'social_whatsapp', 'social_x', 'social_instagram', 'social_snapchat', 'social_youtube', 'social_telegram'],
            'mail' => ['mail_from_name', 'mail_from_email', 'mail_smtp_host', 'mail_smtp_port', 'mail_smtp_user', 'mail_smtp_pass', 'mail_smtp_encryption'],
            'files' => ['media_max_size_mb', 'media_allowed_extensions'],
            'seo' => ['seo_default_title', 'seo_default_description'],
            'maintenance' => ['maintenance_mode', 'maintenance_message'],
        ];

        if ($tab === 'homepage') {
            $order = Request::post('sections_order', '');
            $order = array_filter(array_map('trim', explode(',', (string) $order)));
            $visibleKeys = Request::post('visible', []);
            $visible = [];
            foreach (['hero', 'next_event', 'coming_soon', 'ticker', 'news', 'events', 'calendar_mini', 'gatherings', 'announcements', 'gallery', 'quick_links', 'stats'] as $key) {
                $visible[$key] = in_array($key, (array) $visibleKeys, true);
            }
            Settings::set('home_sections_order', array_values($order));
            Settings::set('home_sections_visible', $visible);
            Settings::set('home_items_count', (string) Request::int('home_items_count', 6));
            Settings::set('ticker_enabled', Request::bool('ticker_enabled') ? '1' : '0');
        } else {
            $fields = $fieldsByTab[$tab] ?? [];
            foreach ($fields as $field) {
                if (in_array($field, ['directory_show_public_count', 'directory_city_enabled', 'directory_prevent_duplicates', 'branches_enabled', 'maintenance_mode'], true)) {
                    Settings::set($field, Request::bool($field) ? '1' : '0');
                } else {
                    Settings::set($field, Security::cleanText((string) Request::post($field, '')));
                }
            }

            if ($tab === 'identity') {
                $this->handleIdentityUpload('identity_logo', 'identity_logo_media_id');
                $this->handleIdentityUpload('identity_cover', 'identity_cover_media_id');
            }
        }

        Terms::reset();
        ActivityLog::record('settings_update', 'تحديث إعدادات: ' . $tab);
        \Core\Support\Session::flash('success', 'تم حفظ الإعدادات بنجاح.');
        Response::redirect(Url::admin('settings/' . $tab));
    }

    private function handleIdentityUpload(string $inputName, string $settingKey): void
    {
        $file = Request::file($inputName);
        if (!$file) {
            return;
        }
        try {
            $data = Media::handleUpload($file, Auth::user()['id'] ?? null);
            $mediaId = Database::insert('media', array_merge($data, ['created_at' => date('Y-m-d H:i:s')]));
            Settings::set($settingKey, (string) $mediaId);
        } catch (\Throwable $e) {
            \Core\Support\Session::flash('error', $e->getMessage());
        }
    }
}
