<?php
/** @var array $rows
 *  @var int $page
 *  @var int $perPage
 *  @var int $total
 *  @var array $years
 *  @var int|null $currentYear
 *  @var string $currentType
 */
use Core\Media;
use Core\Support\Url;
use Core\View;

function fam_gallery_cover($mediaId)
{
    if (!$mediaId) return Url::theme('assets/img/placeholder.svg');
    $row = \Core\Database::fetchOne('SELECT stored_path, thumb_path FROM ' . \Core\Database::table('media') . ' WHERE id = ?', [$mediaId]);
    return $row ? Media::thumbUrl($row['thumb_path'], $row['stored_path']) : Url::theme('assets/img/placeholder.svg');
}

$totalPages = max(1, (int) ceil($total / $perPage));
?>
<div class="container section">
  <div class="breadcrumbs"><a href="<?= Url::to('') ?>">الرئيسية</a> / المعرض</div>
  <h1>المعرض</h1>

  <?php if (!empty($years)): ?>
  <form method="get" style="display:flex;gap:10px;flex-wrap:wrap;margin:14px 0">
    <select class="form-control" name="year" onchange="this.form.submit()" style="max-width:160px">
      <option value="">كل السنوات</option>
      <?php foreach ($years as $y): ?>
        <option value="<?= View::e((string) $y['year']) ?>" <?= (int) $currentYear === (int) $y['year'] ? 'selected' : '' ?>><?= View::e((string) $y['year']) ?></option>
      <?php endforeach; ?>
    </select>
    <select class="form-control" name="type" onchange="this.form.submit()" style="max-width:160px">
      <option value="">كل الأنواع</option>
      <option value="photo" <?= $currentType === 'photo' ? 'selected' : '' ?>>صور</option>
      <option value="video" <?= $currentType === 'video' ? 'selected' : '' ?>>فيديو</option>
    </select>
  </form>
  <?php endif; ?>

  <div class="grid grid-3">
    <?php foreach ($rows as $album): ?>
      <a class="card" href="<?= Url::to('gallery/' . $album['slug']) ?>">
        <div class="card-img"><img loading="lazy" src="<?= View::e(fam_gallery_cover($album['cover_media_id'])) ?>" alt=""></div>
        <div class="card-body">
          <p class="card-title"><?= View::e($album['title']) ?></p>
          <div class="card-meta">
            <?php if (!empty($album['year'])): ?><span><?= View::e((string) $album['year']) ?></span><?php endif; ?>
            <?php if (!empty($album['city_name'])): ?><span><?= View::e($album['city_name']) ?></span><?php endif; ?>
            <span><?= (int) $album['photos_count'] ?> صورة</span>
          </div>
        </div>
      </a>
    <?php endforeach; ?>
    <?php if (empty($rows)): ?><div class="empty-state">لا توجد ألبومات منشورة حاليًا.</div><?php endif; ?>
  </div>

  <?php if ($totalPages > 1): ?>
  <div style="display:flex;gap:6px;margin-top:20px;flex-wrap:wrap">
    <?php for ($p = 1; $p <= $totalPages; $p++): ?>
      <a class="btn btn-sm <?= $p === $page ? 'btn-primary' : 'btn-outline' ?>" style="<?= $p === $page ? '' : 'color:var(--c-text);border-color:var(--c-border)' ?>" href="<?= Url::to('gallery?page=' . $p) ?>"><?= $p ?></a>
    <?php endfor; ?>
  </div>
  <?php endif; ?>
</div>
