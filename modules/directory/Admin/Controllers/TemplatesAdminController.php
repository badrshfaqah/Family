<?php

namespace Modules\Directory\Admin\Controllers;

use Core\ActivityLog;
use Core\Auth;
use Core\Database;
use Core\Support\Csrf;
use Core\Support\Request;
use Core\Support\Response;
use Core\Support\Security;
use Core\Support\Session;
use Core\Support\Url;
use Core\View;

/**
 * قوالب الرسائل (دعوة زواج، إعلان عزاء...) — نطاق النسخة الأولى: تجهيز نص فقط،
 * بدون إرسال فعلي عبر واتساب/رسائل نصية (يُترك لإضافات تكامل مستقبلية).
 */
final class TemplatesAdminController
{
    public function index(array $params): void
    {
        Auth::requirePermission('templates.manage');

        $rows = Database::fetchAll('SELECT * FROM ' . Database::table('directory_message_templates') . ' ORDER BY id ASC');

        echo View::render(CORE_PATH . '/Admin/Views/layouts/admin.php', [
            'pageTitle' => 'قوالب الرسائل',
            'contentView' => __DIR__ . '/../Views/templates/index.php',
            'rows' => $rows,
        ]);
    }

    public function create(array $params): void
    {
        Auth::requirePermission('templates.manage');

        echo View::render(CORE_PATH . '/Admin/Views/layouts/admin.php', [
            'pageTitle' => 'إضافة قالب رسالة',
            'contentView' => __DIR__ . '/../Views/templates/form.php',
            'item' => null,
        ]);
    }

    public function store(array $params): void
    {
        Auth::requirePermission('templates.manage');
        Csrf::verifyRequestOrFail();

        $name = Security::cleanText(Request::trimmed('name'));
        $body = Security::cleanText((string) Request::post('body', ''));

        if ($name === '' || $body === '') {
            Session::flash('error', 'اسم القالب ونصه مطلوبان.');
            Response::redirect(Url::admin('directory-templates/create'));
        }

        Database::insert('directory_message_templates', [
            'name' => $name,
            'body' => $body,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        ActivityLog::record('directory_template_create', 'إضافة قالب رسالة: ' . $name);
        Session::flash('success', 'تمت إضافة القالب.');
        Response::redirect(Url::admin('directory-templates'));
    }

    public function edit(array $params): void
    {
        Auth::requirePermission('templates.manage');

        $item = Database::fetchOne('SELECT * FROM ' . Database::table('directory_message_templates') . ' WHERE id = ?', [(int) $params['id']]);
        if (!$item) {
            Response::redirect(Url::admin('directory-templates'));
        }

        echo View::render(CORE_PATH . '/Admin/Views/layouts/admin.php', [
            'pageTitle' => 'تعديل قالب رسالة',
            'contentView' => __DIR__ . '/../Views/templates/form.php',
            'item' => $item,
        ]);
    }

    public function update(array $params): void
    {
        Auth::requirePermission('templates.manage');
        Csrf::verifyRequestOrFail();

        $id = (int) $params['id'];
        $name = Security::cleanText(Request::trimmed('name'));
        $body = Security::cleanText((string) Request::post('body', ''));

        if ($name === '' || $body === '') {
            Session::flash('error', 'اسم القالب ونصه مطلوبان.');
            Response::redirect(Url::admin('directory-templates/' . $id . '/edit'));
        }

        Database::update('directory_message_templates', [
            'name' => $name,
            'body' => $body,
            'updated_at' => date('Y-m-d H:i:s'),
        ], ['id' => $id]);

        ActivityLog::record('directory_template_update', 'تعديل قالب رسالة: ' . $name, 'directory_message_template', $id);
        Session::flash('success', 'تم تحديث القالب.');
        Response::redirect(Url::admin('directory-templates'));
    }

    public function copy(array $params): void
    {
        Auth::requirePermission('templates.manage');
        Csrf::verifyRequestOrFail();

        $item = Database::fetchOne('SELECT * FROM ' . Database::table('directory_message_templates') . ' WHERE id = ?', [(int) $params['id']]);
        if ($item) {
            Database::insert('directory_message_templates', [
                'name' => $item['name'] . ' (نسخة)',
                'body' => $item['body'],
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ]);
            ActivityLog::record('directory_template_copy', 'نسخ قالب رسالة: ' . $item['name']);
        }

        Session::flash('success', 'تم نسخ القالب.');
        Response::redirect(Url::admin('directory-templates'));
    }

    public function delete(array $params): void
    {
        Auth::requirePermission('templates.manage');
        Csrf::verifyRequestOrFail();

        $id = (int) $params['id'];
        Database::delete('directory_message_templates', ['id' => $id]);
        ActivityLog::record('directory_template_delete', 'حذف قالب رسالة رقم #' . $id);
        Session::flash('success', 'تم حذف القالب.');
        Response::redirect(Url::admin('directory-templates'));
    }

    public function preview(array $params): void
    {
        Auth::requirePermission('templates.manage');

        $item = Database::fetchOne('SELECT * FROM ' . Database::table('directory_message_templates') . ' WHERE id = ?', [(int) $params['id']]);
        if (!$item) {
            Response::redirect(Url::admin('directory-templates'));
        }

        preg_match_all('/\{([a-zA-Z0-9_]+)\}/', $item['body'], $matches);
        $placeholders = array_values(array_unique($matches[1] ?? []));

        $values = [];
        $rendered = $item['body'];
        foreach ($placeholders as $key) {
            $value = trim((string) Request::get($key, ''));
            $values[$key] = $value;
            $rendered = str_replace('{' . $key . '}', $value !== '' ? $value : '{' . $key . '}', $rendered);
        }

        echo View::render(CORE_PATH . '/Admin/Views/layouts/admin.php', [
            'pageTitle' => 'معاينة قالب: ' . $item['name'],
            'contentView' => __DIR__ . '/../Views/templates/preview.php',
            'item' => $item,
            'placeholders' => $placeholders,
            'values' => $values,
            'rendered' => $rendered,
        ]);
    }
}
