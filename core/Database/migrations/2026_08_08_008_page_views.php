<?php
/** جدول زيارات الصفحات لإحصائيات لوحة التحكم (بصمة زائر يومية مجهّلة بلا IP) */
return function (): void {
    $table = \Core\Database::table('page_views');
    \Core\Database::pdo()->exec("CREATE TABLE IF NOT EXISTS `{$table}` (
        `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
        `path` VARCHAR(191) NOT NULL,
        `visitor` CHAR(40) NOT NULL,
        `device` ENUM('mobile','desktop') NOT NULL DEFAULT 'mobile',
        `referrer` VARCHAR(120) NULL,
        `viewed_on` DATE NOT NULL,
        `viewed_at` DATETIME NOT NULL,
        PRIMARY KEY (`id`),
        KEY `page_views_on_idx` (`viewed_on`),
        KEY `page_views_path_idx` (`path`(60)),
        KEY `page_views_visitor_idx` (`visitor`, `viewed_on`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
};
