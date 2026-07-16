<?php

namespace Core\Admin\Controllers;

use Core\ActivityLog;
use Core\Auth;
use Core\Database;
use Core\Permissions;
use Core\Support\Csrf;
use Core\Support\Request;
use Core\Support\Response;
use Core\Support\Security;
use Core\Support\Session;
use Core\Support\Url;
use Core\View;

final class RolesController
{
    public function index(array $params): void
    {
        Auth::requirePermission('system.users');
        $roles = Database::fetchAll('SELECT * FROM ' . Database::table('roles') . ' ORDER BY id ASC');

        echo View::render(CORE_PATH . '/Admin/Views/layouts/admin.php', [
            'pageTitle' => 'الأدوار والصلاحيات',
            'contentView' => CORE_PATH . '/Admin/Views/roles/index.php',
            'roles' => $roles,
        ]);
    }

    public function edit(array $params): void
    {
        Auth::requirePermission('system.users');
        $id = (int) $params['id'];
        $role = Database::fetchOne('SELECT * FROM ' . Database::table('roles') . ' WHERE id = ?', [$id]);
        if (!$role) {
            Response::redirect(Url::admin('roles'));
        }

        $assigned = array_column(
            Database::fetchAll('SELECT permission_key FROM ' . Database::table('role_permissions') . ' WHERE role_id = ?', [$id]),
            'permission_key'
        );

        $extra = Database::tableExists('permissions_extra')
            ? Database::fetchAll('SELECT * FROM ' . Database::table('permissions_extra') . ' ORDER BY module_slug')
            : [];

        echo View::render(CORE_PATH . '/Admin/Views/layouts/admin.php', [
            'pageTitle' => 'صلاحيات الدور: ' . $role['name'],
            'contentView' => CORE_PATH . '/Admin/Views/roles/edit.php',
            'role' => $role,
            'catalog' => Permissions::catalog(),
            'extraPermissions' => $extra,
            'assigned' => $assigned,
        ]);
    }

    public function update(array $params): void
    {
        Auth::requirePermission('system.users');
        Csrf::verifyRequestOrFail();

        $id = (int) $params['id'];
        $role = Database::fetchOne('SELECT * FROM ' . Database::table('roles') . ' WHERE id = ?', [$id]);
        if (!$role) {
            Response::redirect(Url::admin('roles'));
        }

        if ($role['slug'] === 'system_admin') {
            Session::flash('error', 'دور مدير النظام يملك جميع الصلاحيات دائمًا ولا يمكن تعديله.');
            Response::redirect(Url::admin('roles'));
        }

        $selected = (array) Request::post('permissions', []);
        $validKeys = Permissions::allKeys();
        $extraKeys = Database::tableExists('permissions_extra')
            ? array_column(Database::fetchAll('SELECT permission_key FROM ' . Database::table('permissions_extra')), 'permission_key')
            : [];
        $validKeys = array_merge($validKeys, $extraKeys);

        Database::delete('role_permissions', ['role_id' => $id]);
        foreach ($selected as $key) {
            if (in_array($key, $validKeys, true)) {
                Database::insert('role_permissions', ['role_id' => $id, 'permission_key' => Security::cleanText($key)]);
            }
        }

        ActivityLog::record('role_update', 'تحديث صلاحيات الدور: ' . $role['name']);
        Session::flash('success', 'تم تحديث صلاحيات الدور.');
        Response::redirect(Url::admin('roles'));
    }
}
