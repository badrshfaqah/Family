<?php
/** رابط فيديو الألبوم (يوتيوب/قوقل درايف) في ألبومات المعرض */
return function (): void {
    if (!\Core\Database::tableExists('gallery_albums')) {
        return;
    }
    $table = \Core\Database::table('gallery_albums');
    if (!\Core\Database::fetchOne("SHOW COLUMNS FROM {$table} LIKE 'video_url'")) {
        \Core\Database::query("ALTER TABLE {$table} ADD COLUMN `video_url` VARCHAR(255) NULL AFTER `album_type`");
    }
};
