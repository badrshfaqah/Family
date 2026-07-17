<?php

namespace Core\Admin\Controllers;

use Core\ActivityLog;
use Core\Auth;
use Core\Database;
use Core\Media;
use Core\Support\Csrf;
use Core\Support\Request;
use Core\Support\Response;
use Core\Support\Security;
use Core\Support\Session;
use Core\Support\Url;
use Core\View;

final class MediaController
{
    public function index(array $params): void
    {
        Auth::requirePermission('content.media');

        $page = max(1, Request::int('page', 1));
        $perPage = 24;
        $offset = ($page - 1) * $perPage;

        $total = (int) Database::fetchValue('SELECT COUNT(*) FROM ' . Database::table('media'));
        $items = Database::fetchAll('SELECT * FROM ' . Database::table('media') . " ORDER BY id DESC LIMIT {$perPage} OFFSET {$offset}");

        echo View::render(CORE_PATH . '/Admin/Views/layouts/admin.php', [
            'pageTitle' => 'الوسائط',
            'contentView' => CORE_PATH . '/Admin/Views/media/index.php',
            'items' => $items,
            'total' => $total,
            'page' => $page,
            'perPage' => $perPage,
        ]);
    }

    public function upload(array $params): void
    {
        Auth::requirePermission('content.media');
        Csrf::verifyRequestOrFail();

        $files = $_FILES['files'] ?? null;
        $uploadedCount = 0;

        if ($files && is_array($files['name'])) {
            foreach ($files['name'] as $i => $name) {
                if (($files['error'][$i] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
                    continue;
                }
                $single = [
                    'name' => $files['name'][$i],
                    'type' => $files['type'][$i],
                    'tmp_name' => $files['tmp_name'][$i],
                    'error' => $files['error'][$i],
                    'size' => $files['size'][$i],
                ];
                try {
                    $data = Media::handleUpload($single, Auth::user()['id'] ?? null);
                    Database::insert('media', array_merge($data, ['created_at' => date('Y-m-d H:i:s')]));
                    $uploadedCount++;
                } catch (\Throwable $e) {
                    Session::flash('error', $e->getMessage());
                }
            }
        }

        if ($uploadedCount > 0) {
            ActivityLog::record('media_upload', "رفع {$uploadedCount} ملف/ملفات");
            Session::flash('success', "تم رفع {$uploadedCount} ملف بنجاح.");
        }

        Response::redirect(Url::admin('media'));
    }

    public function update(array $params): void
    {
        Auth::requirePermission('content.media');
        Csrf::verifyRequestOrFail();

        $id = (int) $params['id'];
        Database::update('media', [
            'title' => Security::cleanText(Request::trimmed('title')),
            'alt_text' => Security::cleanText(Request::trimmed('alt_text')),
            'caption' => Security::cleanText(Request::trimmed('caption')),
        ], ['id' => $id]);

        Session::flash('success', 'تم تحديث بيانات الملف.');
        Response::redirect(Url::admin('media'));
    }

    public function delete(array $params): void
    {
        Auth::requirePermission('content.media');
        Csrf::verifyRequestOrFail();

        $id = (int) $params['id'];
        $item = Database::fetchOne('SELECT * FROM ' . Database::table('media') . ' WHERE id = ?', [$id]);
        if ($item) {
            Media::deleteFiles($item['stored_path'], $item['thumb_path']);
            Database::delete('media', ['id' => $id]);
            ActivityLog::record('media_delete', 'حذف ملف وسائط: ' . $item['file_name']);
        }

        Session::flash('success', 'تم حذف الملف.');
        Response::redirect(Url::admin('media'));
    }
}
