<?php

namespace Core\Admin\Controllers;

use Core\Auth;
use Core\Database;
use Core\ModuleManager;
use Core\View;

final class DashboardController
{
    public function index(array $params): void
    {
        Auth::requireLogin();

        $stats = [
            'news' => $this->count('news'),
            'events' => $this->count('events'),
            'gatherings_active' => $this->count('gatherings', "status = 'active'"),
            'directory' => $this->count('directory_contacts'),
            'newsletter' => $this->count('newsletter_subscribers'),
        ];

        $recentActivity = Database::tableExists('activity_log')
            ? Database::fetchAll('SELECT * FROM ' . Database::table('activity_log') . ' ORDER BY id DESC LIMIT 8')
            : [];

        $storageUsed = $this->folderSize(STORAGE_PATH . '/uploads');

        echo View::render(CORE_PATH . '/Admin/Views/layouts/admin.php', [
            'pageTitle' => 'لوحة التحكم',
            'contentView' => CORE_PATH . '/Admin/Views/dashboard/index.php',
            'stats' => $stats,
            'recentActivity' => $recentActivity,
            'storageUsed' => $storageUsed,
            'modulesCount' => count(ModuleManager::installedMap()),
        ]);
    }

    private function count(string $table, string $where = '1=1'): int
    {
        if (!Database::tableExists($table)) {
            return 0;
        }
        return (int) Database::fetchValue('SELECT COUNT(*) FROM ' . Database::table($table) . ' WHERE ' . $where);
    }

    private function folderSize(string $dir): int
    {
        if (!is_dir($dir)) {
            return 0;
        }
        $size = 0;
        $items = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($dir, \FilesystemIterator::SKIP_DOTS));
        foreach ($items as $item) {
            $size += $item->getSize();
        }
        return $size;
    }
}
