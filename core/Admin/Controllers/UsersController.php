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

final class UsersController
{
    public function index(array $params): void
    {
        Auth::requirePermission('system.users');

        $users = Database::fetchAll(
            'SELECT u.*, r.name AS role_name FROM ' . Database::table('admin_users') . ' u
             LEFT JOIN ' . Database::table('roles') . ' r ON r.id = u.role_id
             ORDER BY u.id ASC'
        );

        echo View::render(CORE_PATH . '/Admin/Views/layouts/admin.php', [
            'pageTitle' => 'المستخدمون الإداريون',
            'contentView' => CORE_PATH . '/Admin/Views/users/index.php',
            'users' => $users,
        ]);
    }

    public function create(array $params): void
    {
        Auth::requirePermission('system.users');
        $roles = Database::fetchAll('SELECT * FROM ' . Database::table('roles') . ' ORDER BY id ASC');

        echo View::render(CORE_PATH . '/Admin/Views/layouts/admin.php', [
            'pageTitle' => 'إضافة مستخدم إداري',
            'contentView' => CORE_PATH . '/Admin/Views/users/form.php',
            'roles' => $roles,
            'user' => null,
        ]);
    }

    public function store(array $params): void
    {
        Auth::requirePermission('system.users');
        Csrf::verifyRequestOrFail();

        $name = Security::cleanText(Request::trimmed('name'));
        $username = preg_replace('/[^a-zA-Z0-9_\.]/', '', Request::trimmed('username'));
        $email = filter_var(Request::trimmed('email'), FILTER_VALIDATE_EMAIL) ?: '';
        $password = (string) Request::post('password', '');
        $roleId = Request::int('role_id');

        $errors = [];
        if ($name === '') $errors[] = 'الاسم مطلوب.';
        if ($username === '') $errors[] = 'اسم المستخدم مطلوب.';
        if ($email === '') $errors[] = 'البريد الإلكتروني غير صحيح.';
        if (strlen($password) < 8) $errors[] = 'كلمة المرور يجب ألا تقل عن 8 أحرف.';

        if (!$errors) {
            $exists = Database::fetchValue(
                'SELECT COUNT(*) FROM ' . Database::table('admin_users') . ' WHERE username = ? OR email = ?',
                [$username, $email]
            );
            if ($exists) {
                $errors[] = 'اسم المستخدم أو البريد الإلكتروني مستخدم مسبقًا.';
            }
        }

        if ($errors) {
            $roles = Database::fetchAll('SELECT * FROM ' . Database::table('roles') . ' ORDER BY id ASC');
            echo View::render(CORE_PATH . '/Admin/Views/layouts/admin.php', [
                'pageTitle' => 'إضافة مستخدم إداري',
                'contentView' => CORE_PATH . '/Admin/Views/users/form.php',
                'roles' => $roles,
                'user' => null,
                'errors' => $errors,
            ]);
            return;
        }

        Database::insert('admin_users', [
            'name' => $name,
            'username' => $username,
            'email' => $email,
            'password_hash' => Security::hashPassword($password),
            'role_id' => $roleId,
            'status' => 'active',
            'created_at' => date('Y-m-d H:i:s'),
        ]);

        ActivityLog::record('user_create', 'إضافة مستخدم إداري: ' . $username);
        Session::flash('success', 'تم إضافة المستخدم بنجاح.');
        Response::redirect(Url::admin('users'));
    }

    public function toggleStatus(array $params): void
    {
        Auth::requirePermission('system.users');
        Csrf::verifyRequestOrFail();

        $id = (int) $params['id'];
        $current = Database::fetchOne('SELECT * FROM ' . Database::table('admin_users') . ' WHERE id = ?', [$id]);

        if ($current && (int) ($current['id'] ?? 0) === (int) (Auth::user()['id'] ?? 0)) {
            Session::flash('error', 'لا يمكنك تعطيل حسابك الخاص.');
            Response::redirect(Url::admin('users'));
        }

        if ($current) {
            $newStatus = $current['status'] === 'active' ? 'inactive' : 'active';
            Database::update('admin_users', ['status' => $newStatus], ['id' => $id]);
            ActivityLog::record('user_status', 'تغيير حالة مستخدم: ' . $current['username'] . ' إلى ' . $newStatus);
        }

        Session::flash('success', 'تم تحديث حالة المستخدم.');
        Response::redirect(Url::admin('users'));
    }

    public function delete(array $params): void
    {
        Auth::requirePermission('system.users');
        Csrf::verifyRequestOrFail();

        $id = (int) $params['id'];
        if ($id === (int) (Auth::user()['id'] ?? 0)) {
            Session::flash('error', 'لا يمكنك حذف حسابك الخاص.');
            Response::redirect(Url::admin('users'));
        }

        \Core\TwoFactor::disable($id);
        Database::delete('admin_users', ['id' => $id]);
        ActivityLog::record('user_delete', 'حذف مستخدم إداري رقم: ' . $id);
        Session::flash('success', 'تم حذف المستخدم.');
        Response::redirect(Url::admin('users'));
    }
}
