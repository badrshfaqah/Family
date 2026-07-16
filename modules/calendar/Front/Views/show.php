<?php
/** @var array $item
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
?>
<div class="container section">
  <div class="breadcrumbs"><a href="<?= Url::to('') ?>">الرئيسية</a> / <a href="<?= Url::to('calendar') ?>">رزنامة المناسبات</a> / <?= View::e($item['title']) ?></div>

  <article class="card" style="padding:0;overflow:hidden">
    <?php if ($cover): ?><div class="card-img" style="aspect-ratio:16/8"><img src="<?= View::e($cover) ?>" alt=""></div><?php endif; ?>
    <div style="padding:22px">
      <span class="card-meta"><?= View::e($item['entry_type']) ?></span>
      <h1 style="margin:8px 0"><?= View::e($item['title']) ?></h1>
      <div class="card-meta" style="margin-bottom:16px">
        <span>🗓 <?= View::e(date('Y/m/d H:i', strtotime($item['entry_datetime']))) ?></span>
        <?php if (!empty($item['city_name'])): ?><span>📍 <?= View::e($item['city_name']) ?></span><?php endif; ?>
        <?php if (!empty($item['venue_name'])): ?><span><?= View::e($item['venue_name']) ?></span><?php endif; ?>
      </div>

      <?php if (!empty($item['maps_url'])): ?>
        <p><a href="<?= View::e($item['maps_url']) ?>" target="_blank" rel="noopener">فتح الموقع على خرائط جوجل ↗</a></p>
      <?php endif; ?>

      <?php if (!empty($item['notes'])): ?>
        <div class="page-content"><?= View::e($item['notes']) ?></div>
      <?php endif; ?>

      <?php if ($linkedEvent): ?>
        <div style="margin-top:18px">
          <a class="btn btn-primary" href="<?= Url::to('events/' . $linkedEvent['slug']) ?>">عرض صفحة المناسبة الكاملة ↗</a>
        </div>
      <?php endif; ?>
    </div>
  </article>

  <div style="display:flex;gap:10px;margin-top:16px">
    <a class="btn btn-secondary" target="_blank" rel="noopener" href="https://wa.me/?text=<?= urlencode($item['title'] . ' ' . Url::current()) ?>">مشاركة عبر واتساب</a>
    <button class="btn btn-secondary" data-share-native data-url="<?= View::e(Url::current()) ?>" data-title="<?= View::e($item['title']) ?>">مشاركة</button>
    <button class="btn btn-secondary" data-copy-link data-url="<?= View::e(Url::current()) ?>">نسخ الرابط</button>
  </div>
</div>
