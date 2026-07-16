<?php
/** @var string $pageTitle
 *  @var string $contentView
 */
use Core\Auth;
use Core\ModuleManager;
use Core\Settings;
use Core\Support\Session;
use Core\Support\Url;
use Core\Terms;
use Core\View;

$user = Auth::user();
$currentPath = \Core\Support\Request::path();

$coreMenu = [
    ['label' => 'الرئيسية', 'url' => Url::admin(''), 'icon' => '🏠', 'perm' => null],
    ['label' => 'الوسائط', 'url' => Url::admin('media'), 'icon' => '🖼️', 'perm' => 'content.media'],
    ['label' => 'القوائم', 'url' => Url::admin('menus'), 'icon' => '📋', 'perm' => 'content.pages'],
    ['label' => 'المدن', 'url' => Url::admin('cities'), 'icon' => '🏙️', 'perm' => 'content.pages'],
];

if (Settings::get('branches_enabled', '0') === '1') {
    $coreMenu[] = ['label' => 'الفروع', 'url' => Url::admin('branches'), 'icon' => '🌿', 'perm' => 'content.tree'];
}

$moduleMenu = ModuleManager::adminMenuItems();

$systemMenu = [
    ['label' => 'المستخدمون الإداريون', 'url' => Url::admin('users'), 'icon' => '👤', 'perm' => 'system.users'],
    ['label' => 'الأدوار والصلاحيات', 'url' => Url::admin('roles'), 'icon' => '🛡️', 'perm' => 'system.users'],
    ['label' => 'الإضافات', 'url' => Url::admin('modules'), 'icon' => '🧩', 'perm' => 'system.modules'],
    ['label' => 'النسخ الاحتياطي', 'url' => Url::admin('backup'), 'icon' => '💾', 'perm' => 'system.backup'],
    ['label' => 'سجل العمليات', 'url' => Url::admin('logs'), 'icon' => '🧾', 'perm' => 'system.logs'],
    ['label' => 'الإعدادات', 'url' => Url::admin('settings'), 'icon' => '⚙️', 'perm' => 'system.settings'],
];

function fam_admin_active(string $url, string $current): bool
{
    $urlPath = parse_url($url, PHP_URL_PATH);
    return $urlPath !== null && ($current === rtrim($urlPath, '/') || str_starts_with($current . '/', rtrim($urlPath, '/') . '/'));
}
?><!doctype html>
<html lang="ar" dir="rtl">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= View::e($pageTitle ?? 'لوحة التحكم') ?> — لوحة التحكم</title>
<link rel="stylesheet" href="<?= Url::to('admin/assets/css/admin.css') ?>">
</head>
<body>
<div class="admin-shell">
  <aside class="admin-sidebar" data-sidebar>
    <div class="admin-brand">
      <span>🕌</span>
      <strong>لوحة تحكم <?= View::e(Settings::get('identity_short_name', 'الموقع')) ?></strong>
    </div>
    <nav class="admin-nav">
      <?php foreach ($coreMenu as $item): if ($item['perm'] && !Auth::can($item['perm'])) continue; ?>
        <a class="<?= fam_admin_active($item['url'], $currentPath) ? 'active' : '' ?>" href="<?= View::e($item['url']) ?>"><span><?= $item['icon'] ?></span><?= View::e($item['label']) ?></a>
      <?php endforeach; ?>

      <?php foreach ($moduleMenu as $item): if (!empty($item['perm']) && !Auth::can($item['perm'])) continue; ?>
        <a class="<?= fam_admin_active($item['url'], $currentPath) ? 'active' : '' ?>" href="<?= View::e($item['url']) ?>"><span><?= $item['icon'] ?? '▫️' ?></span><?= View::e($item['label']) ?></a>
      <?php endforeach; ?>

      <div class="admin-nav-sep">النظام</div>
      <?php foreach ($systemMenu as $item): if ($item['perm'] && !Auth::can($item['perm'])) continue; ?>
        <a class="<?= fam_admin_active($item['url'], $currentPath) ? 'active' : '' ?>" href="<?= View::e($item['url']) ?>"><span><?= $item['icon'] ?></span><?= View::e($item['label']) ?></a>
      <?php endforeach; ?>
    </nav>
  </aside>

  <div class="admin-main">
    <header class="admin-topbar">
      <button class="icon-btn" data-sidebar-toggle aria-label="القائمة">☰</button>
      <h1><?= View::e($pageTitle ?? '') ?></h1>
      <div class="admin-topbar-user">
        <a href="<?= Url::to('') ?>" target="_blank" class="btn-link">مشاهدة الموقع ↗</a>
        <span><?= View::e($user['name'] ?? '') ?></span>
        <a href="<?= Url::admin('logout') ?>" class="btn-link">خروج</a>
      </div>
    </header>

    <div class="admin-content">
      <?php $success = Session::flash('success'); $error = Session::flash('error'); ?>
      <?php if ($success): ?><div class="alert alert-success"><?= View::e($success) ?></div><?php endif; ?>
      <?php if ($error): ?><div class="alert alert-error"><?= View::e($error) ?></div><?php endif; ?>

      <?php include $contentView; ?>
    </div>
  </div>
</div>
<script src="<?= Url::to('admin/assets/js/admin.js') ?>"></script>
</body>
</html>
