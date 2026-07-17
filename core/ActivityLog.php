<?php

namespace Core;

use Core\Support\Request;

/**
 * سجل العمليات الإدارية المهمة. لا يتم تسجيل أي كلمات مرور أو بيانات سرية هنا.
 */
final class ActivityLog
{
    public static function record(string $action, string $description = '', ?string $subjectType = null, $subjectId = null): void
    {
        if (!Database::tableExists('activity_log')) {
            return;
        }

        $user = Auth::user();

        Database::insert('activity_log', [
            'user_id' => $user['id'] ?? null,
            'user_name' => $user['name'] ?? 'النظام',
            'action' => $action,
            'description' => $description,
            'subject_type' => $subjectType,
            'subject_id' => $subjectId !== null ? (string) $subjectId : null,
            'ip_address' => Request::ip(),
            'created_at' => date('Y-m-d H:i:s'),
        ]);
    }

    public static function paginate(int $page, int $perPage = 30, array $filters = []): array
    {
        $where = [];
        $params = [];

        if (!empty($filters['action'])) {
            $where[] = 'action = ?';
            $params[] = $filters['action'];
        }
        if (!empty($filters['user_id'])) {
            $where[] = 'user_id = ?';
            $params[] = $filters['user_id'];
        }

        $whereSql = $where ? ('WHERE ' . implode(' AND ', $where)) : '';

        $total = (int) Database::fetchValue(
            'SELECT COUNT(*) FROM ' . Database::table('activity_log') . " {$whereSql}",
            $params
        );

        $offset = max(0, ($page - 1) * $perPage);
        $rows = Database::fetchAll(
            'SELECT * FROM ' . Database::table('activity_log') . " {$whereSql} ORDER BY id DESC LIMIT {$perPage} OFFSET {$offset}",
            $params
        );

        return ['rows' => $rows, 'total' => $total, 'page' => $page, 'perPage' => $perPage];
    }
}
