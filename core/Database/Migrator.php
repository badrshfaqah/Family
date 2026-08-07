<?php

namespace Core\Database;

use Core\Database;

/**
 * نظام ترحيلات قاعدة البيانات: ملفات مرقمة في core/Database/migrations تُطبق
 * بالترتيب مرة واحدة فقط، ويُسجل المطبق منها في جدول schema_migrations.
 * يعمل تلقائيًا بعد التحديث الذاتي من GitHub بحيث تتحدث قاعدة البيانات مع الكود.
 * كل ملف ترحيل يعيد دالة، ويجب أن يكون آمن التكرار (يفحص وجود العمود/الجدول قبل الإضافة).
 */
final class Migrator
{
    /** @return string[] أسماء الترحيلات التي طُبقت في هذا الاستدعاء */
    public static function migrate(): array
    {
        self::ensureTable();

        $dir = CORE_PATH . '/Database/migrations';
        if (!is_dir($dir)) {
            return [];
        }

        $files = glob($dir . '/*.php') ?: [];
        sort($files);

        $done = array_column(
            Database::fetchAll('SELECT name FROM ' . Database::table('schema_migrations')),
            'name'
        );

        $applied = [];
        foreach ($files as $file) {
            $name = basename($file, '.php');
            if (in_array($name, $done, true)) {
                continue;
            }

            $migration = require $file;
            if (is_callable($migration)) {
                $migration();
            }

            Database::insert('schema_migrations', [
                'name' => $name,
                'applied_at' => date('Y-m-d H:i:s'),
            ]);
            $applied[] = $name;
        }

        return $applied;
    }

    /** عدد الترحيلات المتوفرة في الملفات ولم تُطبق بعد */
    public static function pendingCount(): int
    {
        self::ensureTable();

        $dir = CORE_PATH . '/Database/migrations';
        $files = is_dir($dir) ? (glob($dir . '/*.php') ?: []) : [];
        $done = array_column(
            Database::fetchAll('SELECT name FROM ' . Database::table('schema_migrations')),
            'name'
        );

        $pending = 0;
        foreach ($files as $file) {
            if (!in_array(basename($file, '.php'), $done, true)) {
                $pending++;
            }
        }
        return $pending;
    }

    private static function ensureTable(): void
    {
        $table = Database::table('schema_migrations');
        Database::pdo()->exec(
            "CREATE TABLE IF NOT EXISTS `{$table}` (
                `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
                `name` VARCHAR(191) NOT NULL,
                `applied_at` DATETIME NOT NULL,
                PRIMARY KEY (`id`),
                UNIQUE KEY `schema_migrations_name_unique` (`name`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
        );
    }
}
