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

<?php /* قائمة استكشاف بأيقونات أسفل الشعار مباشرة — اختصارات بأسلوب التطبيقات */ ?>
<div class="explore-bar">
  <div class="container explore-scroll">
    <a class="explore-item" href="<?= Url::to('directory/register') ?>"><span class="ex-icon">📱</span>جوال القبيلة</a>
    <a class="explore-item" href="<?= Url::to('calendar') ?>"><span class="ex-icon">📅</span>الرزنامة</a>
    <a class="explore-item" href="<?= Url::to('gatherings') ?>"><span class="ex-icon">☕</span>الجمعات</a>
    <a class="explore-item" href="<?= Url::to('tree') ?>"><span class="ex-icon">🌳</span>شجرة النسب</a>
    <a class="explore-item" href="<?= Url::to('gallery') ?>"><span class="ex-icon">🖼</span>مكتبة الصور</a>
    <a class="explore-item" href="<?= Url::to('archive') ?>"><span class="ex-icon">📜</span>الأرشيف</a>
    <a class="explore-item" href="<?= Url::to('news') ?>"><span class="ex-icon">📰</span>الأخبار</a>
  </div>
</div>

<?php foreach ($sectionsOrder as $section): if (!$visible($section)) continue; ?>

  <?php if ($section === 'hero'): ?>
  <section class="hero-slider" data-slider>
    <div class="slides" data-slider-track>
      <div class="slide" style="<?= $heroCover ? '' : 'background:linear-gradient(135deg,var(--c-primary),var(--c-primary-dark))' ?>">
        <?php if ($heroCover): ?><img src="<?= \Core\View::e($heroCover) ?>" alt=""><?php endif; ?>
        <div class="slide-content container">
          <h1><?= \Core\View::e(Terms::phrase('welcome')) ?></h1>
          <?php if (Settings::get('identity_brief')): ?><p><?= \Core\View::e(Settings::get('identity_brief')) ?></p><?php endif; ?>
          <div class="hero-actions">
            <a href="<?= Url::to('directory/register') ?>" class="btn btn-primary">📱 <?= \Core\View::e(Terms::phrase('family_directory_register')) ?></a>
            <a href="<?= Url::to('gatherings') ?>" class="btn btn-outline">الجمعات</a>
          </div>
        </div>
      </div>
      <?php foreach (($heroSlides ?? []) as $slide): ?>
      <div class="slide">
        <img src="<?= \Core\View::e(Media::url($slide['image'])) ?>" alt="" loading="lazy">
        <div class="slide-content container">
          <span class="badge slide-badge"><?= \Core\View::e($slide['badge']) ?></span>
          <h2><?= \Core\View::e($slide['title']) ?></h2>
          <?php if (!empty($slide['text'])): ?><p><?= \Core\View::e($slide['text']) ?></p><?php endif; ?>
          <div class="hero-actions">
            <a href="<?= Url::to($slide['url']) ?>" class="btn btn-primary">عرض التفاصيل</a>
          </div>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
    <div class="slider-dots" data-slider-dots aria-label="التنقل بين الشرائح"></div>
  </section>

  <section class="join-band">
    <div class="container">
      <div class="join-band-inner">
        <div class="join-band-text">
          <b>📱 <?= \Core\View::e(Terms::phrase('family_directory_register')) ?></b>
          <span>خلّك قريب من أهلك.. سجّل رقمك ويصلك كل جديد: مناسباتنا وجمعاتنا وأخبارنا أولًا بأول</span>
        </div>
        <a href="<?= Url::to('directory/register') ?>" class="btn btn-primary join-band-btn">سجّل الآن ✨</a>
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

  <?php if ($section === 'next_event' && (!empty($comingSoon) || !empty($gatherings))): ?>
  <?php
    // الوسط: رزنامة المناسبات (يمين) والجمعات (يسار) جنبًا إلى جنب
    $arMonths = [1 => 'يناير', 'فبراير', 'مارس', 'أبريل', 'مايو', 'يونيو', 'يوليو', 'أغسطس', 'سبتمبر', 'أكتوبر', 'نوفمبر', 'ديسمبر'];
    $arDays = ['الأحد', 'الاثنين', 'الثلاثاء', 'الأربعاء', 'الخميس', 'الجمعة', 'السبت'];
    $todayTs = strtotime(date('Y-m-d'));
  ?>
  <section class="section container">
    <div class="home-cols">

      <?php if (!empty($comingSoon)): ?>
      <div class="home-col">
        <div class="section-head"><h2>📅 رزنامة المناسبات</h2><a href="<?= Url::to('calendar') ?>">الرزنامة كاملة</a></div>
        <div class="cal-list">
          <?php foreach ($comingSoon as $i => $ev):
            $ts = strtotime($ev['entry_datetime']);
            $daysLeft = (int) floor((strtotime(date('Y-m-d', $ts)) - $todayTs) / 86400);
            if ($daysLeft <= 0) { $leftLabel = 'اليوم'; }
            elseif ($daysLeft === 1) { $leftLabel = 'غدًا'; }
            elseif ($daysLeft === 2) { $leftLabel = 'باقي يومين'; }
            elseif ($daysLeft <= 10) { $leftLabel = 'باقي ' . $daysLeft . ' أيام'; }
            else { $leftLabel = 'باقي ' . $daysLeft . ' يوم'; }
            $time = date('g:i', $ts) . ' ' . (date('a', $ts) === 'pm' ? 'م' : 'ص');
          ?>
          <a class="cal-item<?= $i === 0 ? ' cal-next' : '' ?>" href="<?= Url::to('calendar/' . $ev['id']) ?>">
            <div class="cal-date">
              <span><?= $arDays[(int) date('w', $ts)] ?></span>
              <b><?= (int) date('j', $ts) ?></b>
              <span><?= $arMonths[(int) date('n', $ts)] ?></span>
            </div>
            <div class="cal-info">
              <p class="cal-title"><?= \Core\View::e($ev['title']) ?></p>
              <span class="cal-meta">
                <span>🕗 <?= $time ?></span>
                <?php if (!empty($ev['venue_name'])): ?><span>📍 <?= \Core\View::e($ev['venue_name']) ?><?= !empty($ev['city_name']) ? ' — ' . \Core\View::e($ev['city_name']) : '' ?></span><?php endif; ?>
              </span>
            </div>
            <span class="cal-left"><?= $leftLabel ?></span>
          </a>
          <?php endforeach; ?>
        </div>
      </div>
      <?php endif; ?>

      <?php if (!empty($gatherings)): ?>
      <div class="home-col">
        <div class="section-head"><h2>☕ الجمعات</h2><a href="<?= Url::to('gatherings') ?>">عرض الكل</a></div>
        <div class="gath-list">
          <?php foreach ($gatherings as $g): ?>
          <div class="gath-card">
            <div class="gath-icon">☕</div>
            <div class="gath-body">
              <p class="gath-title"><?= \Core\View::e($g['title']) ?></p>
              <?php if (!empty($g['recurrence_label'])): ?><span class="gath-chip">🗓 <?= \Core\View::e($g['recurrence_label']) ?></span><?php endif; ?>
              <?php if (!empty($g['venue'])): ?><span class="gath-meta">📍 <?= \Core\View::e($g['venue']) ?><?= !empty($g['city_name']) ? ' — ' . \Core\View::e($g['city_name']) : '' ?></span><?php endif; ?>
            </div>
          </div>
          <?php endforeach; ?>
        </div>
      </div>
      <?php endif; ?>

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
