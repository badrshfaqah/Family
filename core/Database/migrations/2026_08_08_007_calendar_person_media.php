<?php
/** صورة صاحب المناسبة (العريس/الخريج...) في الرزنامة — تظهر داخل بطاقة المشاركة كصورة */
return function (): void {
    if (!\Core\Database::tableExists('calendar_entries')) {
        return;
    }
    $table = \Core\Database::table('calendar_entries');
    if (!\Core\Database::fetchOne("SHOW COLUMNS FROM {$table} LIKE 'person_media_id'")) {
        \Core\Database::query("ALTER TABLE {$table} ADD COLUMN `person_media_id` INT UNSIGNED NULL AFTER `cover_media_id`");
    }
};
