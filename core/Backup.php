<?php

namespace Core;

/**
 * نسخ احتياطي بديل خالص بلغة PHP (بدون الاعتماد على أوامر mysqldump/zip الخارجية)
 * ليعمل على أي استضافة مشتركة عادية.
 */
final class Backup
{
    public static function backupsDir(): string
    {
        return STORAGE_PATH . '/backups';
    }

    public static function dumpDatabase(): string
    {
        $pdo = Database::pdo();
        $prefix = Database::prefix();

        $tables = $pdo->query("SHOW TABLES")->fetchAll(\PDO::FETCH_COLUMN);
        $tables = array_filter($tables, fn($t) => $prefix === '' || str_starts_with($t, $prefix));

        $fileName = 'db-backup-' . date('Ymd-His') . '.sql';
        $filePath = self::backupsDir() . '/' . $fileName;

        $handle = fopen($filePath, 'w');
        fwrite($handle, "-- نسخة احتياطية لقاعدة البيانات\n-- تاريخ الإنشاء: " . date('Y-m-d H:i:s') . "\n\n");
        fwrite($handle, "SET FOREIGN_KEY_CHECKS=0;\n\n");

        foreach ($tables as $table) {
            $createRow = $pdo->query("SHOW CREATE TABLE `{$table}`")->fetch(\PDO::FETCH_ASSOC);
            $createSql = $createRow['Create Table'] ?? '';
            fwrite($handle, "DROP TABLE IF EXISTS `{$table}`;\n{$createSql};\n\n");

            $count = (int) $pdo->query("SELECT COUNT(*) FROM `{$table}`")->fetchColumn();
            $chunk = 500;
            for ($offset = 0; $offset < $count; $offset += $chunk) {
                $rows = $pdo->query("SELECT * FROM `{$table}` LIMIT {$chunk} OFFSET {$offset}")->fetchAll(\PDO::FETCH_ASSOC);
                if (!$rows) {
                    continue;
                }
                $columns = array_keys($rows[0]);
                $columnsSql = implode(', ', array_map(fn($c) => "`$c`", $columns));
                $valuesSql = [];
                foreach ($rows as $row) {
                    $vals = array_map(function ($v) use ($pdo) {
                        return $v === null ? 'NULL' : $pdo->quote((string) $v);
                    }, $row);
                    $valuesSql[] = '(' . implode(', ', $vals) . ')';
                }
                fwrite($handle, "INSERT INTO `{$table}` ({$columnsSql}) VALUES\n" . implode(",\n", $valuesSql) . ";\n");
            }
            fwrite($handle, "\n");
        }

        fwrite($handle, "SET FOREIGN_KEY_CHECKS=1;\n");
        fclose($handle);

        Database::insert('backups_log', [
            'type' => 'database',
            'file_path' => $fileName,
            'size_bytes' => filesize($filePath),
            'created_by' => Auth::user()['id'] ?? null,
            'created_at' => date('Y-m-d H:i:s'),
        ]);

        return $fileName;
    }

    public static function backupFiles(): string
    {
        if (!class_exists('ZipArchive')) {
            throw new \RuntimeException('مكتبة ZipArchive غير متوفرة على هذا السيرفر، لا يمكن إنشاء نسخة احتياطية للملفات.');
        }

        $fileName = 'files-backup-' . date('Ymd-His') . '.zip';
        $filePath = self::backupsDir() . '/' . $fileName;

        $zip = new \ZipArchive();
        $zip->open($filePath, \ZipArchive::CREATE);

        $sourceDir = STORAGE_PATH . '/uploads';
        if (is_dir($sourceDir)) {
            $items = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($sourceDir, \FilesystemIterator::SKIP_DOTS));
            foreach ($items as $item) {
                $relative = 'uploads/' . substr($item->getPathname(), strlen($sourceDir) + 1);
                $zip->addFile($item->getPathname(), $relative);
            }
        }

        if (is_file(ROOT_PATH . '/config.php')) {
            $zip->addFile(ROOT_PATH . '/config.php', 'config.php');
        }

        $zip->close();

        Database::insert('backups_log', [
            'type' => 'files',
            'file_path' => $fileName,
            'size_bytes' => filesize($filePath),
            'created_by' => Auth::user()['id'] ?? null,
            'created_at' => date('Y-m-d H:i:s'),
        ]);

        return $fileName;
    }

    public static function list(): array
    {
        if (!Database::tableExists('backups_log')) {
            return [];
        }
        return Database::fetchAll('SELECT * FROM ' . Database::table('backups_log') . ' ORDER BY id DESC');
    }

    public static function delete(int $id): void
    {
        $row = Database::fetchOne('SELECT * FROM ' . Database::table('backups_log') . ' WHERE id = ?', [$id]);
        if ($row) {
            $path = self::backupsDir() . '/' . $row['file_path'];
            if (is_file($path)) {
                @unlink($path);
            }
            Database::delete('backups_log', ['id' => $id]);
        }
    }

    public static function enforceRetention(int $keep): void
    {
        $rows = self::list();
        if (count($rows) <= $keep) {
            return;
        }
        $toDelete = array_slice($rows, $keep);
        foreach ($toDelete as $row) {
            self::delete((int) $row['id']);
        }
    }

    /** استعادة نسخة قاعدة بيانات من ملف SQL بصورة آمنة (تنفيذ ضمن معاملة واحدة قدر الإمكان) */
    public static function restoreDatabase(string $filePath): void
    {
        $sql = file_get_contents($filePath);
        if ($sql === false) {
            throw new \RuntimeException('تعذر قراءة ملف النسخة الاحتياطية.');
        }

        $pdo = Database::pdo();
        $statements = self::splitSqlStatements($sql);

        foreach ($statements as $statement) {
            $statement = trim($statement);
            if ($statement === '' || str_starts_with($statement, '--')) {
                continue;
            }
            $pdo->exec($statement);
        }
    }

    private static function splitSqlStatements(string $sql): array
    {
        return array_filter(array_map('trim', explode(";\n", $sql)));
    }
}
