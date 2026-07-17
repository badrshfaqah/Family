<?php

namespace Core;

/**
 * إعادة توجيه تلقائية عند تغيير الرابط المختصر (Slug) لأي محتوى،
 * لتجنّب روابط معطوبة (404) بعد التعديل.
 */
final class Redirects
{
    public static function recordSlugChange(string $routePrefix, string $oldSlug, string $newSlug): void
    {
        if ($oldSlug === $newSlug || $oldSlug === '' || $newSlug === '') {
            return;
        }
        if (!Database::tableExists('redirects')) {
            return;
        }

        $prefix = trim($routePrefix, '/');
        $from = $prefix !== '' ? '/' . $prefix . '/' . $oldSlug : '/' . $oldSlug;
        $to = $prefix !== '' ? '/' . $prefix . '/' . $newSlug : '/' . $newSlug;

        if ($from === $to) {
            return;
        }

        Database::query(
            'INSERT INTO ' . Database::table('redirects') . ' (from_path, to_path, created_at) VALUES (?, ?, ?)
             ON DUPLICATE KEY UPDATE to_path = VALUES(to_path), created_at = VALUES(created_at)',
            [$from, $to, date('Y-m-d H:i:s')]
        );

        // تحديث أي تحويلات سابقة كانت تشير للرابط القديم حتى لا تنكسر السلسلة
        Database::query(
            'UPDATE ' . Database::table('redirects') . ' SET to_path = ? WHERE to_path = ? AND from_path != ?',
            [$to, $from, $from]
        );
    }

    public static function resolve(string $path): ?string
    {
        if (!Database::tableExists('redirects')) {
            return null;
        }
        $row = Database::fetchOne(
            'SELECT to_path FROM ' . Database::table('redirects') . ' WHERE from_path = ?',
            [$path]
        );
        return $row['to_path'] ?? null;
    }
}
