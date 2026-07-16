<?php

namespace Core;

use PDO;
use PDOException;
use PDOStatement;

/**
 * طبقة اتصال خفيفة بقاعدة بيانات MySQL عبر PDO.
 * تدعم بادئة الجداول وتمنع حقن SQL عبر الاستعلامات المجهزة دائمًا.
 */
final class Database
{
    private static ?PDO $pdo = null;
    private static string $prefix = '';

    public static function connect(string $host, string $port, string $name, string $user, string $pass, string $charset = 'utf8mb4', string $prefix = ''): PDO
    {
        $dsn = sprintf('mysql:host=%s;port=%s;dbname=%s;charset=%s', $host, $port ?: '3306', $name, $charset);

        self::$pdo = new PDO($dsn, $user, $pass, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
            PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES {$charset} COLLATE {$charset}_unicode_ci",
        ]);

        self::$prefix = $prefix;

        return self::$pdo;
    }

    /** اتصال اختباري لا يُبقي أثرًا في الحالة العامة، يُستخدم في معالج التثبيت */
    public static function testConnection(string $host, string $port, string $name, string $user, string $pass, string $charset = 'utf8mb4'): array
    {
        try {
            $dsn = sprintf('mysql:host=%s;port=%s;dbname=%s;charset=%s', $host, $port ?: '3306', $name, $charset);
            new PDO($dsn, $user, $pass, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
            return ['ok' => true, 'message' => 'تم الاتصال بقاعدة البيانات بنجاح.'];
        } catch (PDOException $e) {
            return ['ok' => false, 'message' => self::friendlyError($e)];
        }
    }

    private static function friendlyError(PDOException $e): string
    {
        $msg = $e->getMessage();
        if (str_contains($msg, 'Unknown database')) {
            return 'تعذر العثور على قاعدة البيانات المحددة. تأكد من إنشائها مسبقًا من لوحة الاستضافة.';
        }
        if (str_contains($msg, 'Access denied')) {
            return 'اسم المستخدم أو كلمة المرور غير صحيحة، أو لا تملك صلاحية الوصول لهذه القاعدة.';
        }
        if (str_contains($msg, "getaddrinfo") || str_contains($msg, 'Connection refused') || str_contains($msg, 'timed out')) {
            return 'تعذر الوصول لخادم قاعدة البيانات. تأكد من اسم السيرفر والمنفذ.';
        }
        return 'تعذر الاتصال بقاعدة البيانات. الرجاء التحقق من البيانات المدخلة.';
    }

    public static function pdo(): PDO
    {
        if (self::$pdo === null) {
            throw new \RuntimeException('لم يتم تهيئة الاتصال بقاعدة البيانات.');
        }
        return self::$pdo;
    }

    public static function setInstance(PDO $pdo, string $prefix): void
    {
        self::$pdo = $pdo;
        self::$prefix = $prefix;
    }

    public static function prefix(): string
    {
        return self::$prefix;
    }

    /** يحول اسم جدول منطقي (news) إلى الاسم الفعلي مع البادئة (fam_news) */
    public static function table(string $name): string
    {
        return self::$prefix . $name;
    }

    public static function query(string $sql, array $params = []): PDOStatement
    {
        $stmt = self::pdo()->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    }

    public static function fetchAll(string $sql, array $params = []): array
    {
        return self::query($sql, $params)->fetchAll();
    }

    public static function fetchOne(string $sql, array $params = []): ?array
    {
        $row = self::query($sql, $params)->fetch();
        return $row === false ? null : $row;
    }

    public static function fetchValue(string $sql, array $params = [])
    {
        $row = self::query($sql, $params)->fetch(PDO::FETCH_NUM);
        return $row === false ? null : $row[0];
    }

    public static function insert(string $table, array $data): string
    {
        $columns = array_keys($data);
        $placeholders = array_map(fn($c) => ':' . $c, $columns);

        $sql = sprintf(
            'INSERT INTO %s (%s) VALUES (%s)',
            self::table($table),
            implode(', ', array_map(fn($c) => "`$c`", $columns)),
            implode(', ', $placeholders)
        );

        $stmt = self::pdo()->prepare($sql);
        foreach ($data as $col => $val) {
            $stmt->bindValue(':' . $col, $val);
        }
        $stmt->execute();

        return self::pdo()->lastInsertId();
    }

    public static function update(string $table, array $data, array $where): int
    {
        $set = implode(', ', array_map(fn($c) => "`$c` = :set_$c", array_keys($data)));
        $whereSql = implode(' AND ', array_map(fn($c) => "`$c` = :where_$c", array_keys($where)));

        $sql = sprintf('UPDATE %s SET %s WHERE %s', self::table($table), $set, $whereSql);
        $stmt = self::pdo()->prepare($sql);

        foreach ($data as $col => $val) {
            $stmt->bindValue(':set_' . $col, $val);
        }
        foreach ($where as $col => $val) {
            $stmt->bindValue(':where_' . $col, $val);
        }
        $stmt->execute();

        return $stmt->rowCount();
    }

    public static function delete(string $table, array $where): int
    {
        $whereSql = implode(' AND ', array_map(fn($c) => "`$c` = :where_$c", array_keys($where)));
        $sql = sprintf('DELETE FROM %s WHERE %s', self::table($table), $whereSql);
        $stmt = self::pdo()->prepare($sql);
        foreach ($where as $col => $val) {
            $stmt->bindValue(':where_' . $col, $val);
        }
        $stmt->execute();

        return $stmt->rowCount();
    }

    public static function lastInsertId(): string
    {
        return self::pdo()->lastInsertId();
    }

    public static function beginTransaction(): bool
    {
        return self::pdo()->beginTransaction();
    }

    public static function commit(): bool
    {
        return self::pdo()->commit();
    }

    public static function rollBack(): bool
    {
        return self::pdo()->rollBack();
    }

    public static function tableExists(string $table): bool
    {
        try {
            self::pdo()->query('SELECT 1 FROM ' . self::table($table) . ' LIMIT 1');
            return true;
        } catch (PDOException $e) {
            return false;
        }
    }
}
