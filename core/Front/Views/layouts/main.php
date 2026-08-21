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

$topBarAnnouncement = null;
$popupAnnouncement = null;
if (\Core\ModuleManager::isEnabled('announcements') && Database::tableExists('announcements')) {
    $activeWhere = 'status = "active" AND (starts_at IS NULL OR starts_at <= NOW()) AND (ends_at IS NULL OR ends_at >= NOW())';
    $topBarAnnouncement = Database::fetchOne(
        'SELECT * FROM ' . Database::table('announcements') . " WHERE {$activeWhere} AND placement = 'top_bar' ORDER BY id DESC LIMIT 1"
    );
    $popupAnnouncement = Database::fetchOne(
        'SELECT * FROM ' . Database::table('announcements') . " WHERE {$activeWhere} AND placement = 'popup' ORDER BY id DESC LIMIT 1"
    );
}

function fam_announce_visibility_class(array $a): string
{
    $classes = [];
    if (empty($a['show_on_desktop'])) {
        $classes[] = 'hide-on-desktop';
    }
    if (empty($a['show_on_mobile'])) {
        $classes[] = 'hide-on-mobile';
    }
    return implode(' ', $classes);
}

$footerMenu = [];
if (Database::tableExists('menu_items')) {
    $footerMenu = Database::fetchAll(
        'SELECT mi.* FROM ' . Database::table('menu_items') . ' mi
         JOIN ' . Database::table('menus') . ' m ON m.id = mi.menu_id
         WHERE m.slug = "footer" ORDER BY mi.sort_order ASC'
    );
}

// قائمة الموقع الموحدة: أعلى الصفحة في المتصفح، وقائمة جانبية على الجوال
$siteMenu = array_values(array_filter([
    ['url' => Url::to('directory/register'), 'icon' => '📱', 'label' => 'جوال القبيلة', 'show' => \Core\ModuleManager::isEnabled('directory')],
    ['url' => Url::to('calendar'), 'icon' => '📅', 'label' => 'الرزنامة', 'show' => \Core\ModuleManager::isEnabled('calendar')],
    ['url' => Url::to('gatherings'), 'icon' => '☕', 'label' => 'الجمعات', 'show' => \Core\ModuleManager::isEnabled('gatherings')],
    ['url' => Url::to('obituaries'), 'icon' => '🕊', 'label' => 'الوفيات', 'show' => \Core\ModuleManager::isEnabled('obituaries'), 'header' => false],
    ['url' => Url::to('poetry'), 'icon' => '🪶', 'label' => 'سماء الشعراء', 'show' => \Core\ModuleManager::isEnabled('poetry')],
    ['url' => Url::to('tree'), 'icon' => '🌳', 'label' => 'شجرة النسب', 'show' => \Core\ModuleManager::isEnabled('family-tree'), 'header' => false],
    ['url' => Url::to('gallery'), 'icon' => '🖼', 'label' => 'مكتبة الصور', 'show' => \Core\ModuleManager::isEnabled('gallery')],
    ['url' => Url::to('archive'), 'icon' => '📜', 'label' => 'الأرشيف', 'show' => \Core\ModuleManager::isEnabled('archive'), 'header' => false],
    ['url' => Url::to('news'), 'icon' => '📰', 'label' => 'الأخبار', 'show' => \Core\ModuleManager::isEnabled('news'), 'header' => false],
], fn($item) => $item['show']));

// القسم النشط حاليًا (للقائمة العلوية والجانبية والسفلية)
$navBasePath = rtrim((string) parse_url(Url::to(''), PHP_URL_PATH), '/');
$navCurrentPath = rtrim((string) parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH), '/');
$navCurrentRel = trim(substr($navCurrentPath, strlen($navBasePath)), '/');
$navIsActive = function (string $url) use ($navBasePath, $navCurrentRel): bool {
    $match = trim(substr(rtrim((string) parse_url($url, PHP_URL_PATH), '/'), strlen($navBasePath)), '/');
    $match = explode('/', $match)[0];
    return $match === '' ? $navCurrentRel === '' : str_starts_with($navCurrentRel . '/', $match . '/');
};

function fam_menu_link(array $item): string
{
    if (!empty($item['url'])) {
        return $item['url'];
    }
    return Url::to('');
}

