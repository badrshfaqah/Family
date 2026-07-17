<?php

namespace Core\Admin\Controllers;

use Core\ActivityLog;
use Core\Auth;
use Core\Backup;
use Core\Settings;
use Core\Support\Csrf;
use Core\Support\Request;
use Core\Support\Response;
use Core\Support\Session;
use Core\Support\Str;
use Core\Support\Url;
use Core\View;

final class BackupController
{
    public function index(array $params): void
    {
        Auth::requirePermission('system.backup');

        echo View::render(CORE_PATH . '/Admin/Views/layouts/admin.php', [
            'pageTitle' => 'النسخ الاحتياطي',
            'contentView' => CORE_PATH . '/Admin/Views/backup/index.php',
            'backups' => Backup::list(),
            'zipSupported' => class_exists('ZipArchive'),
        ]);
    }

    public function createDatabase(array $params): void
    {
        Auth::requirePermission('system.backup');
        Csrf::verifyRequestOrFail();

        try {
            Backup::dumpDatabase();
            Backup::enforceRetention((int) Settings::get('backup_retention', 10));
            ActivityLog::record('backup_create', 'إنشاء نسخة احتياطية لقاعدة البيانات');
            Session::flash('success', 'تم إنشاء نسخة احتياطية لقاعدة البيانات بنجاح.');
        } catch (\Throwable $e) {
            Session::flash('error', 'تعذر إنشاء النسخة الاحتياطية: ' . $e->getMessage());
        }

        Response::redirect(Url::admin('backup'));
    }

    public function createFiles(array $params): void
    {
        Auth::requirePermission('system.backup');
        Csrf::verifyRequestOrFail();

        try {
            Backup::backupFiles();
            Backup::enforceRetention((int) Settings::get('backup_retention', 10));
            ActivityLog::record('backup_create', 'إنشاء نسخة احتياطية للملفات');
            Session::flash('success', 'تم إنشاء نسخة احتياطية للملفات بنجاح.');
        } catch (\Throwable $e) {
            Session::flash('error', 'تعذر إنشاء النسخة الاحتياطية: ' . $e->getMessage());
        }

        Response::redirect(Url::admin('backup'));
    }

    public function download(array $params): void
    {
        Auth::requirePermission('system.backup');

        $file = basename((string) $params['file']);
        $path = Backup::backupsDir() . '/' . $file;

        if (!is_file($path)) {
            http_response_code(404);
            echo 'الملف غير موجود.';
            return;
        }

        ActivityLog::record('backup_download', 'تحميل نسخة احتياطية: ' . $file);

        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="' . $file . '"');
        header('Content-Length: ' . filesize($path));
        readfile($path);
        exit;
    }

    public function delete(array $params): void
    {
        Auth::requirePermission('system.backup');
        Csrf::verifyRequestOrFail();

        Backup::delete((int) $params['id']);
        ActivityLog::record('backup_delete', 'حذف نسخة احتياطية رقم: ' . $params['id']);
        Session::flash('success', 'تم حذف النسخة الاحتياطية.');
        Response::redirect(Url::admin('backup'));
    }

    public function restore(array $params): void
    {
        Auth::requirePermission('system.backup');
        Csrf::verifyRequestOrFail();

        $file = Request::file('backup_file');
        if (!$file) {
            Session::flash('error', 'الرجاء اختيار ملف نسخة احتياطية بصيغة SQL.');
            Response::redirect(Url::admin('backup'));
        }

        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if ($ext !== 'sql') {
            Session::flash('error', 'يجب أن يكون الملف بصيغة SQL.');
            Response::redirect(Url::admin('backup'));
        }

        $tmpPath = STORAGE_PATH . '/temp/restore-' . Str::random(8) . '.sql';
        move_uploaded_file($file['tmp_name'], $tmpPath);

        try {
            // نسخة أمان تلقائية قبل الاستعادة تحسبًا لأي خطأ
            Backup::dumpDatabase();
            Backup::restoreDatabase($tmpPath);
            ActivityLog::record('backup_restore', 'استعادة نسخة احتياطية لقاعدة البيانات');
            Session::flash('success', 'تمت الاستعادة بنجاح. تم أخذ نسخة احتياطية تلقائية قبل الاستعادة تحسبًا لأي خطأ.');
        } catch (\Throwable $e) {
            Session::flash('error', 'فشلت عملية الاستعادة: ' . $e->getMessage());
        } finally {
            @unlink($tmpPath);
        }

        Response::redirect(Url::admin('backup'));
    }
}
