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
            // مدير النظام: كل الصلاحيات دائمًا (ولا يمكن تقييده من واجهة الأدوار)
            'system_admin' => $all,
            // مدير الموقع: كل شيء عدا إدارة المستخدمين والإضافات والنسخ الاحتياطي
            'site_manager' => array_values(array_merge(
                array_diff($all, ['system.users', 'system.modules', 'system.backup']),
                ['content.obituaries']
            )),
            // محتوى: إدارة المحتوى فقط دون بيانات جوال العائلة أو أي إعدادات نظام
            'content_editor' => [
                'content.news', 'content.events', 'content.gatherings', 'content.obituaries',
                'content.pages', 'content.media', 'content.archive', 'content.announcements',
                'content.tree', 'reports.view',
            ],
        ];
    }
}
