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

final class MenusController
{
    public function index(array $params): void
    {
        Auth::requirePermission('content.pages');

        $slug = $params['slug'] ?? 'main';
        if (!in_array($slug, ['main', 'mobile', 'footer'], true)) {
            $slug = 'main';
        }

        $menu = Database::fetchOne('SELECT * FROM ' . Database::table('menus') . ' WHERE slug = ?', [$slug]);
        $items = $menu ? Database::fetchAll(
            'SELECT * FROM ' . Database::table('menu_items') . ' WHERE menu_id = ? ORDER BY sort_order ASC',
            [$menu['id']]
        ) : [];

        echo View::render(CORE_PATH . '/Admin/Views/layouts/admin.php', [
            'pageTitle' => 'القوائم',
            'contentView' => CORE_PATH . '/Admin/Views/menus/index.php',
            'slug' => $slug,
            'menu' => $menu,
            'items' => $items,
        ]);
    }

    public function store(array $params): void
    {
        Auth::requirePermission('content.pages');
        Csrf::verifyRequestOrFail();

        $slug = $params['slug'] ?? 'main';
        $menu = Database::fetchOne('SELECT * FROM ' . Database::table('menus') . ' WHERE slug = ?', [$slug]);
        if (!$menu) {
            Response::redirect(Url::admin('menus'));
        }

        $label = Security::cleanText(Request::trimmed('label'));
        $url = Security::cleanText(Request::trimmed('url'));

        if ($label !== '') {
            $maxOrder = (int) Database::fetchValue('SELECT COALESCE(MAX(sort_order),0) FROM ' . Database::table('menu_items') . ' WHERE menu_id = ?', [$menu['id']]);
            Database::insert('menu_items', [
                'menu_id' => $menu['id'],
                'label' => $label,
                'link_type' => 'custom_url',
                'url' => $url !== '' ? $url : '#',
                'sort_order' => $maxOrder + 1,
                'open_new_tab' => Request::bool('open_new_tab') ? 1 : 0,
                'hide_on' => in_array(Request::post('hide_on'), ['desktop', 'mobile'], true) ? Request::post('hide_on') : 'none',
            ]);
            ActivityLog::record('menu_item_create', 'إضافة عنصر قائمة: ' . $label);
        }

        Response::redirect(Url::admin('menus/' . $slug));
    }

    public function update(array $params): void
    {
        Auth::requirePermission('content.pages');
        Csrf::verifyRequestOrFail();

        $id = (int) $params['id'];
        Database::update('menu_items', [
            'label' => Security::cleanText(Request::trimmed('label')),
            'url' => Security::cleanText(Request::trimmed('url')),
            'sort_order' => Request::int('sort_order', 0),
            'open_new_tab' => Request::bool('open_new_tab') ? 1 : 0,
            'hide_on' => in_array(Request::post('hide_on'), ['desktop', 'mobile'], true) ? Request::post('hide_on') : 'none',
        ], ['id' => $id]);

        Session::flash('success', 'تم تحديث عنصر القائمة.');
        Response::redirect(Url::admin('menus/' . Security::cleanText(Request::get('slug', 'main'))));
    }

    public function delete(array $params): void
    {
        Auth::requirePermission('content.pages');
        Csrf::verifyRequestOrFail();

        Database::delete('menu_items', ['id' => (int) $params['id']]);
        ActivityLog::record('menu_item_delete', 'حذف عنصر قائمة رقم: ' . $params['id']);
        Response::redirect(Url::admin('menus/' . Security::cleanText(Request::get('slug', 'main'))));
    }
}
