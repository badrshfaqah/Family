<?php
/** الرئيسية بأسلوب تطبيق الحناتيش النيتف: شعار، عاجل الوفيات، أقرب مناسبة،
 *  آخر الأخبار (تمرير أفقي)، مناسبات قادمة، الجمعات، ثم روابط سريعة.
 *  @var array $news
 *  @var array|null $nextEvent
 *  @var array $comingSoon
 *  @var array $gatherings
 *  @var array $gatheringCities
 *  @var array $announcements
 *  @var array $sectionsVisible
 */
use Core\Media;
use Core\Settings;
use Core\Support\Url;
use Core\View;

$visible = fn($key) => ($sectionsVisible[$key] ?? true);

function fam_media_url($mediaId)
{
    if (!$mediaId) return '';
    $row = \Core\Database::fetchOne('SELECT stored_path FROM ' . \Core\Database::table('media') . ' WHERE id = ?', [$mediaId]);
    return $row ? Media::url($row['stored_path']) : '';
}

$arDays = ['الأحد', 'الاثنين', 'الثلاثاء', 'الأربعاء', 'الخميس', 'الجمعة', 'السبت'];
$arMonths = [1 => 'يناير', 'فبراير', 'مارس', 'أبريل', 'مايو', 'يونيو', 'يوليو', 'أغسطس', 'سبتمبر', 'أكتوبر', 'نوفمبر', 'ديسمبر'];
$todayTs = strtotime(date('Y-m-d'));

$countdown = function (int $ts) use ($todayTs): string {
    $days = (int) floor((strtotime(date('Y-m-d', $ts)) - $todayTs) / 86400);
    if ($days <= 0) return 'اليوم';
    if ($days === 1) return 'غدًا';
    if ($days === 2) return 'باقي يومان';
    if ($days <= 10) return 'باقي ' . $days . ' أيام';
    return 'باقي ' . $days . ' يومًا';
};

$logoUrl = fam_media_url(Settings::get('identity_logo_media_id', ''));

