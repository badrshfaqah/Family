<?php
/** @var array $item
 *  @var array $coverageAlbums
 *  @var array|null $linkedEvent
 */
use Core\Media;
use Core\Support\Url;
use Core\View;

function fam_calendar_cover($mediaId)
{
    if (!$mediaId) return '';
    $row = \Core\Database::fetchOne('SELECT stored_path FROM ' . \Core\Database::table('media') . ' WHERE id = ?', [$mediaId]);
    return $row ? Media::url($row['stored_path']) : '';
}

$cover = fam_calendar_cover($item['cover_media_id'] ?? null);

$arDays = ['الأحد', 'الاثنين', 'الثلاثاء', 'الأربعاء', 'الخميس', 'الجمعة', 'السبت'];
$arMonths = [1 => 'يناير', 'فبراير', 'مارس', 'أبريل', 'مايو', 'يونيو', 'يوليو', 'أغسطس', 'سبتمبر', 'أكتوبر', 'نوفمبر', 'ديسمبر'];

$ts = strtotime($item['entry_datetime']);
$dateLabel = $arDays[(int) date('w', $ts)] . ' ' . (int) date('j', $ts) . ' ' . $arMonths[(int) date('n', $ts)] . ' ' . date('Y', $ts);
$timeLabel = date('g:i', $ts) . ' ' . (date('a', $ts) === 'pm' ? 'مساءً' : 'صباحًا');

$daysLeft = (int) floor((strtotime(date('Y-m-d', $ts)) - strtotime(date('Y-m-d'))) / 86400);
if ($daysLeft < 0) { $stateChip = ['label' => 'انتهت', 'past' => true]; }
elseif ($daysLeft === 0) { $stateChip = ['label' => 'اليوم 🎉', 'past' => false]; }
elseif ($daysLeft === 1) { $stateChip = ['label' => 'غدًا', 'past' => false]; }
elseif ($daysLeft === 2) { $stateChip = ['label' => 'باقي يومين', 'past' => false]; }
elseif ($daysLeft <= 10) { $stateChip = ['label' => 'باقي ' . $daysLeft . ' أيام', 'past' => false]; }
else { $stateChip = ['label' => 'باقي ' . $daysLeft . ' يوم', 'past' => false]; }
?>
<div class="container section">
  <div class="breadcrumbs"><a href="<?= Url::to('') ?>">الرئيسية</a> / <a href="<?= Url::to('calendar') ?>">رزنامة المناسبات</a> / <?= View::e($item['title']) ?></div>

  <article class="event-page">
    <?php if ($cover): ?><div class="event-cover"><img src="<?= View::e($cover) ?>" alt=""></div><?php endif; ?>

    <div class="event-body">
      <header class="event-head">
        <div class="event-chips">
          <span class="event-type">🏷 <?= View::e($item['entry_type']) ?></span>
          <span class="event-state <?= $stateChip['past'] ? 'is-past' : '' ?>"><?= $stateChip['label'] ?></span>
        </div>
        <h1><?= View::e($item['title']) ?></h1>
      </header>

      <div class="info-rows">
        <div class="info-row"><span class="info-label">🗓 اليوم والتاريخ</span><span><?= $dateLabel ?></span></div>
        <div class="info-row"><span class="info-label">🕗 الوقت</span><span><?= $timeLabel ?></span></div>
        <?php if (!empty($item['venue_name'])): ?>
          <div class="info-row"><span class="info-label">📍 المكان</span><span><?= View::e($item['venue_name']) ?><?= !empty($item['city_name']) ? ' — ' . View::e($item['city_name']) : '' ?></span></div>
        <?php elseif (!empty($item['city_name'])): ?>
          <div class="info-row"><span class="info-label">🏙 المدينة</span><span><?= View::e($item['city_name']) ?></span></div>
        <?php endif; ?>
        <?php if (!empty($item['maps_url'])): ?>
          <div class="info-row"><span class="info-label">🗺 الوصول</span><span><a class="info-link" href="<?= View::e($item['maps_url']) ?>" target="_blank" rel="noopener">فتح الموقع على خرائط جوجل ←</a></span></div>
        <?php endif; ?>
      </div>

      <?php if (!empty($item['notes'])): ?>
        <div class="event-notes">
          <h2 class="event-notes-title">تفاصيل المناسبة</h2>
          <div class="event-notes-body"><?= nl2br(View::e($item['notes'])) ?></div>
        </div>
      <?php endif; ?>

      <?php if ($linkedEvent): ?>
        <p style="margin:18px 0 0"><a class="btn btn-primary" href="<?= Url::to('events/' . $linkedEvent['slug']) ?>">عرض صفحة المناسبة الكاملة ↗</a></p>
      <?php endif; ?>
    </div>
  </article>

  <?php if (!empty($coverageAlbums)): ?>
  <section style="margin-top:26px">
    <div class="section-head"><h2>📸 تغطية المناسبة</h2><a href="<?= Url::to('gallery') ?>">مكتبة الصور</a></div>
    <div class="grid grid-3">
      <?php foreach ($coverageAlbums as $alb): ?>
      <a class="card" href="<?= Url::to('gallery/' . $alb['slug']) ?>">
        <?php if (!empty($alb['cover_path'])): ?>
          <div class="card-img"><img loading="lazy" src="<?= View::e(Media::url($alb['cover_path'])) ?>" alt=""></div>
        <?php endif; ?>
        <div class="card-body">
          <p class="card-title"><?= View::e($alb['title']) ?></p>
          <div class="card-meta">
            <span>🖼 <?= (int) $alb['photos_count'] ?> صورة</span>
            <?php if (!empty($alb['video_url'])): ?><span>🎬 فيديو</span><?php endif; ?>
          </div>
        </div>
      </a>
      <?php endforeach; ?>
    </div>
  </section>
  <?php endif; ?>

  <div class="share-row">
    <span class="share-label">شارك المناسبة:</span>
    <a class="btn btn-sm btn-outline-dark" target="_blank" rel="noopener" href="https://wa.me/?text=<?= urlencode($item['title'] . ' ' . Url::current()) ?>">واتساب</a>
    <a class="btn btn-sm btn-outline-dark" target="_blank" rel="noopener" href="https://twitter.com/intent/tweet?text=<?= urlencode($item['title']) ?>&url=<?= urlencode(Url::current()) ?>">X</a>
    <a class="btn btn-sm btn-outline-dark" target="_blank" rel="noopener" href="https://t.me/share/url?url=<?= urlencode(Url::current()) ?>&text=<?= urlencode($item['title']) ?>">تيليجرام</a>
    <button class="btn btn-sm btn-outline-dark" data-copy-link data-url="<?= View::e(Url::current()) ?>">نسخ الرابط</button>
    <button class="btn btn-sm btn-primary" data-share-native data-url="<?= View::e(Url::current()) ?>" data-title="<?= View::e($item['title']) ?>">مشاركة 📤</button>
  </div>
</div>
