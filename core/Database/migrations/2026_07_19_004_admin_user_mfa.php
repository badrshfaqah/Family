<?php
/** جدول المصادقة الثنائية للمستخدمين الإداريين */
return function (): void {
    $table = \Core\Database::table('admin_user_mfa');
    \Core\Database::pdo()->exec("CREATE TABLE IF NOT EXISTS `{$table}` (
        `user_id` INT UNSIGNED NOT NULL,
        `secret_encrypted` TEXT NOT NULL,
        `recovery_codes` TEXT NOT NULL,
        `enabled_at` DATETIME NOT NULL,
        `updated_at` DATETIME NOT NULL,
        PRIMARY KEY (`user_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
};
