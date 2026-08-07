<?php
/** جدول اشتراكات تنبيهات Web Push */
return function (): void {
    $table = \Core\Database::table('push_subscriptions');
    \Core\Database::pdo()->exec("CREATE TABLE IF NOT EXISTS `{$table}` (
        `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
        `endpoint` VARCHAR(500) NOT NULL,
        `endpoint_hash` CHAR(64) NOT NULL,
        `p256dh` VARCHAR(255) NOT NULL,
        `auth` VARCHAR(255) NOT NULL,
        `user_agent` VARCHAR(255) NULL,
        `created_at` DATETIME NOT NULL,
        `updated_at` DATETIME NULL,
        PRIMARY KEY (`id`),
        UNIQUE KEY `push_subscriptions_endpoint_unique` (`endpoint_hash`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
};
