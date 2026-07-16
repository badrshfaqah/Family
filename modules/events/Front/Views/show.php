<?php
/** @var array $item
 *  @var array $related
 */
use Core\Media;
use Core\Support\Url;
use Core\Terms;
use Core\View;

function fam_event_cover($mediaId)
{
    if (!$mediaId) return '';
    $row = \Core\Database::fetchOne('SELECT stored_path FROM ' . \Core\Database::table('media') . ' WHERE id = ?', [$mediaId]);
    return $row ? Media::url($row['stored_path']) : '';
}

function fam_event_media_list($idsCsv)
{
    $ids = array_filter(array_map('intval', explode(',', (string) $idsCsv)));
    if (!$ids) return [];
    $placeholders = implode(',', array_fill(0, count($ids), '?'));
    return \Core\Database::fetchAll('SELECT * FROM ' . \Core\Database::table('media') . " WHERE id IN ($placeholders)", $ids);
}

$cover = fam_event_cover($item['cover_media_id']);
$tags = array_filter(array_map('trim', explode(',', $item['tags'] ?? '')));
$gallery = fam_event_media_list($item['gallery_media_ids'] ?? '');
$attachments = fam_event_media_list($item['attachment_media_ids'] ?? '');
$externalLinks = array_filter(array_map('trim', preg_split('/[\r\n,]+/', $item['external_links'] ?? '')));

$jsonLd = [
    '@context' => 'https://schema.org',
    '@type' => 'Event',
    'name' => $item['title'],
    'eventStatus' => 'https://schema.org/EventScheduled',
    'eventAttendanceMode' => 'https://schema.org/OfflineEventAttendanceMode',
];
if (!empty($item['starts_at'])) {
    $jsonLd['startDate'] = date('c', strtotime($item['starts_at']));
}
if (!empty($item['ends_at'])) {
    $jsonLd['endDate'] = date('c', strtotime($item['ends_at']));
}
if (!empty($item['venue_name']) || !empty($item['city_name'])) {
    $jsonLd['location'] = [
        '@type' => 'Place',
        'name' => $item['venue_name'] ?: ($item['city_name'] ?? ''),
        'address' => $item['city_name'] ?? '',
    ];
}
if ($cover) {
    $jsonLd['image'] = [Url::origin() . $cover];
}
if (!empty($item['excerpt'])) {
    $jsonLd['description'] = $item['excerpt'];
}
?>
<script type="application/ld+json"><?= str_replace('</', '<\/', json_encode($jsonLd, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)) ?></script>
<div class="container section">
  <div class="breadcrumbs"><a href="<?= Url::to('') ?>">الرئيسية</a> / <a href="<?= Url::to('events') ?>"><?= View::e(Terms::phrase('events')) ?></a> / <?= View::e($item['title']) ?></div>

  <article class="card" style="padding:0;overflow:hidden">
    <?php if ($cover): ?><div class="card-img" style="aspect-ratio:16/8"><img src="<?= View::e($cover) ?>" alt=""></div><?php endif; ?>
    <div style="padding:22px">
      <?php if (!empty($item['event_type'])): ?><span class="card-meta"><?= View::e($item['event_type']) ?></span><?php endif; ?>
      <h1 style="margin:8px 0"><?= View::e($item['title']) ?></h1>
      <div class="card-meta" style="margin-bottom:16px">
        <?php if (!empty($item['starts_at'])): ?><span>🗓 <?= View::e(date('Y/m/d H:i', strtotime($item['starts_at']))) ?><?php if (!empty($item['ends_at'])): ?> – <?= View::e(date('Y/m/d H:i', strtotime($item['ends_at']))) ?><?php endif; ?></span><?php endif; ?>
        <?php if (!empty($item['city_name'])): ?><span>📍 <?= View::e($item['city_name']) ?></span><?php endif; ?>
        <?php if (!empty($item['venue_name'])): ?><span><?= View::e($item['venue_name']) ?></span><?php endif; ?>
      </div>

      <?php if (!empty($item['maps_url'])): ?>
        <p><a href="<?= View::e($item['maps_url']) ?>" target="_blank" rel="noopener">فتح الموقع على خرائط جوجل ↗</a></p>
      <?php endif; ?>

      <?php if (!empty($item['video_url'])): ?>
        <p><a href="<?= View::e($item['video_url']) ?>" target="_blank" rel="noopener">مشاهدة الفيديو المرفق ↗</a></p>
      <?php endif; ?>

      <div class="page-content"><?= $item['content'] ?></div>

      <?php if ($gallery): ?>
        <div class="section-head" style="margin-top:20px"><h2>معرض الصور</h2></div>
        <div class="grid grid-3">
          <?php foreach ($gallery as $g): ?>
            <div class="card-img"><img loading="lazy" src="<?= View::e(Media::thumbUrl($g['thumb_path'], $g['stored_path'])) ?>" alt=""></div>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>

      <?php if (!empty($item['drive_url']) || $attachments || $externalLinks): ?>
        <div class="section-head" style="margin-top:20px"><h2>روابط ومرفقات</h2></div>
        <ul>
          <?php if (!empty($item['drive_url'])): ?><li><a href="<?= View::e($item['drive_url']) ?>" target="_blank" rel="noopener">مجلد Google Drive ↗</a></li><?php endif; ?>
          <?php foreach ($externalLinks as $link): ?><li><a href="<?= View::e($link) ?>" target="_blank" rel="noopener"><?= View::e($link) ?></a></li><?php endforeach; ?>
          <?php foreach ($attachments as $a): ?><li><a href="<?= View::e(Media::url($a['stored_path'])) ?>" target="_blank" rel="noopener"><?= View::e($a['file_name']) ?></a></li><?php endforeach; ?>
        </ul>
      <?php endif; ?>

      <?php if ($tags): ?>
        <div style="margin-top:18px;display:flex;gap:8px;flex-wrap:wrap">
          <?php foreach ($tags as $tag): ?><span class="badge badge-gray">#<?= View::e($tag) ?></span><?php endforeach; ?>
        </div>
      <?php endif; ?>
    </div>
  </article>

  <div style="display:flex;gap:10px;margin-top:16px">
    <a class="btn btn-secondary" target="_blank" rel="noopener" href="https://wa.me/?text=<?= urlencode($item['title'] . ' ' . Url::current()) ?>">مشاركة عبر واتساب</a>
    <button class="btn btn-secondary" data-share-native data-url="<?= View::e(Url::current()) ?>" data-title="<?= View::e($item['title']) ?>">مشاركة</button>
    <button class="btn btn-secondary" data-copy-link data-url="<?= View::e(Url::current()) ?>">نسخ الرابط</button>
  </div>

  <?php if (!empty($related)): ?>
  <div class="section">
    <div class="section-head"><h2>مناسبات ذات صلة</h2></div>
    <div class="grid grid-3">
      <?php foreach ($related as $r): ?>
        <a class="card" href="<?= Url::to('events/' . $r['slug']) ?>">
          <div class="card-body"><p class="card-title"><?= View::e($r['title']) ?></p></div>
        </a>
      <?php endforeach; ?>
    </div>
  </div>
  <?php endif; ?>
</div>
