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

  <?php
    $wdNames = ['الأحد', 'الاثنين', 'الثلاثاء', 'الأربعاء', 'الخميس', 'الجمعة', 'السبت'];
    $moNames = [1 => 'يناير', 'فبراير', 'مارس', 'أبريل', 'مايو', 'يونيو', 'يوليو', 'أغسطس', 'سبتمبر', 'أكتوبر', 'نوفمبر', 'ديسمبر'];
  ?>
  <section class="welcome-band">
    <div class="container">
      <h1><?= \Core\View::e(Terms::phrase('welcome')) ?></h1>
      <p class="welcome-sub">
        <?= $wdNames[(int) date('w')] ?> <?= (int) date('j') ?> <?= $moNames[(int) date('n')] ?> <?= date('Y') ?>
        <?php if (Settings::get('identity_brief')): ?> · <?= \Core\View::e(Settings::get('identity_brief')) ?><?php endif; ?>
      </p>
    </div>
  </section>

  <?php
    // إعلانات الوفيات النشطة: بطاقات صغيرة أعلى بطاقة التسجيل
    $homeObituaries = [];
    if (\Core\ModuleManager::isEnabled('obituaries') && \Core\Database::tableExists('obituaries')) {
        $homeObituaries = \Core\Database::fetchAll(
            'SELECT o.id, o.name, o.condolence_venue, c.name AS city_name
             FROM ' . \Core\Database::table('obituaries') . ' o
             LEFT JOIN ' . \Core\Database::table('cities') . " c ON c.id = o.city_id
             WHERE o.status = 'active' ORDER BY o.id DESC LIMIT 3"
        );
    }
  ?>
  <?php if (!empty($homeObituaries)): ?>
  <section class="obit-band">
    <div class="container">
      <div class="obit-list">
        <?php foreach ($homeObituaries as $ob): ?>
        <a class="obit-card" href="<?= Url::pretty('obituaries', (int) $ob['id'], 'وفاة ' . $ob['name']) ?>">
          <span class="obit-icon">🕊</span>
          <span class="obit-body">
            <b>وفاة <?= \Core\View::e($ob['name']) ?></b>
            <?php if (!empty($ob['condolence_venue'])): ?><span class="obit-meta"><span>📍 العزاء: <?= \Core\View::e($ob['condolence_venue']) ?></span></span><?php endif; ?>
          </span>
          <?php if (!empty($ob['city_name'])): ?><span class="obit-city"><?= \Core\View::e($ob['city_name']) ?></span><?php endif; ?>
        </a>
        <?php endforeach; ?>
      </div>
    </div>
  </section>
  <?php endif; ?>

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
          ?>
          <a class="cal-item<?= $i === 0 ? ' cal-next' : '' ?>" href="<?= Url::pretty('calendar', (int) $ev['id'], $ev['title']) ?>">
            <div class="cal-date">
              <span><?= $arDays[(int) date('w', $ts)] ?></span>
              <b><?= (int) date('j', $ts) ?></b>
              <span><?= $arMonths[(int) date('n', $ts)] ?></span>
            </div>
            <div class="cal-info">
              <p class="cal-title"><?= \Core\View::e($ev['title']) ?></p>
              <span class="cal-meta">
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
              <p class="gath-title"><a class="gath-title-link" href="<?= Url::to('gatherings/' . $g['id']) ?>"><?= \Core\View::e($g['title']) ?></a></p>
              <?php if (!empty($g['recurrence_label'])): ?><span class="gath-chip">🗓 <?= \Core\View::e($g['recurrence_label']) ?></span><?php endif; ?>
              <?php if (!empty($g['venue'])): ?><span class="gath-meta">📍 <?= \Core\View::e($g['venue']) ?><?= !empty($g['city_name']) ? ' — ' . \Core\View::e($g['city_name']) : '' ?></span><?php endif; ?>
              <?php if (!empty($g['map_url'])): ?><a class="gath-map" href="<?= \Core\View::e($g['map_url']) ?>" target="_blank" rel="noopener">🗺 الموقع على الخريطة ←</a><?php endif; ?>
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
    <div class="section-head"><h2>📣 إعلانات</h2></div>
    <div class="grid">
      <?php foreach ($announcements as $a): ?>
      <div class="card ann-card">
        <div class="card-body">
          <p class="card-title"><?= \Core\View::e($a['title']) ?></p>
          <p class="ann-msg"><?= \Core\View::e($a['message'] ?? '') ?></p>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
  </section>
  <?php endif; ?>


<?php endforeach; ?>
