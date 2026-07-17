<?php

namespace Core\Admin\Controllers;

use Core\ActivityLog;
use Core\Auth;
use Core\Database;
use Core\Settings;
use Core\Support\Csrf;
use Core\Support\Request;
use Core\Support\Response;
use Core\Support\Security;
use Core\Support\Session;
use Core\Support\Url;
use Core\View;

final class BranchesController
{
    public function index(array $params): void
    {
        Auth::requirePermission('content.tree');

        $branches = Database::fetchAll('SELECT * FROM ' . Database::table('branches') . ' ORDER BY sort_order ASC, id ASC');

        echo View::render(CORE_PATH . '/Admin/Views/layouts/admin.php', [
            'pageTitle' => 'الفروع',
            'contentView' => CORE_PATH . '/Admin/Views/branches/index.php',
            'branches' => $branches,
        ]);
    }

    public function store(array $params): void
    {
        Auth::requirePermission('content.tree');
        Csrf::verifyRequestOrFail();

        $name = Security::cleanText(Request::trimmed('name'));
        if ($name !== '') {
            $maxOrder = (int) Database::fetchValue('SELECT COALESCE(MAX(sort_order),0) FROM ' . Database::table('branches'));
            Database::insert('branches', [
                'name' => $name,
                'description' => Security::cleanText(Request::trimmed('description')),
                'sort_order' => $maxOrder + 1,
                'show_public_page' => Request::bool('show_public_page') ? 1 : 0,
                'status' => 'active',
                'created_at' => date('Y-m-d H:i:s'),
            ]);

            // تفعيل خصائص الفروع تلقائيًا بمجرد إضافة أول فرع
            if (Settings::get('branches_enabled', '0') !== '1') {
                Settings::set('branches_enabled', '1');
            }

            ActivityLog::record('branch_create', 'إضافة فرع: ' . $name);
            Session::flash('success', 'تمت إضافة الفرع.');
        }

        Response::redirect(Url::admin('branches'));
    }

    public function update(array $params): void
    {
        Auth::requirePermission('content.tree');
        Csrf::verifyRequestOrFail();

        $id = (int) $params['id'];
        Database::update('branches', [
            'name' => Security::cleanText(Request::trimmed('name')),
            'description' => Security::cleanText(Request::trimmed('description')),
            'sort_order' => Request::int('sort_order', 0),
            'show_public_page' => Request::bool('show_public_page') ? 1 : 0,
            'status' => Request::post('status') === 'inactive' ? 'inactive' : 'active',
        ], ['id' => $id]);

        Session::flash('success', 'تم تحديث الفرع.');
        Response::redirect(Url::admin('branches'));
    }

    public function delete(array $params): void
    {
        Auth::requirePermission('content.tree');
        Csrf::verifyRequestOrFail();

        Database::delete('branches', ['id' => (int) $params['id']]);
        ActivityLog::record('branch_delete', 'حذف فرع رقم: ' . $params['id']);
        Session::flash('success', 'تم حذف الفرع.');
        Response::redirect(Url::admin('branches'));
    }
}
