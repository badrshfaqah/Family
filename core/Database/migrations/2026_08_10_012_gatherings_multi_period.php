<?php
/** الجمعات: توسيع عمود فترة الوقت لاستيعاب أكثر من فترة (CSV) — اختيار متعدد */
return function (): void {
    if (!\Core\Database::tableExists('gatherings')) {
        return;
    }
    $table = \Core\Database::table('gatherings');
    if (\Core\Database::fetchOne("SHOW COLUMNS FROM {$table} LIKE 'time_period'")) {
        \Core\Database::query("ALTER TABLE {$table} MODIFY `time_period` VARCHAR(120) NULL");
    }
};
