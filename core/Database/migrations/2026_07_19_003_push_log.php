<?php
/** سجل التنبيهات المرسلة مع اسم المرسل ونتائج الإرسال */
return function (): void {
    $table = \Core\Database::table('push_log');
    \Core\Database::pdo()->exec("CREATE TABLE IF NOT EXISTS `{$table}` (
        `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
        `title` VARCHAR(120) NOT NULL,
        `body` VARCHAR(255) NULL,
        `url` VARCHAR(255) NULL,
        `sent_by` INT UNSIGNED NULL,
        `sent_by_name` VARCHAR(150) NOT NULL DEFAULT '',
        `sent_count` INT UNSIGNED NOT NULL DEFAULT 0,
        `failed_count` INT UNSIGNED NOT NULL DEFAULT 0,
        `removed_count` INT UNSIGNED NOT NULL DEFAULT 0,
        `created_at` DATETIME NOT NULL,
        PRIMARY KEY (`id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
};
