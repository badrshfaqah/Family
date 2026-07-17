<?php

namespace Core\Admin\Controllers;

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

final class CitiesController
{
    public function index(array $params): void
    {
        Auth::requirePermission('content.pages');

        $cities = Database::fetchAll('SELECT * FROM ' . Database::table('cities') . ' ORDER BY sort_order ASC, id ASC');

        echo View::render(CORE_PATH . '/Admin/Views/layouts/admin.php', [
            'pageTitle' => 'المدن',
            'contentView' => CORE_PATH . '/Admin/Views/cities/index.php',
            'cities' => $cities,
        ]);
    }

    public function store(array $params): void
    {
        Auth::requirePermission('content.pages');
        Csrf::verifyRequestOrFail();

        $name = Security::cleanText(Request::trimmed('name'));
        if ($name !== '') {
            $maxOrder = (int) Database::fetchValue('SELECT COALESCE(MAX(sort_order),0) FROM ' . Database::table('cities'));
            Database::insert('cities', ['name' => $name, 'sort_order' => $maxOrder + 1, 'status' => 'active']);
            ActivityLog::record('city_create', 'إضافة مدينة: ' . $name);
            Session::flash('success', 'تمت إضافة المدينة.');
        }

        Response::redirect(Url::admin('cities'));
    }

    public function update(array $params): void
    {
        Auth::requirePermission('content.pages');
        Csrf::verifyRequestOrFail();

        $id = (int) $params['id'];
        $name = Security::cleanText(Request::trimmed('name'));
        $status = Request::post('status') === 'inactive' ? 'inactive' : 'active';
        $sortOrder = Request::int('sort_order', 0);

        if ($name !== '') {
            Database::update('cities', ['name' => $name, 'status' => $status, 'sort_order' => $sortOrder], ['id' => $id]);
            ActivityLog::record('city_update', 'تعديل مدينة رقم: ' . $id);
            Session::flash('success', 'تم تحديث المدينة.');
        }

        Response::redirect(Url::admin('cities'));
    }

    public function delete(array $params): void
    {
        Auth::requirePermission('content.pages');
        Csrf::verifyRequestOrFail();

        Database::delete('cities', ['id' => (int) $params['id']]);
        ActivityLog::record('city_delete', 'حذف مدينة رقم: ' . $params['id']);
        Session::flash('success', 'تم حذف المدينة.');
        Response::redirect(Url::admin('cities'));
    }
}
