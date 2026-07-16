<?php

namespace Core\Database\Seeds;

use Core\Database;
use Core\Permissions;

/**
 * زراعة البيانات والإعدادات الافتراضية بعد إنشاء الجداول مباشرة أثناء التثبيت.
 */
final class DefaultSeeder
{
    public static function run(array $siteData): void
    {
        self::seedRoles();
        self::seedSettings($siteData);
        self::seedMenus();
    }

    private static function seedRoles(): void
    {
        $roles = [
            ['slug' => 'system_admin', 'name' => 'مدير النظام', 'is_system' => 1],
            ['slug' => 'content_manager', 'name' => 'مدير المحتوى', 'is_system' => 1],
            ['slug' => 'communication_manager', 'name' => 'مدير التواصل', 'is_system' => 1],
            ['slug' => 'viewer', 'name' => 'مشاهد', 'is_system' => 1],
        ];

        $roleIds = [];
        foreach ($roles as $role) {
            $id = Database::insert('roles', $role + ['created_at' => date('Y-m-d H:i:s')]);
            $roleIds[$role['slug']] = $id;
        }

        $defaults = Permissions::defaultRolePermissions();
        foreach ($defaults as $slug => $permissionKeys) {
            if (!isset($roleIds[$slug])) {
                continue;
            }
            foreach ($permissionKeys as $key) {
                Database::insert('role_permissions', [
                    'role_id' => $roleIds[$slug],
                    'permission_key' => $key,
                ]);
            }
        }
    }

    private static function seedSettings(array $siteData): void
    {
        $settings = [
            'installed' => '1',
            'core_version' => CORE_VERSION,

            'term_mode' => $siteData['term_mode'] ?? 'family',
            'term_custom' => $siteData['term_custom'] ?? '',

            'identity_official_name' => $siteData['official_name'] ?? '',
            'identity_short_name' => $siteData['short_name'] ?? '',
            'identity_logo_media_id' => '',
            'identity_cover_media_id' => '',
            'identity_brief' => '',
            'identity_extended_bio' => '',
            'identity_lineage_origin' => '',
            'identity_lineage_sequence' => '',
            'identity_welcome_message' => '',
            'identity_documentation_note' => '',

            'contact_phone' => '',
            'contact_email' => '',
            'social_whatsapp' => '',
            'social_x' => '',
            'social_instagram' => '',
            'social_snapchat' => '',
            'social_youtube' => '',
            'social_telegram' => '',

            'mail_from_name' => '',
            'mail_from_email' => '',
            'mail_smtp_host' => '',
            'mail_smtp_port' => '587',
            'mail_smtp_user' => '',
            'mail_smtp_pass' => '',
            'mail_smtp_encryption' => 'tls',

            'theme_color_primary' => '#0f6e5e',
            'theme_color_secondary' => '#c9a24b',
            'theme_font' => 'default',
            'theme_style_preset' => 'calm',

            'home_sections_order' => json_encode([
                'hero', 'next_event', 'coming_soon', 'ticker', 'news', 'events',
                'calendar_mini', 'gatherings', 'announcements', 'gallery', 'quick_links', 'stats',
            ], JSON_UNESCAPED_UNICODE),
            'home_sections_visible' => json_encode([
                'hero' => true, 'next_event' => true, 'coming_soon' => true, 'ticker' => true,
                'news' => true, 'events' => true, 'calendar_mini' => true, 'gatherings' => true,
                'announcements' => true, 'gallery' => true, 'quick_links' => true, 'stats' => true,
            ], JSON_UNESCAPED_UNICODE),
            'home_stats_visible' => json_encode([
                'news' => true, 'events' => true, 'gatherings' => true, 'gallery' => true,
                'archive' => true, 'directory_count' => true,
            ], JSON_UNESCAPED_UNICODE),
            'home_items_count' => '6',
            'ticker_enabled' => '1',

            'directory_consent_text' => '',
            'directory_show_public_count' => '1',
            'directory_city_enabled' => '1',
            'directory_prevent_duplicates' => '1',

            'media_max_size_mb' => '8',
            'media_allowed_extensions' => '',

            'seo_default_title' => $siteData['official_name'] ?? '',
            'seo_default_description' => '',

            'maintenance_mode' => '0',
            'maintenance_message' => 'الموقع تحت الصيانة حاليًا، سنعود قريبًا بإذن الله.',

            'branches_enabled' => '0',
            'backup_retention' => '10',
        ];

        foreach ($settings as $key => $value) {
            Database::insert('settings', ['setting_key' => $key, 'setting_value' => $value]);
        }
    }

    private static function seedMenus(): void
    {
        $menus = [
            ['slug' => 'main', 'name' => 'القائمة الرئيسية'],
            ['slug' => 'mobile', 'name' => 'قائمة الجوال'],
            ['slug' => 'footer', 'name' => 'قائمة التذييل'],
        ];
        foreach ($menus as $menu) {
            Database::insert('menus', $menu);
        }
    }
}
