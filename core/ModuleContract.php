<?php

namespace Core;

/**
 * العقد الذي يجب أن تلتزم به كل إضافة عبر ملف modules/{slug}/Module.php
 */
interface ModuleContract
{
    /** ينفذ عند تثبيت الإضافة لأول مرة: إنشاء الجداول والبيانات الافتراضية */
    public function install(): void;

    /** ينفذ عند تحديث الإضافة من إصدار سابق */
    public function migrate(string $fromVersion, string $toVersion): void;

    /** ينفذ عند إزالة الإضافة. $keepData يحدد الاحتفاظ بالجداول أو حذفها */
    public function uninstall(bool $keepData): void;

    /** يسجل مسارات لوحة التحكم الخاصة بالإضافة */
    public function registerAdminRoutes(Router $router): void;

    /** يسجل مسارات الموقع العام الخاصة بالإضافة */
    public function registerPublicRoutes(Router $router): void;

    /** عناصر قائمة لوحة التحكم التي تضيفها الإضافة */
    public function adminMenu(): array;

    /** الصلاحيات الخاصة بالإضافة لإضافتها لكتالوج الصلاحيات */
    public function permissions(): array;

    /** ينفذ في كل طلب عندما تكون الإضافة مفعّلة (اختياري) */
    public function boot(): void;
}
