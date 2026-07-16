<?php
/** @var string $content */
use Core\Database;
use Core\Settings;
use Core\Support\Url;
use Core\Terms;

$officialName = Settings::get('identity_official_name', '');
$shortName = Settings::get('identity_short_name', '') ?: $officialName;
$logoId = Settings::get('identity_logo_media_id', '');
$logoUrl = '';
if ($logoId) {
    $logo = Database::fetchOne('SELECT stored_path FROM ' . Database::table('media') . ' WHERE id = ?', [$logoId]);
    if ($logo) {
        $logoUrl = \Core\Media::url($logo['stored_path']);
    }
}

$mainMenu = [];
$mobileMenu = [];
$footerMenu = [];
if (Database::tableExists('menu_items')) {
    $mainMenu = Database::fetchAll(
        'SELECT mi.* FROM ' . Database::table('menu_items') . ' mi
         JOIN ' . Database::table('menus') . ' m ON m.id = mi.menu_id
         WHERE m.slug = "main" AND mi.hide_on != "desktop" ORDER BY mi.sort_order ASC'
    );
    $mobileMenu = Database::fetchAll(
        'SELECT mi.* FROM ' . Database::table('menu_items') . ' mi
         JOIN ' . Database::table('menus') . ' m ON m.id = mi.menu_id
         WHERE m.slug = "mobile" AND mi.hide_on != "mobile" ORDER BY mi.sort_order ASC'
    );
    if (!$mobileMenu) {
        $mobileMenu = $mainMenu;
    }
    $footerMenu = Database::fetchAll(
        'SELECT mi.* FROM ' . Database::table('menu_items') . ' mi
         JOIN ' . Database::table('menus') . ' m ON m.id = mi.menu_id
         WHERE m.slug = "footer" ORDER BY mi.sort_order ASC'
    );
}

function fam_menu_link(array $item): string
{
    if (!empty($item['url'])) {
        return $item['url'];
    }
    return Url::to('');
}

$primaryColor = Settings::get('theme_color_primary', '#0f6e5e');
$secondaryColor = Settings::get('theme_color_secondary', '#c9a24b');
$pageTitle = $pageTitle ?? $officialName;
$metaDescription = $metaDescription ?? Settings::get('seo_default_description', '');
?><!doctype html>
<html lang="ar" dir="rtl">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<title><?= \Core\View::e($pageTitle) ?></title>
<meta name="description" content="<?= \Core\View::e($metaDescription) ?>">
<link rel="canonical" href="<?= \Core\View::e(Url::current()) ?>">
<?php if ($logoUrl): ?><link rel="icon" href="<?= \Core\View::e($logoUrl) ?>"><?php endif; ?>
<meta property="og:title" content="<?= \Core\View::e($pageTitle) ?>">
<meta property="og:description" content="<?= \Core\View::e($metaDescription) ?>">
<meta property="og:type" content="website">
<?php if ($logoUrl): ?><meta property="og:image" content="<?= \Core\View::e(Url::origin() . $logoUrl) ?>"><?php endif; ?>
<link rel="stylesheet" href="<?= Url::theme('assets/css/site.css') ?>">
<style>:root{--c-primary:<?= \Core\View::e($primaryColor) ?>;--c-secondary:<?= \Core\View::e($secondaryColor) ?>;}</style>
</head>
<body>

<header class="site-header">
  <div class="container bar">
    <a href="<?= Url::to('') ?>" class="brand">
      <?php if ($logoUrl): ?><img src="<?= \Core\View::e($logoUrl) ?>" alt="<?= \Core\View::e($shortName) ?>"><?php endif; ?>
      <span><?= \Core\View::e($shortName) ?></span>
    </a>
    <nav class="nav-desktop">
      <?php foreach ($mainMenu as $item): ?>
        <a href="<?= \Core\View::e(fam_menu_link($item)) ?>" <?= $item['open_new_tab'] ? 'target="_blank" rel="noopener"' : '' ?>><?= \Core\View::e($item['label']) ?></a>
      <?php endforeach; ?>
    </nav>
    <div class="header-actions">
      <a class="icon-btn" href="<?= Url::to('search') ?>" aria-label="بحث">🔍</a>
      <button class="icon-btn nav-mobile-trigger" data-nav-trigger aria-label="القائمة">☰</button>
    </div>
  </div>
</header>

<div class="mobile-nav" data-mobile-nav>
  <div class="panel">
    <button class="icon-btn close-btn" data-nav-close aria-label="إغلاق">✕</button>
    <?php foreach ($mobileMenu as $item): ?>
      <a href="<?= \Core\View::e(fam_menu_link($item)) ?>"><?= \Core\View::e($item['label']) ?></a>
    <?php endforeach; ?>
  </div>
</div>

<main>
<?= $content ?>
</main>

<footer class="site-footer">
  <div class="container">
    <div class="footer-cols">
      <div>
        <h3><?= \Core\View::e($shortName) ?></h3>
        <p><?= \Core\View::e(Settings::get('identity_brief', '')) ?></p>
        <div class="social-row">
          <?php
          $socials = [
              'social_whatsapp' => '💬', 'social_x' => '𝕏', 'social_instagram' => '📷',
              'social_snapchat' => '👻', 'social_youtube' => '▶', 'social_telegram' => '✈',
          ];
          foreach ($socials as $key => $icon):
              $val = Settings::get($key, '');
              if ($val):
          ?>
            <a href="<?= \Core\View::e($val) ?>" target="_blank" rel="noopener"><?= $icon ?></a>
          <?php endif; endforeach; ?>
        </div>
      </div>
      <div>
        <h3>روابط</h3>
        <?php foreach ($footerMenu as $item): ?>
          <p><a href="<?= \Core\View::e(fam_menu_link($item)) ?>"><?= \Core\View::e($item['label']) ?></a></p>
        <?php endforeach; ?>
      </div>
      <div>
        <h3>التواصل</h3>
        <?php if (Settings::get('contact_phone')): ?><p><?= \Core\View::e(Settings::get('contact_phone')) ?></p><?php endif; ?>
        <?php if (Settings::get('contact_email')): ?><p><?= \Core\View::e(Settings::get('contact_email')) ?></p><?php endif; ?>
      </div>
    </div>
    <div class="copyright">© <?= date('Y') ?> <?= \Core\View::e($shortName) ?> — <?= \Core\View::e(Terms::phrase('official_site')) ?></div>
  </div>
</footer>

<script src="<?= Url::theme('assets/js/site.js') ?>"></script>
</body>
</html>
