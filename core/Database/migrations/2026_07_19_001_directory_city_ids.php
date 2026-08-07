<?php
/** المدن المتعددة لجهات جوال العائلة: عمود city_ids (CSV) بجانب city_id */
return function (): void {
    if (!\Core\Database::tableExists('directory_contacts')) {
        return;
    }
    $table = \Core\Database::table('directory_contacts');
    if (!\Core\Database::fetchOne("SHOW COLUMNS FROM {$table} LIKE 'city_ids'")) {
        \Core\Database::query("ALTER TABLE {$table} ADD COLUMN `city_ids` VARCHAR(191) NULL AFTER `city_id`");
    }
};
