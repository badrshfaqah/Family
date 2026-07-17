<?php
/** @var array $news
 *  @var array|null $nextEvent
 *  @var array $comingSoon
 *  @var array $gatherings
 *  @var array $announcements
 *  @var array $gallery
 *  @var array $stats
 *  @var array $sectionsOrder
 *  @var array $sectionsVisible
 */
use Core\Media;
use Core\Settings;
use Core\Support\Url;
use Core\Terms;

$visible = fn($key) => ($sectionsVisible[$key] ?? true);

function fam_media_url($mediaId)
{
    if (!$mediaId) return '';
    $row = \Core\Database::fetchOne('SELECT stored_path FROM ' . \Core\Database::table('media') . ' WHERE id = ?', [$mediaId]);
    return $row ? Media::url($row['stored_path']) : '';
}

$heroCover = fam_media_url(Settings::get('identity_cover_media_id', ''));
?>

<?php foreach ($sectionsOrder as $section): if (!$visible($section)) continue; ?>

  <?php if ($section === 'hero'): ?>
  <section class="hero" style="<?= $heroCover ? '' : 'background:linear-gradient(135deg,var(--c-primary),var(--c-primary-dark))' ?>">
    <?php if ($heroCover): ?><img src="<?= \Core\View::e($heroCover) ?>" alt=""><?php endif; ?>
    <div class="hero-content container">
      <h1><?= \Core\View::e(Terms::phrase('welcome')) ?></h1>
      <?php if (Settings::get('identity_brief')): ?><p><?= \Core\View::e(Settings::get('identity_brief')) ?></p><?php endif; ?>
      <div class="hero-actions">
        <a href="<?= Url::to('news') ?>" class="btn btn-primary">آخر الأخبار</a>
        <a href="<?= Url::to('calendar') ?>" class="btn btn-outline">المناسبات القادمة</a>
      </div>
    </div>
  </section>
  <?php endif; ?>

  <?php if ($section === 'ticker' && Settings::get('ticker_enabled', '1') === '1' && !empty($news)): ?>
  <div class="ticker">
    <div class="ticker-track">
      <?php foreach (array_slice($news, 0, 6) as $n): ?>
        <span>📰 <?= \Core\View::e($n['title']) ?></span>
      <?php endforeach; ?>
    </div>
  </div>
  <?php endif; ?>

  <?php if ($section === 'next_event' && $nextEvent): ?>
  <section class="section container">
    <div class="next-event">
      <div class="next-event-body">
        <span class="badge"><?= \Core\View::e($nextEvent['entry_type'] ?? 'مناسبة') ?><?php if (!empty($nextEvent['city_name'])): ?> · <?= \Core\View::e($nextEvent['city_name']) ?><?php endif; ?></span>
        <h2 style="margin:0 0 6px"><?= \Core\View::e($nextEvent['title']) ?></h2>
        <div data-countdown="<?= \Core\View::e(date('c', strtotime($nextEvent['entry_datetime']))) ?>">
          <div style="font-weight:700;margin-bottom:6px" data-countdown-label></div>
          <div class="countdown">
            <div class="cell"><b data-c-days>00</b><span>يوم</span></div>
            <div class="cell"><b data-c-hours>00</b><span>ساعة</span></div>
            <div class="cell"><b data-c-mins>00</b><span>دقيقة</span></div>
            <div class="cell"><b data-c-secs>00</b><span>ثانية</span></div>
          </div>
        </div>
        <a href="<?= Url::to('calendar/' . $nextEvent['id']) ?>" class="btn btn-outline">عرض التفاصيل</a>
      </div>
    </div>
  </section>
  <?php endif; ?>

  <?php if ($section === 'coming_soon' && !empty($comingSoon)): ?>
  <section class="section container">
    <div class="section-head"><h2>قريبًا</h2><a href="<?= Url::to('calendar') ?>">عرض الكل</a></div>
    <div class="grid grid-3">
      <?php foreach ($comingSoon as $ev): ?>
      <a class="card" href="<?= Url::to('calendar/' . $ev['id']) ?>">
        <div class="card-body">
          <div class="card-meta"><span><?= \Core\View::e(date('Y/m/d', strtotime($ev['entry_datetime']))) ?></span><?php if (!empty($ev['city_name'])): ?><span><?= \Core\View::e($ev['city_name']) ?></span><?php endif; ?></div>
          <p class="card-title"><?= \Core\View::e($ev['title']) ?></p>
        </div>
      </a>
      <?php endforeach; ?>
    </div>
  </section>
  <?php endif; ?>

  <?php if ($section === 'news' && !empty($news)): ?>
  <section class="section container">
    <div class="section-head"><h2><?= \Core\View::e(Terms::phrase('news')) ?></h2><a href="<?= Url::to('news') ?>">عرض الكل</a></div>
    <div class="grid grid-3">
      <?php foreach ($news as $item): ?>
      <a class="card" href="<?= Url::to('news/' . $item['slug']) ?>">
        <div class="card-img"><img loading="lazy" src="<?= \Core\View::e(fam_media_url($item['cover_media_id']) ?: Url::theme('assets/img/placeholder.svg')) ?>" alt=""></div>
        <div class="card-body">
          <p class="card-title"><?= \Core\View::e($item['title']) ?></p>
          <div class="card-meta"><span><?= \Core\View::e(date('Y/m/d', strtotime($item['published_at']))) ?></span></div>
        </div>
      </a>
      <?php endforeach; ?>
    </div>
  </section>
  <?php endif; ?>

  <?php if ($section === 'gatherings' && !empty($gatherings)): ?>
  <section class="section container">
    <div class="section-head"><h2>الجمعات</h2><a href="<?= Url::to('gatherings') ?>">عرض الكل</a></div>
    <div class="grid grid-3">
      <?php foreach ($gatherings as $g): ?>
      <div class="card">
        <div class="card-body">
          <p class="card-title"><?= \Core\View::e($g['title']) ?></p>
          <div class="card-meta"><span><?= \Core\View::e($g['recurrence_label'] ?? '') ?></span><?php if (!empty($g['city_name'])): ?><span><?= \Core\View::e($g['city_name']) ?></span><?php endif; ?></div>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
  </section>
  <?php endif; ?>

  <?php if ($section === 'announcements' && !empty($announcements)): ?>
  <section class="section container">
    <div class="section-head"><h2>إعلانات</h2></div>
    <div class="grid">
      <?php foreach ($announcements as $a): ?>
      <div class="card"><div class="card-body"><p class="card-title"><?= \Core\View::e($a['title']) ?></p><p><?= \Core\View::e($a['message'] ?? '') ?></p></div></div>
      <?php endforeach; ?>
    </div>
  </section>
  <?php endif; ?>

  <?php if ($section === 'gallery' && !empty($gallery)): ?>
  <section class="section container">
    <div class="section-head"><h2>من المعرض</h2><a href="<?= Url::to('gallery') ?>">عرض الكل</a></div>
    <div class="grid grid-3">
      <?php foreach ($gallery as $photo): ?>
        <div class="card-img" style="border-radius:var(--radius)"><img loading="lazy" src="<?= \Core\View::e(Media::thumbUrl($photo['thumb_path'], $photo['stored_path'])) ?>" alt=""></div>
      <?php endforeach; ?>
    </div>
  </section>
  <?php endif; ?>

  <?php if ($section === 'quick_links'): ?>
  <section class="section container">
    <div class="quick-links">
      <a class="quick-link" href="<?= Url::to('directory/register') ?>"><?= \Core\View::e(Terms::phrase('family_directory_register')) ?></a>
      <a class="quick-link" href="<?= Url::to('calendar') ?>">المناسبات القادمة</a>
      <a class="quick-link" href="<?= Url::to('tree') ?>"><?= \Core\View::e(Terms::phrase('tree')) ?></a>
      <a class="quick-link" href="<?= Url::to('archive') ?>"><?= \Core\View::e(Terms::phrase('archive')) ?></a>
    </div>
  </section>
  <?php endif; ?>

  <?php if ($section === 'stats' && !empty($stats)): ?>
  <section class="section container">
    <div class="stats-grid">
      <?php $labels = ['news' => 'خبر منشور', 'events' => 'مناسبة', 'gatherings' => 'جمعة نشطة', 'gallery' => 'صورة', 'archive' => 'وثيقة', 'directory_count' => 'رقم مسجّل']; ?>
      <?php foreach ($stats as $key => $value): ?>
        <div class="stat-card"><b><?= (int) $value ?></b><span><?= \Core\View::e($labels[$key] ?? $key) ?></span></div>
      <?php endforeach; ?>
    </div>
  </section>
  <?php endif; ?>

<?php endforeach; ?>