// إعلانات الوفيات النشطة — بأسلوب «عاجل الوفيات» في التطبيق
$homeObituaries = [];
if (\Core\ModuleManager::isEnabled('obituaries') && \Core\Database::tableExists('obituaries')) {
    $homeObituaries = \Core\Database::fetchAll(
        'SELECT o.*, c.name AS city_name
         FROM ' . \Core\Database::table('obituaries') . ' o
         LEFT JOIN ' . \Core\Database::table('cities') . " c ON c.id = o.city_id
         WHERE o.status = 'active' ORDER BY o.id DESC LIMIT 3"
    );
}
?>
<div class="container app-home">

  <?php if ($visible('hero')): ?>
  <header class="app-logo-head">
    <?php if ($logoUrl): ?>
      <img src="<?= View::e($logoUrl) ?>" alt="<?= View::e(Settings::get('identity_short_name', '')) ?>">
    <?php else: ?>
      <h1 class="app-logo-name"><?= View::e(Settings::get('identity_short_name', '')) ?></h1>
    <?php endif; ?>
    <p class="app-today"><?= $arDays[(int) date('w')] ?> <?= (int) date('j') ?> <?= $arMonths[(int) date('n')] ?> <?= date('Y') ?></p>
  </header>
  <?php endif; ?>

  <?php if (!empty($homeObituaries)): ?>
  <section class="urgent-band">
    <div class="urgent-head">❗ عاجل — الوفيات</div>
    <?php foreach ($homeObituaries as $ob): ?>
    <a class="urgent-card" href="<?= Url::pretty('obituaries', (int) $ob['id'], 'وفاة ' . $ob['name']) ?>">
      <span class="urgent-verse">﴿ إِنَّا لِلَّهِ وَإِنَّا إِلَيْهِ رَاجِعُونَ ﴾</span>
      <b class="urgent-name"><?= View::e($ob['name']) ?></b>
      <span class="urgent-rows">
        <?php if (!empty($ob['city_name'])): ?><span>🏙 <?= View::e($ob['city_name']) ?></span><?php endif; ?>
        <?php if (!empty($ob['condolence_venue'])): ?><span>📍 العزاء: <?= View::e($ob['condolence_venue']) ?></span><?php endif; ?>
      </span>
    </a>
    <?php endforeach; ?>
  </section>
  <?php endif; ?>

  <?php if ($visible('next_event') && $nextEvent):
    $ts = strtotime($nextEvent['entry_datetime']); ?>
  <section class="app-section">
    <div class="app-section-head"><span class="ash-icon">📅</span><h2>أقرب مناسبة</h2></div>
    <a class="next-card" href="<?= Url::pretty('calendar', (int) $nextEvent['id'], $nextEvent['title']) ?>">
      <span class="next-top">
        <span class="chip chip-primary"><?= View::e($nextEvent['entry_type']) ?></span>
        <span class="countdown-pill"><?= $countdown($ts) ?></span>
      </span>
      <b class="next-title"><?= View::e($nextEvent['title']) ?></b>
      <span class="info-row-app">🗓 <?= $arDays[(int) date('w', $ts)] ?> <?= (int) date('j', $ts) ?> <?= $arMonths[(int) date('n', $ts)] ?> <?= date('Y', $ts) ?></span>
      <?php if (!empty($nextEvent['venue_name']) || !empty($nextEvent['city_name'])): ?>
        <span class="info-row-app">📍 <?= View::e(trim(($nextEvent['venue_name'] ?? '') . (!empty($nextEvent['venue_name']) && !empty($nextEvent['city_name']) ? ' — ' : '') . ($nextEvent['city_name'] ?? ''))) ?></span>
      <?php endif; ?>
    </a>
  </section>
  <?php endif; ?>

  <?php if ($visible('news') && !empty($news)): ?>
  <section class="app-section">
    <div class="app-section-head"><span class="ash-icon">📰</span><h2>آخر الأخبار</h2><a class="ash-more" href="<?= Url::to('news') ?>">الكل</a></div>
    <div class="hscroll">
      <?php foreach ($news as $n): $cover = fam_media_url($n['cover_media_id'] ?? null); ?>
      <a class="hnews-card" href="<?= Url::pretty('news', (int) $n['id'], $n['title']) ?>">
        <?php if ($cover): ?><span class="hnews-img"><img loading="lazy" src="<?= View::e($cover) ?>" alt=""></span><?php endif; ?>
        <b class="hnews-title"><?= View::e($n['title']) ?></b>
        <?php if (!empty($n['published_at'])): ?><span class="hnews-date"><?= (int) date('j', strtotime($n['published_at'])) ?> <?= $arMonths[(int) date('n', strtotime($n['published_at']))] ?></span><?php endif; ?>
      </a>
      <?php endforeach; ?>
    </div>
  </section>
  <?php endif; ?>

  <?php
    $upcoming = array_slice($comingSoon, $nextEvent ? 1 : 0, 3);
    if ($visible('coming_soon') && !empty($upcoming)): ?>
  <section class="app-section">
    <div class="app-section-head"><span class="ash-icon">🗓</span><h2>مناسبات قادمة</h2><a class="ash-more" href="<?= Url::to('calendar') ?>">الرزنامة كاملة</a></div>
    <div class="app-rows">
      <?php foreach ($upcoming as $ev): $ts = strtotime($ev['entry_datetime']); ?>
      <a class="entry-row" href="<?= Url::pretty('calendar', (int) $ev['id'], $ev['title']) ?>">
        <span class="date-sq"><b><?= (int) date('j', $ts) ?></b><span><?= $arMonths[(int) date('n', $ts)] ?></span></span>
        <span class="entry-info">
          <b><?= View::e($ev['title']) ?></b>
          <span class="entry-meta">
            <span class="chip chip-gold"><?= View::e($ev['entry_type']) ?></span>
            <?php if (!empty($ev['city_name'])): ?><span class="entry-city"><?= View::e($ev['city_name']) ?></span><?php endif; ?>
          </span>
          <span class="entry-countdown"><?= $countdown($ts) ?></span>
        </span>
      </a>
      <?php endforeach; ?>
    </div>
  </section>
  <?php endif; ?>

  <?php if ($visible('gatherings') && !empty($gatherings)): ?>
  <section class="app-section">
    <div class="app-section-head"><span class="ash-icon">☕</span><h2>الجمعات</h2><a class="ash-more" href="<?= Url::to('gatherings') ?>">الكل</a></div>
    <?php if (!empty($gatheringCities)): ?>
    <div class="gath-cities">
      <?php foreach ($gatheringCities as $gc): ?>
        <a class="gath-city-chip" href="<?= Url::to('gatherings') ?>?city=<?= (int) $gc['id'] ?>">📍 <?= View::e($gc['name']) ?> <b><?= (int) $gc['cnt'] ?></b></a>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>
    <div class="app-rows">
      <?php foreach ($gatherings as $g):
        $gPeriod = class_exists('\Modules\Gatherings\Support\RecurrenceLabel')
            ? \Modules\Gatherings\Support\RecurrenceLabel::periodLabel($g['time_period'] ?? null) : '';
        $gDays = (string) ($g['recurrence_label'] ?? '');
        if ($gPeriod !== '' && str_ends_with($gDays, '، ' . $gPeriod)) {
            $gDays = substr($gDays, 0, -strlen('، ' . $gPeriod));
        }
      ?>
      <a class="entry-row" href="<?= Url::to('gatherings/' . $g['id']) ?>">
        <span class="date-sq date-sq-icon">☕</span>
        <span class="entry-info">
          <b><?= View::e($g['title']) ?></b>
          <?php if ($gDays !== ''): ?><span class="entry-city">🗓 <?= View::e($gDays) ?><?= $gPeriod !== '' ? ' — ' . View::e($gPeriod) : '' ?></span><?php endif; ?>
          <?php if (!empty($g['venue']) || !empty($g['city_name'])): ?>
            <span class="entry-city">📍 <?= View::e(trim(($g['venue'] ?? '') . (!empty($g['venue']) && !empty($g['city_name']) ? ' — ' : '') . ($g['city_name'] ?? ''))) ?></span>
          <?php endif; ?>
        </span>
      </a>
      <?php endforeach; ?>
    </div>
  </section>
  <?php endif; ?>

  <?php if ($visible('announcements') && !empty($announcements)): ?>
  <section class="app-section">
    <div class="app-section-head"><span class="ash-icon">📣</span><h2>إعلانات</h2></div>
    <div class="app-rows">
      <?php foreach ($announcements as $a): ?>
      <div class="entry-row">
        <span class="entry-info">
          <b><?= View::e($a['title']) ?></b>
          <?php if (!empty($a['message'])): ?><span class="entry-city"><?= View::e($a['message']) ?></span><?php endif; ?>
        </span>
      </div>
      <?php endforeach; ?>
    </div>
  </section>
  <?php endif; ?>

  <?php if ($visible('quick_links')): ?>
  <section class="quick-links">
    <?php if (\Core\ModuleManager::isEnabled('poetry')): ?>
    <a class="quick-card" href="<?= Url::to('poetry') ?>">
      <span class="quick-icon quick-icon-primary">🪶</span>
      <b>الشعراء</b>
      <span>سماء الشعراء وقصائدهم</span>
    </a>
    <?php endif; ?>
    <?php if (\Core\ModuleManager::isEnabled('directory')): ?>
    <a class="quick-card" href="<?= Url::to('directory/register') ?>">
      <span class="quick-icon quick-icon-gold">📱</span>
      <b>جوال القبيلة</b>
      <span>سجّل رقمك ويصلك كل جديد</span>
    </a>
    <?php endif; ?>
  </section>
  <?php endif; ?>

</div>
