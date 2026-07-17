<?php

namespace Core\Admin\Controllers;

use Core\ActivityLog;
use Core\Auth;
use Core\DemoData;
use Core\ModuleManager;
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
            'disabledModules' => $this->disabledModules(),
            'canManageModules' => Auth::can('system.modules'),
        ]);
    }

    public function seed(array $params): void
    {
        Auth::requirePermission('system.settings');
        Csrf::verifyRequestOrFail();

        $created = DemoData::seed();
        $message = 'تم توليد بيانات تجريبية: ' . implode('، ', $created) . '.';

        $disabled = $this->disabledModules();
        if ($disabled) {
            $message .= ' (لم تُولَّد بيانات للإضافات غير المفعّلة: ' . implode('، ', array_column($disabled, 'name')) . ')';
        }

        Session::flash('success', $message);
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

    /** تثبيت وتفعيل جميع الإضافات المرفقة دفعة واحدة تمهيدًا لتوليد بيانات تجريبية كاملة. */
    public function enableModules(array $params): void
    {
        Auth::requirePermission('system.modules');
        Csrf::verifyRequestOrFail();

        $done = [];
        $failed = [];

        foreach (ModuleManager::discover() as $slug => $manifest) {
            $name = $manifest['name'] ?? $slug;
            if (!ModuleManager::isInstalled($slug)) {
                $result = ModuleManager::install($slug);
                $result['ok'] ? $done[] = $name : $failed[] = $name;
            } elseif (!ModuleManager::isEnabled($slug)) {
                $result = ModuleManager::setStatus($slug, 'enabled');
                $result['ok'] ? $done[] = $name : $failed[] = $name;
            }
        }

        if ($done) {
            ActivityLog::record('module_install', 'تثبيت/تفعيل جماعي للإضافات: ' . implode('، ', $done));
        }

        if ($failed) {
            Session::flash('error', 'تعذر تثبيت: ' . implode('، ', $failed));
        } else {
            Session::flash('success', $done ? 'تم تثبيت وتفعيل جميع الإضافات (' . count($done) . ').' : 'جميع الإضافات مفعّلة مسبقًا.');
        }

        Response::redirect(Url::admin('demo-data'));
    }

    /** @return array<int, array{slug: string, name: string}> الإضافات المكتشفة وغير المفعّلة حاليًا */
    private function disabledModules(): array
    {
        $disabled = [];
        foreach (ModuleManager::discover() as $slug => $manifest) {
            if (!ModuleManager::isEnabled($slug)) {
                $disabled[] = ['slug' => $slug, 'name' => $manifest['name'] ?? $slug];
            }
        }
        return $disabled;
    }
}