$primaryColor = Settings::get('theme_color_primary', '#0f6e5e');
$secondaryColor = Settings::get('theme_color_secondary', '#c9a24b');
$fontStacks = [
    'default' => "'Tajawal','Segoe UI',Tahoma,Arial,sans-serif",
    'traditional' => "'Simplified Arabic','Traditional Arabic','Segoe UI',Tahoma,Arial,sans-serif",
    'modern' => "'Segoe UI',Helvetica,Arial,Tahoma,sans-serif",
];
$fontStack = $fontStacks[Settings::get('theme_font', 'default')] ?? $fontStacks['default'];
$pageTitle = $pageTitle ?? $officialName;
$metaDescription = $metaDescription ?? '';
if ($metaDescription === '') {
    $metaDescription = Settings::get('seo_default_description', '') ?: Settings::get('identity_brief', '');
}
// عنوان الصفحة مع اسم الموقع (بدون تكرار في الرئيسية)
$fullTitle = ($pageTitle === $officialName || $pageTitle === $shortName)
    ? $pageTitle
    : $pageTitle . ' | ' . $shortName;
$ogImage = $ogImage ?? ($logoUrl ? Url::origin() . $logoUrl : '');
$metaRobots = $metaRobots ?? '';
$jsonLd = $jsonLd ?? null;
?><!doctype html>
<html lang="ar" dir="rtl">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<title><?= \Core\View::e($fullTitle) ?></title>
<meta name="description" content="<?= \Core\View::e($metaDescription) ?>">
<link rel="canonical" href="<?= \Core\View::e(Url::current()) ?>">
<?php if ($metaRobots !== ''): ?><meta name="robots" content="<?= \Core\View::e($metaRobots) ?>"><?php endif; ?>
<?php if ($logoUrl): ?><link rel="icon" href="<?= \Core\View::e($logoUrl) ?>"><?php endif; ?>
<meta property="og:title" content="<?= \Core\View::e($fullTitle) ?>">
<meta property="og:description" content="<?= \Core\View::e($metaDescription) ?>">
<meta property="og:type" content="<?= \Core\View::e($ogType ?? 'website') ?>">
<meta property="og:url" content="<?= \Core\View::e(Url::current()) ?>">
<meta property="og:site_name" content="<?= \Core\View::e($shortName) ?>">
<meta property="og:locale" content="ar_AR">
<?php if ($ogImage): ?><meta property="og:image" content="<?= \Core\View::e($ogImage) ?>"><?php endif; ?>
<meta name="twitter:card" content="<?= $ogImage ? 'summary_large_image' : 'summary' ?>">
<meta name="twitter:title" content="<?= \Core\View::e($fullTitle) ?>">
<meta name="twitter:description" content="<?= \Core\View::e($metaDescription) ?>">
<?php if ($ogImage): ?><meta name="twitter:image" content="<?= \Core\View::e($ogImage) ?>"><?php endif; ?>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Tajawal:wght@400;500;700;800&display=swap">
<link rel="stylesheet" href="<?= Url::theme('assets/css/site.css') ?>?v=<?= CORE_VERSION ?>">
<style>:root{--c-primary:<?= \Core\View::e($primaryColor) ?>;--c-secondary:<?= \Core\View::e($secondaryColor) ?>;--font:<?= $fontStack ?>;}</style>
<link rel="manifest" href="<?= Url::to('manifest.webmanifest') ?>">
<meta name="theme-color" content="<?= \Core\View::e($primaryColor) ?>">
<link rel="apple-touch-icon" href="<?= Url::to('pwa-icon-192.png') ?>">
<?php
// بيانات منظمة لمحركات البحث: هوية الموقع + أي بيانات خاصة تمررها الصفحة
$ldGraph = [
    [
        '@type' => 'WebSite',
        'name' => $shortName,
        'url' => Url::origin() . Url::to(''),
        'inLanguage' => 'ar',
    ],
    array_filter([
        '@type' => 'Organization',
        'name' => $officialName ?: $shortName,
        'url' => Url::origin() . Url::to(''),
        'logo' => $logoUrl ? Url::origin() . $logoUrl : null,
    ]),
];
if (is_array($jsonLd) && $jsonLd) {
    $ldGraph[] = $jsonLd;
}
?>
<script type="application/ld+json" nonce="<?= \Core\Support\Security::cspNonce() ?>"><?= json_encode(['@context' => 'https://schema.org', '@graph' => $ldGraph], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?></script>
</head>
<body>

<?php if ($topBarAnnouncement): ?>
<div class="announce-bar <?= fam_announce_visibility_class($topBarAnnouncement) ?>" data-announce-bar data-id="<?= (int) $topBarAnnouncement['id'] ?>">
  <span><?= \Core\View::e($topBarAnnouncement['title']) ?><?php if (!empty($topBarAnnouncement['message'])): ?> — <?= \Core\View::e($topBarAnnouncement['message']) ?><?php endif; ?></span>
  <button type="button" class="close-btn" data-announce-close aria-label="إغلاق">✕</button>
</div>
<?php endif; ?>

<header class="site-header">
  <div class="container bar">
    <a href="<?= Url::to('') ?>" class="brand<?= $logoUrl ? ' has-logo' : '' ?>">
      <?php if ($logoUrl): ?><img src="<?= \Core\View::e($logoUrl) ?>" alt="<?= \Core\View::e($shortName) ?>"><?php endif; ?>
      <span><?= \Core\View::e($shortName) ?></span>
    </a>
    <div class="header-actions">
      <?php $pushKey = \Core\Push::isSupported() ? \Core\Push::publicKey() : ''; ?>
      <?php if ($pushKey !== ''): ?>
      <button class="btn-notify" data-push-enable data-hide-on-active
              data-vapid="<?= \Core\View::e($pushKey) ?>"
              data-subscribe-url="<?= Url::to('push/subscribe') ?>"
              data-install-url="<?= Url::to('install-app') ?>"
              data-csrf-token="<?= \Core\View::e(\Core\Support\Csrf::token()) ?>">فعّل التنبيهات</button>
      <?php endif; ?>
      <a class="icon-btn" href="<?= Url::to('search') ?>" aria-label="بحث">🔍</a>
      <button class="icon-btn nav-mobile-trigger" data-nav-trigger aria-label="القائمة">☰</button>
    </div>
  </div>
  <nav class="top-nav" aria-label="أقسام الموقع">
    <div class="container top-nav-in">
      <a href="<?= Url::to('') ?>" class="<?= $navCurrentRel === '' ? 'active' : '' ?>">الرئيسية</a>
      <?php foreach ($siteMenu as $navItem): if (($navItem['header'] ?? true) === false) continue; ?>
        <a href="<?= \Core\View::e($navItem['url']) ?>" class="<?= $navIsActive($navItem['url']) ? 'active' : '' ?>"><?= \Core\View::e($navItem['label']) ?></a>
      <?php endforeach; ?>
    </div>
  </nav>
</header>
<div class="sadu-strip" aria-hidden="true"></div>

<div class="mobile-nav" data-mobile-nav>
  <div class="panel">
    <div class="panel-head">
      <b><?= \Core\View::e($shortName) ?></b>
      <button class="icon-btn close-btn" data-nav-close aria-label="إغلاق">✕</button>
    </div>
    <a href="<?= Url::to('') ?>" class="<?= $navCurrentRel === '' ? 'active' : '' ?>"><span class="nav-icon">🏠</span>الرئيسية</a>
    <?php foreach ($siteMenu as $navItem): if (($navItem['header'] ?? true) === false) continue; ?>
      <a href="<?= \Core\View::e($navItem['url']) ?>" class="<?= $navIsActive($navItem['url']) ? 'active' : '' ?>"><span class="nav-icon"><?= $navItem['icon'] ?></span><?= \Core\View::e($navItem['label']) ?></a>
    <?php endforeach; ?>
  </div>
</div>

<main>
<?= $content ?>
</main>

<?php
// شريط تنقل سفلي بأسلوب التطبيقات (للجوال)
$basePath = rtrim((string) parse_url(Url::to(''), PHP_URL_PATH), '/');
$currentPath = rtrim((string) parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH), '/');
$currentRel = trim(substr($currentPath, strlen($basePath)), '/');
$bottomNav = [
    ['match' => '', 'url' => Url::to(''), 'icon' => '🏠', 'label' => 'الرئيسية', 'show' => true],
    ['match' => 'news', 'url' => Url::to('news'), 'icon' => '📰', 'label' => 'الأخبار', 'show' => \Core\ModuleManager::isEnabled('news')],
    ['match' => 'calendar', 'url' => Url::to('calendar'), 'icon' => '📅', 'label' => 'الرزنامة', 'show' => \Core\ModuleManager::isEnabled('calendar')],
    ['match' => 'gatherings', 'url' => Url::to('gatherings'), 'icon' => '☕', 'label' => 'الجمعات', 'show' => \Core\ModuleManager::isEnabled('gatherings')],
];
?>
<div class="a2hs-bar" data-a2hs hidden>
  <span class="a2hs-text">📲 أضف الموقع لشاشتك الرئيسية وفعّل التنبيهات</span>
  <span class="a2hs-actions">
    <button class="btn btn-sm btn-primary" data-a2hs-install data-install-fallback="<?= Url::to('install-app') ?>" hidden>تثبيت</button>
    <a class="btn btn-sm btn-outline-dark" href="<?= Url::to('install-app') ?>">الطريقة</a>
    <button class="a2hs-close" data-a2hs-close aria-label="إغلاق">✕</button>
  </span>
