<?php

namespace Core\Admin\Controllers;

use Core\Auth;
use Core\DemoData;
use Core\Support\Csrf;
use Core\Support\Response;
use Core\Support\Session;
use Core\Support\Url;
use Core\View;

final class DemoDataController
{
    public function index(array $params): void
    {
        Auth::requirePermission('system.settings');

        echo View::render(CORE_PATH . '/Admin/Views/layouts/admin.php', [
            'pageTitle' => 'بيانات تجريبية',
            'contentView' => CORE_PATH . '/Admin/Views/demo-data/index.php',
            'hasDemoData' => DemoData::hasDemoData(),
        ]);
    }

    public function seed(array $params): void
    {
        Auth::requirePermission('system.settings');
        Csrf::verifyRequestOrFail();

        $created = DemoData::seed();
        Session::flash('success', 'تم توليد بيانات تجريبية: ' . implode('، ', $created));
        Response::redirect(Url::admin('demo-data'));
    }

    public function purge(array $params): void
    {
        Auth::requirePermission('system.settings');
        Csrf::verifyRequestOrFail();

        $count = DemoData::purge();
        Session::flash('success', "تم حذف جميع البيانات التجريبية ({$count} عنصر).");
        Response::redirect(Url::admin('demo-data'));
    }
}
