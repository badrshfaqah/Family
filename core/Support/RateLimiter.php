<?php

namespace Core\Support;

use Core\Database;

/**
 * حماية بسيطة من محاولات الدخول المتكررة وتعبئة النماذج العامة بشكل آلي مكثف،
 * تعتمد على جدول قاعدة بيانات ولا تحتاج أي خدمة خارجية.
 */
final class RateLimiter
{
    public static function tooManyAttempts(string $key, int $maxAttempts, int $decayMinutes): bool
    {
        $row = Database::fetchOne(
            'SELECT attempts, first_attempt_at FROM ' . Database::table('rate_limits') . ' WHERE limit_key = ?',
            [$key]
        );

        if (!$row) {
            return false;
        }

        $windowExpired = strtotime($row['first_attempt_at']) < (time() - $decayMinutes * 60);
        if ($windowExpired) {
            return false;
        }

        return (int) $row['attempts'] >= $maxAttempts;
    }

    public static function hit(string $key): void
    {
        $row = Database::fetchOne(
            'SELECT id, first_attempt_at FROM ' . Database::table('rate_limits') . ' WHERE limit_key = ?',
            [$key]
        );

        if (!$row) {
            Database::insert('rate_limits', [
                'limit_key' => $key,
                'attempts' => 1,
                'first_attempt_at' => date('Y-m-d H:i:s'),
            ]);
            return;
        }

        Database::query(
            'UPDATE ' . Database::table('rate_limits') . ' SET attempts = attempts + 1 WHERE id = ?',
            [$row['id']]
        );
    }

    public static function clear(string $key): void
    {
        Database::delete('rate_limits', ['limit_key' => $key]);
    }
}
