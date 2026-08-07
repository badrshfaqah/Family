<?php
/** @var array $rows
 *  @var int $page
 *  @var int $perPage
 *  @var int $total
 *  @var array $categories
 *  @var int|null $currentCategory
 */
use Core\Media;
use Core\Support\Url;
use Core\Terms;
use Core\View;

function fam_archive_cover($mediaId)
{
    if (!$mediaId) return Url::theme('assets/img/placeholder.svg');
    $row = \Core\Database::fetchOne('SELECT stored_path, thumb_path FROM ' . \Core\Database::table('media') . ' WHERE id = ?', [$mediaId]);
    return $row ? Media::thumbUrl($row['thumb_path'], $row['stored_path']) : Url::theme('assets/img/placeholder.svg');
}

$totalPages = max(1, (int) ceil($total / $perPage));
?>
<div class="container section">
  <div class="breadcrumbs"><a href="<?= Url::to('') ?>">الرئيسية</a> / <?= View::e(Terms::phrase('archive')) ?></div>
  <h1>📜 <?= View::e(Terms::phrase('archive')) ?></h1>
  <p class="form-hint">ذاكرة القبيلة الموثقة — تصفح حسب التصنيف</p>

  <?php if (!empty($categories)): ?>
  <div class="arc-cats">
    <a class="arc-cat <?= !$currentCategory ? 'active' : '' ?>" href="<?= Url::to('archive') ?>">
      <span class="arc-cat-icon">🗂</span>
      <b>الكل</b>
      <span class="arc-cat-count"><?= (int) ($allCount ?? 0) ?> مادة</span>
    </a>
    <?php foreach ($categories as $c): ?>
    <a class="arc-cat <?= (int) $currentCategory === (int) $c['id'] ? 'active' : '' ?>" href="<?= Url::to('archive?category_id=' . (int) $c['id']) ?>">
      <span class="arc-cat-icon">📜</span>
      <b><?= View::e($c['name']) ?></b>
      <span class="arc-cat-count"><?= (int) ($c['items_count'] ?? 0) ?> مادة</span>
    </a>
    <?php endforeach; ?>
  </div>
  <?php endif; ?>

  <div class="grid grid-3">
    <?php foreach ($rows as $item): ?>
      <a class="card" href="<?= Url::to('archive/' . $item['slug']) ?>">
        <div class="card-img"><img loading="lazy" src="<?= View::e(fam_archive_cover($item['cover_media_id'])) ?>" alt=""></div>
        <div class="card-body">
          <?php if (!empty($item['category_name'])): ?><span class="arc-chip"><?= View::e($item['category_name']) ?></span><?php endif; ?>
          <p class="card-title"><?= View::e($item['title']) ?></p>
          <?php if (!empty($item['period_label'])): ?><div class="card-meta"><span>🕰 <?= View::e($item['period_label']) ?></span></div><?php endif; ?>
        </div>
      </a>
    <?php endforeach; ?>
    <?php if (empty($rows)): ?><div class="empty-state">لا توجد عناصر أرشيف منشورة حاليًا.</div><?php endif; ?>
  </div>

  <?php if ($totalPages > 1): ?>
  <div style="display:flex;gap:6px;margin-top:20px;flex-wrap:wrap">
    <?php for ($p = 1; $p <= $totalPages; $p++): ?>
      <a class="btn btn-sm <?= $p === $page ? 'btn-primary' : 'btn-outline' ?>" style="<?= $p === $page ? '' : 'color:var(--c-text);border-color:var(--c-border)' ?>" href="<?= Url::to('archive?page=' . $p . ($currentCategory ? '&category_id=' . (int) $currentCategory : '')) ?>"><?= $p ?></a>
    <?php endfor; ?>
  </div>
  <?php endif; ?>
</div>
