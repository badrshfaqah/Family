<?php
/** ربط ألبوم المعرض بمناسبة من الرزنامة لعرض التغطية (صور وفيديو) في صفحة المناسبة */
return function (): void {
    if (!\Core\Database::tableExists('gallery_albums')) {
        return;
    }
    $table = \Core\Database::table('gallery_albums');
    if (!\Core\Database::fetchOne("SHOW COLUMNS FROM {$table} LIKE 'related_calendar_id'")) {
        \Core\Database::query("ALTER TABLE {$table} ADD COLUMN `related_calendar_id` INT UNSIGNED NULL AFTER `related_event_id`");
    }
};
