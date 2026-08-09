<?php
/** @var array $item الجمعة مع اسم المدينة ومسار الصورة */
use Core\Media;
use Core\Support\Url;
use Core\View;
use Modules\Gatherings\Support\RecurrenceLabel;

$periodLabel = RecurrenceLabel::periodLabel($item['time_period'] ?? null);

$placeLabel = '';
if (!empty($item['venue'])) {
    $placeLabel = $item['venue'] . (!empty($item['city_name']) ? ' — ' . $item['city_name'] : '');
} elseif (!empty($item['city_name'])) {
    $placeLabel = $item['city_name'];
}

$statusChips = ['paused' => 'متوقفة مؤقتًا ⏸', 'ended' => 'منتهية'];

// نص المشاركة: الاسم والموعد والمكان ثم رابط الصفحة
$shareLines = ['☕ جمعة: ' . $item['title']];
if (!empty($item['recurrence_label'])) {
    $shareLines[] = '🗓 ' . $item['recurrence_label'];
}
if ($placeLabel !== '') {
    $shareLines[] = '📍 ' . $placeLabel;
}
if (!empty($item['map_url'])) {
    $shareLines[] = '🗺 ' . $item['map_url'];
}
$shareText = implode("\n", $shareLines);
$shareTextWithUrl = $shareText . "\n\nللتفاصيل:\n" . Url::current();
?>
<div class="container section">
  <div class="breadcrumbs"><a href="<?= Url::to('') ?>">الرئيسية</a> / <a href="<?= Url::to('gatherings') ?>">الجمعات</a> / <?= View::e($item['title']) ?></div>

  <article class="event-page<?= !empty($item['cover_path']) ? ' has-poster' : '' ?>">
    <?php if (!empty($item['cover_path'])): ?>
      <div class="event-poster"><img src="<?= View::e(Media::url($item['cover_path'])) ?>" alt="<?= View::e($item['title']) ?>"></div>
    <?php endif; ?>

    <div class="event-body">
      <header class="event-head">
        <div class="event-chips">
          <span class="event-type">☕ جمعة</span>
          <?php if (isset($statusChips[$item['status']])): ?>
            <span class="event-state is-past"><?= $statusChips[$item['status']] ?></span>
          <?php endif; ?>
        </div>
        <h1><?= View::e($item['title']) ?></h1>
      </header>

      <div class="info-rows">
        <?php if (!empty($item['recurrence_label'])): ?>
          <div class="info-row"><span class="info-label">🗓 الموعد</span><span><?= View::e($item['recurrence_label']) ?></span></div>
        <?php endif; ?>
        <?php if ($periodLabel !== '' && !str_contains((string) $item['recurrence_label'], $periodLabel)): ?>
          <div class="info-row"><span class="info-label">🕗 الوقت</span><span><?= View::e($periodLabel) ?></span></div>
        <?php endif; ?>
        <?php if (!empty($item['venue'])): ?>
          <div class="info-row"><span class="info-label">📍 المكان</span><span><?= View::e($item['venue']) ?><?= !empty($item['city_name']) ? ' — ' . View::e($item['city_name']) : '' ?></span></div>
        <?php elseif (!empty($item['city_name'])): ?>
          <div class="info-row"><span class="info-label">🏙 المدينة</span><span><?= View::e($item['city_name']) ?></span></div>
        <?php endif; ?>
        <?php if (!empty($item['map_url'])): ?>
          <div class="info-row"><span class="info-label">🗺 الوصول</span><span><a class="info-link" href="<?= View::e($item['map_url']) ?>" target="_blank" rel="noopener">فتح الموقع على خرائط جوجل ←</a></span></div>
        <?php endif; ?>
        <?php if (!empty($item['contact_name'])): ?>
          <div class="info-row"><span class="info-label">👤 المسؤول</span><span><?= View::e($item['contact_name']) ?></span></div>
        <?php endif; ?>
      </div>

      <?php if (!empty($item['description'])): ?>
        <div class="event-notes">
          <h2 class="event-notes-title">عن الجمعة</h2>
          <div class="event-notes-body"><?= nl2br(View::e($item['description'])) ?></div>
        </div>
      <?php endif; ?>
    </div>
  </article>

  <div class="share-row">
    <span class="share-label">شارك الجمعة:</span>
    <a class="btn btn-sm btn-outline-dark" target="_blank" rel="noopener" href="https://wa.me/?text=<?= urlencode($shareTextWithUrl) ?>">واتساب</a>
    <a class="btn btn-sm btn-outline-dark" target="_blank" rel="noopener" href="https://twitter.com/intent/tweet?text=<?= urlencode($shareText) ?>&url=<?= urlencode(Url::current()) ?>">X</a>
    <a class="btn btn-sm btn-outline-dark" target="_blank" rel="noopener" href="https://t.me/share/url?url=<?= urlencode(Url::current()) ?>&text=<?= urlencode($shareText) ?>">تيليجرام</a>
    <button class="btn btn-sm btn-outline-dark" data-copy-link data-url="<?= View::e(Url::current()) ?>">نسخ الرابط</button>
    <button class="btn btn-sm btn-primary" data-share-native data-url="<?= View::e(Url::current()) ?>" data-title="<?= View::e($item['title']) ?>" data-text="<?= View::e($shareText) ?>">مشاركة 📤</button>
  </div>
</div>
