<?php
/** الجمعات: فترة الوقت (الصباح/بعد الصلوات) بدل من-إلى + ترتيب يدوي للعرض */
return function (): void {
    if (!\Core\Database::tableExists('gatherings')) {
        return;
    }
    $table = \Core\Database::table('gatherings');

    if (!\Core\Database::fetchOne("SHOW COLUMNS FROM {$table} LIKE 'time_period'")) {
        \Core\Database::query("ALTER TABLE {$table} ADD COLUMN `time_period` VARCHAR(30) NULL AFTER `end_time`");
    }

    if (!\Core\Database::fetchOne("SHOW COLUMNS FROM {$table} LIKE 'sort_order'")) {
        \Core\Database::query("ALTER TABLE {$table} ADD COLUMN `sort_order` INT UNSIGNED NOT NULL DEFAULT 0 AFTER `status`");
        // تثبيت الترتيب الحالي (الأحدث أولًا) كنقطة بداية للترتيب اليدوي
        $rows = \Core\Database::fetchAll("SELECT id FROM {$table} ORDER BY id DESC");
        $i = 1;
        foreach ($rows as $row) {
            \Core\Database::query("UPDATE {$table} SET sort_order = ? WHERE id = ?", [$i++, $row['id']]);
        }
    }
};
