<?php

namespace Core;

/**
 * نسخ احتياطي بديل خالص بلغة PHP (بدون الاعتماد على أوامر mysqldump/zip الخارجية)
 * ليعمل على أي استضافة مشتركة عادية.
 */
final class Backup
{
    private const ENCRYPTED_MAGIC = "HNTB1";
    private const ENCRYPTION_CHUNK = 1048576;

    public static function backupsDir(): string
    {
        $configured = defined('BACKUP_PATH') ? rtrim((string) BACKUP_PATH, '/\\') : '';
        $dir = $configured !== '' ? $configured : STORAGE_PATH . '/backups';
        if (!is_dir($dir) && !mkdir($dir, 0700, true) && !is_dir($dir)) {
            throw new \RuntimeException('تعذر إنشاء مجلد النسخ الاحتياطية.');
        }
        @chmod($dir, 0700);
        return $dir;
    }

    public static function resolveBackupPath(string $fileName): string
    {
        $fileName = basename($fileName);
        $primary = self::backupsDir() . '/' . $fileName;
        if (is_file($primary)) {
            return $primary;
        }
        $legacy = STORAGE_PATH . '/backups/' . $fileName;
        return is_file($legacy) ? $legacy : $primary;
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

        $fileName = self::encryptBackup($filePath, 'D');
        $filePath = self::backupsDir() . '/' . $fileName;

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
        $fileName = self::encryptBackup($filePath, 'F');
        $filePath = self::backupsDir() . '/' . $fileName;

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
            $path = self::resolveBackupPath($row['file_path']);
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
            if (!self::isAllowedRestoreStatement($statement)) {
                throw new \RuntimeException('يحتوي ملف النسخة الاحتياطية على أمر SQL غير مسموح به.');
            }
            $pdo->exec($statement);
        }
    }

    /** يفك نسخة قاعدة بيانات مشفرة إلى ملف مؤقت ويعيد مساره. */
    public static function decryptDatabaseBackup(string $encryptedPath): string
    {
        $output = STORAGE_PATH . '/temp/restore-dec-' . bin2hex(random_bytes(8)) . '.sql';
        self::decryptBackup($encryptedPath, $output, 'D');
        return $output;
    }

    private static function encryptBackup(string $plainPath, string $type): string
    {
        $secret = defined('BACKUP_ENCRYPTION_KEY') ? trim((string) BACKUP_ENCRYPTION_KEY) : '';
        if ($secret === '') {
            return basename($plainPath);
        }
        if (!function_exists('sodium_crypto_secretstream_xchacha20poly1305_init_push')) {
            throw new \RuntimeException('مكتبة Sodium مطلوبة لتشفير النسخ الاحتياطية.');
        }

        $key = sodium_crypto_generichash($secret, '', SODIUM_CRYPTO_SECRETSTREAM_XCHACHA20POLY1305_KEYBYTES);
        [$state, $header] = sodium_crypto_secretstream_xchacha20poly1305_init_push($key);
        $encryptedPath = $plainPath . '.enc';
        $input = fopen($plainPath, 'rb');
        $output = fopen($encryptedPath, 'wb');
        if (!$input || !$output) {
            throw new \RuntimeException('تعذر فتح ملف النسخة الاحتياطية للتشفير.');
        }
        fwrite($output, self::ENCRYPTED_MAGIC . $type . $header);

        $current = fread($input, self::ENCRYPTION_CHUNK);
        if ($current === false || $current === '') {
            fwrite($output, sodium_crypto_secretstream_xchacha20poly1305_push(
                $state,
                '',
                '',
                SODIUM_CRYPTO_SECRETSTREAM_XCHACHA20POLY1305_TAG_FINAL
            ));
        }
        while ($current !== false && $current !== '') {
            $next = fread($input, self::ENCRYPTION_CHUNK);
            $tag = ($next === false || $next === '')
                ? SODIUM_CRYPTO_SECRETSTREAM_XCHACHA20POLY1305_TAG_FINAL
                : SODIUM_CRYPTO_SECRETSTREAM_XCHACHA20POLY1305_TAG_MESSAGE;
            fwrite($output, sodium_crypto_secretstream_xchacha20poly1305_push($state, $current, '', $tag));
            $current = $next;
        }
        fclose($input);
        fclose($output);
        @chmod($encryptedPath, 0600);
        @unlink($plainPath);
        sodium_memzero($key);
        return basename($encryptedPath);
    }

    private static function decryptBackup(string $encryptedPath, string $outputPath, string $expectedType): void
    {
        $secret = defined('BACKUP_ENCRYPTION_KEY') ? trim((string) BACKUP_ENCRYPTION_KEY) : '';
        if ($secret === '' || !function_exists('sodium_crypto_secretstream_xchacha20poly1305_init_pull')) {
            throw new \RuntimeException('مفتاح أو مكتبة فك تشفير النسخة الاحتياطية غير متوفر.');
        }
        $input = fopen($encryptedPath, 'rb');
        $output = fopen($outputPath, 'wb');
        if (!$input || !$output) {
            throw new \RuntimeException('تعذر فتح ملف النسخة الاحتياطية المشفرة.');
        }

        $prefix = fread($input, strlen(self::ENCRYPTED_MAGIC) + 1);
        $header = fread($input, SODIUM_CRYPTO_SECRETSTREAM_XCHACHA20POLY1305_HEADERBYTES);
        if ($prefix !== self::ENCRYPTED_MAGIC . $expectedType || strlen($header) !== SODIUM_CRYPTO_SECRETSTREAM_XCHACHA20POLY1305_HEADERBYTES) {
            fclose($input); fclose($output); @unlink($outputPath);
            throw new \RuntimeException('نوع ملف النسخة الاحتياطية المشفرة غير صالح.');
        }

        $key = sodium_crypto_generichash($secret, '', SODIUM_CRYPTO_SECRETSTREAM_XCHACHA20POLY1305_KEYBYTES);
        $state = sodium_crypto_secretstream_xchacha20poly1305_init_pull($header, $key);
        $sawFinal = false;
        while (!feof($input)) {
            $cipher = fread($input, self::ENCRYPTION_CHUNK + SODIUM_CRYPTO_SECRETSTREAM_XCHACHA20POLY1305_ABYTES);
            if ($cipher === false || $cipher === '') {
                break;
            }
            $result = sodium_crypto_secretstream_xchacha20poly1305_pull($state, $cipher);
            if ($result === false) {
                fclose($input); fclose($output); @unlink($outputPath);
                throw new \RuntimeException('فشل التحقق من سلامة النسخة الاحتياطية المشفرة.');
            }
            [$plain, $tag] = $result;
            fwrite($output, $plain);
            if ($tag === SODIUM_CRYPTO_SECRETSTREAM_XCHACHA20POLY1305_TAG_FINAL) {
                $sawFinal = true;
                break;
            }
        }
        fclose($input);
        fclose($output);
        sodium_memzero($key);
        if (!$sawFinal) {
            @unlink($outputPath);
            throw new \RuntimeException('ملف النسخة الاحتياطية المشفرة مبتور أو غير مكتمل.');
        }
        @chmod($outputPath, 0600);
    }

    private static function isAllowedRestoreStatement(string $statement): bool
    {
        $normalized = ltrim($statement);
        if (preg_match('/^SET\s+FOREIGN_KEY_CHECKS\s*=\s*[01]$/i', $normalized)) {
            return true;
        }

        if (!preg_match('/^(DROP\s+TABLE\s+IF\s+EXISTS|CREATE\s+TABLE|INSERT\s+INTO)\s+`([^`]+)`/i', $normalized, $match)) {
            return false;
        }

        $prefix = Database::prefix();
        return $prefix === '' || str_starts_with($match[2], $prefix);
    }

    private static function splitSqlStatements(string $sql): array
    {
        return array_filter(array_map('trim', explode(";\n", $sql)));
    }
}
