<?php

namespace Core\Admin\Controllers;

use Core\ActivityLog;
use Core\Auth;
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
}
