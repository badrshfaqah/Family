<?php

namespace Core;

/**
 * كتالوج الصلاحيات المتاحة في النظام. كل إضافة يمكن أن تضيف صلاحياتها الخاصة
 * عبر ModuleManager::registerPermissions() أثناء التثبيت.
 */
final class Permissions
{
    public static function catalog(): array
    {
        return [
            'system' => [
                'label' => 'النظام والإعدادات',
                'items' => [
                    'system.settings' => 'إدارة الإعدادات العامة',
                    'system.users' => 'إدارة المستخدمين الإداريين والأدوار',
                    'system.modules' => 'إدارة الإضافات',
                    'system.backup' => 'إدارة النسخ الاحتياطي والاستعادة',
                    'system.logs' => 'مشاهدة سجل العمليات',
                ],
            ],
            'content' => [
                'label' => 'المحتوى',
                'items' => [
                    'content.news' => 'إدارة الأخبار',
                    'content.events' => 'إدارة المناسبات والرزنامة',
                    'content.gatherings' => 'إدارة الجمعات',
                    'content.pages' => 'إدارة الصفحات والقوائم',
                    'content.media' => 'إدارة الوسائط والمعرض',
                    'content.archive' => 'إدارة الأرشيف التاريخي',
                    'content.announcements' => 'إدارة الإعلانات والتنبيهات',
                    'content.tree' => 'إدارة شجرة النسب والفروع',
                ],
            ],
            'communication' => [
                'label' => 'التواصل',
                'items' => [
                    'directory.view' => 'مشاهدة سجلات جوال ' . '{term}',
                    'directory.manage' => 'إدارة سجلات جوال ' . '{term}',
                    'directory.export' => 'تصدير أرقام جوال ' . '{term}',
                    'newsletter.manage' => 'إدارة القائمة البريدية',
                    'templates.manage' => 'إدارة قوالب الرسائل',
                ],
            ],
            'reports' => [
                'label' => 'التقارير',
                'items' => [
                    'reports.view' => 'مشاهدة لوحة التحكم والتقارير',
                ],
            ],
        ];
    }

    public static function allKeys(): array
    {
        $keys = [];
        foreach (self::catalog() as $group) {
            $keys = array_merge($keys, array_keys($group['items']));
        }
        return $keys;
    }

    public static function defaultRolePermissions(): array
    {
        $all = self::allKeys();

        return [
            'system_admin' => $all,
            'content_manager' => [
                'content.news', 'content.events', 'content.gatherings', 'content.pages',
                'content.media', 'content.archive', 'content.announcements', 'content.tree',
                'reports.view',
            ],
            'communication_manager' => [
                'directory.view', 'directory.manage', 'directory.export',
                'newsletter.manage', 'templates.manage', 'reports.view',
            ],
            'viewer' => ['reports.view'],
        ];
    }
}
