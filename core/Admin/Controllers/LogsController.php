<?php

namespace Core\Admin\Controllers;

use Core\ActivityLog;
use Core\Auth;
use Core\Database;
use Core\Support\Request;
use Core\View;

final class LogsController
{
    public function index(array $params): void
    {
        Auth::requirePermission('system.logs');

        $page = max(1, Request::int('page', 1));
        $result = ActivityLog::paginate($page, 30);

        echo View::render(CORE_PATH . '/Admin/Views/layouts/admin.php', [
            'pageTitle' => 'سجل العمليات',
            'contentView' => CORE_PATH . '/Admin/Views/logs/index.php',
            'result' => $result,
        ]);
    }

    /** نشاط مستخدم محدد: شاشة خاصة بمدير النظام حصريًا (وليس بصلاحية system.logs) */
    public function userActivity(array $params): void
    {
        Auth::requireLogin();
        if ((Auth::user()['role_slug'] ?? '') !== 'system_admin') {
            http_response_code(403);
            echo 'هذه الشاشة متاحة لمدير النظام فقط.';
            exit;
        }

        $users = Database::fetchAll(
            'SELECT u.id, u.name, u.username, u.status, r.name AS role_name
             FROM ' . Database::table('admin_users') . ' u
             LEFT JOIN ' . Database::table('roles') . ' r ON r.id = u.role_id
             ORDER BY u.name ASC'
        );

        $userId = Request::int('user_id', 0);
        $page = max(1, Request::int('page', 1));

        $result = null;
        $stats = null;
        if ($userId > 0) {
            $result = ActivityLog::paginate($page, 30, ['user_id' => $userId]);
            $stats = [
                'total' => (int) Database::fetchValue(
                    'SELECT COUNT(*) FROM ' . Database::table('activity_log') . ' WHERE user_id = ?',
                    [$userId]
                ),
                'last_at' => Database::fetchValue(
                    'SELECT MAX(created_at) FROM ' . Database::table('activity_log') . ' WHERE user_id = ?',
                    [$userId]
                ),
                'last_login' => Database::fetchValue(
                    'SELECT MAX(created_at) FROM ' . Database::table('activity_log') . " WHERE user_id = ? AND action = 'login'",
                    [$userId]
                ),
            ];
        }

        echo View::render(CORE_PATH . '/Admin/Views/layouts/admin.php', [
            'pageTitle' => 'نشاط المستخدمين',
            'contentView' => CORE_PATH . '/Admin/Views/logs/user-activity.php',
            'users' => $users,
            'selectedUserId' => $userId,
            'result' => $result,
            'stats' => $stats,
        ]);
    }
}