</div>

<nav class="bottom-nav" aria-label="التنقل السريع">
  <?php foreach ($bottomNav as $item): if (!$item['show']) continue;
      $isActive = $item['match'] === '' ? $currentRel === '' : str_starts_with($currentRel . '/', $item['match'] . '/');
  ?>
  <a href="<?= \Core\View::e($item['url']) ?>" class="<?= $isActive ? 'active' : '' ?>">
    <span class="bn-icon"><?= $item['icon'] ?></span>
    <span><?= \Core\View::e($item['label']) ?></span>
  </a>
  <?php endforeach; ?>
  <button type="button" data-nav-trigger aria-label="المزيد">
    <span class="bn-icon">⊕</span>
    <span>المزيد</span>
  </button>
</nav>

<footer class="site-footer">
  <div class="container">
    <div class="footer-brand"><?= \Core\View::e($shortName) ?></div>
    <?php if (Settings::get('identity_brief')): ?><p class="footer-brief"><?= \Core\View::e(Settings::get('identity_brief', '')) ?></p><?php endif; ?>

    <nav class="footer-links" aria-label="روابط التذييل">
      <?php if ($footerMenu): ?>
        <?php foreach ($footerMenu as $navItem): ?>
          <a href="<?= \Core\View::e(fam_menu_link($navItem)) ?>"><?= \Core\View::e($navItem['label']) ?></a>
        <?php endforeach; ?>
      <?php else: ?>
        <a href="<?= Url::to('') ?>">الرئيسية</a>
        <?php foreach ($siteMenu as $navItem): ?>
          <a href="<?= \Core\View::e($navItem['url']) ?>"><?= \Core\View::e($navItem['label']) ?></a>
        <?php endforeach; ?>
      <?php endif; ?>
    </nav>

    <div class="footer-services">
      <a href="<?= Url::to('install-app') ?>">📲 أضف الموقع كتطبيق</a>
      <?php if (\Core\ModuleManager::isEnabled('directory')): ?>
        <a href="<?= Url::to('directory/manage-subscription') ?>">🗑 حذف رقم قديم</a>
      <?php endif; ?>
      <a href="<?= Url::to('contact') ?>">✉️ راسل الإدارة</a>
      <a href="<?= Url::to('family') ?>">ℹ️ عن البرنامج</a>
    </div>

    <?php
    $socials = [
        'social_whatsapp' => '💬', 'social_x' => '𝕏', 'social_instagram' => '📷',
        'social_snapchat' => '👻', 'social_youtube' => '▶', 'social_telegram' => '✈',
    ];
    $activeSocials = array_filter(array_map(fn($k) => Settings::get($k, ''), array_keys($socials)));
    ?>
    <?php if ($activeSocials): ?>
    <div class="social-row">
      <?php foreach ($socials as $key => $icon): $val = Settings::get($key, ''); if ($val): ?>
        <a href="<?= \Core\View::e($val) ?>" target="_blank" rel="noopener"><?= $icon ?></a>
      <?php endif; endforeach; ?>
    </div>
    <?php endif; ?>

    <?php if (Settings::get('contact_phone') || Settings::get('contact_email')): ?>
    <div class="footer-contact">
      <?php if (Settings::get('contact_phone')): ?><span dir="ltr">📞 <?= \Core\View::e(Settings::get('contact_phone')) ?></span><?php endif; ?>
      <?php if (Settings::get('contact_email')): ?><span dir="ltr">✉️ <?= \Core\View::e(Settings::get('contact_email')) ?></span><?php endif; ?>
    </div>
    <?php endif; ?>
    <div class="copyright">© <?= date('Y') ?> <?= \Core\View::e($shortName) ?> — جميع الحقوق محفوظة — برنامج العائلة — تطوير <a href="https://almgrat.com" target="_blank" rel="noopener">المجرات</a> — الإصدار <?= CORE_VERSION ?></div>
  </div>
</footer>

<?php if ($popupAnnouncement): ?>
<div class="announce-popup-overlay <?= fam_announce_visibility_class($popupAnnouncement) ?>" data-announce-popup
     data-id="<?= (int) $popupAnnouncement['id'] ?>"
     data-frequency="<?= \Core\View::e($popupAnnouncement['popup_frequency'] ?? 'once') ?>"
     data-interval-days="<?= (int) ($popupAnnouncement['popup_interval_days'] ?? 0) ?>">
  <div class="announce-popup-box">
    <button type="button" class="close-btn" data-announce-close aria-label="إغلاق">✕</button>
    <h3><?= \Core\View::e($popupAnnouncement['title']) ?></h3>
    <p><?= \Core\View::e($popupAnnouncement['message']) ?></p>
  </div>
</div>
<?php endif; ?>

<script src="<?= Url::theme('assets/js/site.js') ?>?v=<?= CORE_VERSION ?>"></script>
<script nonce="<?= \Core\Support\Security::cspNonce() ?>">
if ('serviceWorker' in navigator) {
  window.addEventListener('load', function () {
    navigator.serviceWorker.register('<?= Url::to('sw.js') ?>').catch(function () {});
  });
}
</script>
</body>
</html>
