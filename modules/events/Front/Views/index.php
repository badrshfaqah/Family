<?php
/** @var array $rows
 *  @var int $page
 *  @var int $perPage
 *  @var int $total
 */
use Core\Media;
use Core\Support\Url;
use Core\Terms;
use Core\View;

function fam_event_media_url($mediaId)
{
    if (!$mediaId) return Url::theme('assets/img/placeholder.svg');
    $row = \Core\Database::fetchOne('SELECT stored_path, thumb_path FROM ' . \Core\Database::table('media') . ' WHERE id = ?', [$mediaId]);
    return $row ? Media::thumbUrl($row['thumb_path'], $row['stored_path']) : Url::theme('assets/img/placeholder.svg');
}

$totalPages = max(1, (int) ceil($total / $perPage));
?>
<div class="container section">
  <div class="breadcrumbs"><a href="<?= Url::to('') ?>">الرئيسية</a> / <?= View::e(Terms::phrase('events')) ?></div>
  <h1><?= View::e(Terms::phrase('events')) ?></h1>

  <div class="grid grid-3">
    <?php foreach ($rows as $item): ?>
      <a class="card" href="<?= Url::to('events/' . $item['slug']) ?>">
        <div class="card-img"><img loading="lazy" src="<?= View::e(fam_event_media_url($item['cover_media_id'])) ?>" alt=""></div>
        <div class="card-body">
          <?php if (!empty($item['event_type'])): ?><span class="card-meta"><?= View::e($item['event_type']) ?><?php if (!empty($item['city_name'])): ?> · <?= View::e($item['city_name']) ?><?php endif; ?></span><?php endif; ?>
          <p class="card-title"><?= View::e($item['title']) ?></p>
          <div class="card-meta"><span><?= View::e($item['starts_at'] ? date('Y/m/d', strtotime($item['starts_at'])) : date('Y/m/d', strtotime($item['published_at']))) ?></span></div>
        </div>
      </a>
    <?php endforeach; ?>
    <?php if (empty($rows)): ?><div class="empty-state">لا توجد مناسبات منشورة حاليًا.</div><?php endif; ?>
  </div>

  <?php if ($totalPages > 1): ?>
  <div style="display:flex;gap:6px;margin-top:20px;flex-wrap:wrap">
    <?php for ($p = 1; $p <= $totalPages; $p++): ?>
      <a class="btn btn-sm <?= $p === $page ? 'btn-primary' : 'btn-outline' ?>" style="<?= $p === $page ? '' : 'color:var(--c-text);border-color:var(--c-border)' ?>" href="<?= Url::to('events?page=' . $p) ?>"><?= $p ?></a>
    <?php endfor; ?>
  </div>
  <?php endif; ?>
</div>
