<?php
/** مقترحات الزوار: حالة (pending) للمناسبات والجمعات مع بيانات المرسل للتحقق */
return function (): void {
    if (\Core\Database::tableExists('calendar_entries')) {
        $t = \Core\Database::table('calendar_entries');
        \Core\Database::query("ALTER TABLE {$t} MODIFY `status` ENUM('draft','published','cancelled','pending') NOT NULL DEFAULT 'draft'");
        if (!\Core\Database::fetchOne("SHOW COLUMNS FROM {$t} LIKE 'submitted_name'")) {
            \Core\Database::query("ALTER TABLE {$t} ADD COLUMN `submitted_name` VARCHAR(150) NULL, ADD COLUMN `submitted_phone` VARCHAR(30) NULL");
        }
    }
    if (\Core\Database::tableExists('gatherings')) {
        $t = \Core\Database::table('gatherings');
        \Core\Database::query("ALTER TABLE {$t} MODIFY `status` ENUM('active','paused','ended','pending') NOT NULL DEFAULT 'active'");
        if (!\Core\Database::fetchOne("SHOW COLUMNS FROM {$t} LIKE 'submitted_name'")) {
            \Core\Database::query("ALTER TABLE {$t} ADD COLUMN `submitted_name` VARCHAR(150) NULL, ADD COLUMN `submitted_phone` VARCHAR(30) NULL");
        }
    }
};
