<?php
/** رسائل الزوار للإدارة (نموذج المراسلة العام) */
return function (): void {
    $table = \Core\Database::table('contact_messages');
    \Core\Database::pdo()->exec("CREATE TABLE IF NOT EXISTS `{$table}` (
        `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
        `name` VARCHAR(150) NOT NULL,
        `phone` VARCHAR(30) NULL,
        `subject` VARCHAR(191) NULL,
        `message` TEXT NOT NULL,
        `status` ENUM('new','read') NOT NULL DEFAULT 'new',
        `created_at` DATETIME NOT NULL,
        PRIMARY KEY (`id`),
        KEY `contact_messages_status_idx` (`status`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
};
