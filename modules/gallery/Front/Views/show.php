<?php
/** @var array $album
 *  @var array $photos
 */
use Core\Media;
use Core\Support\Url;
use Core\View;
?>
<div class="container section">
  <div class="breadcrumbs"><a href="<?= Url::to('') ?>">الرئيسية</a> / <a href="<?= Url::to('gallery') ?>">المعرض</a> / <?= View::e($album['title']) ?></div>

  <h1 style="margin-bottom:6px"><?= View::e($album['title']) ?></h1>
  <div class="card-meta" style="margin-bottom:10px">
    <?php if (!empty($album['year'])): ?><span><?= View::e((string) $album['year']) ?></span><?php endif; ?>
    <?php if (!empty($album['city_name'])): ?><span><?= View::e($album['city_name']) ?></span><?php endif; ?>
    <span><?= count($photos) ?> صورة</span>
  </div>
  <?php if (!empty($album['description'])): ?><p><?= View::e($album['description']) ?></p><?php endif; ?>

  <div class="grid grid-3" style="margin-top:16px">
    <?php foreach ($photos as $photo): ?>
      <a class="card-img" style="border-radius:var(--radius)" href="<?= View::e(Media::url($photo['stored_path'])) ?>" target="_blank" rel="noopener">
        <img loading="lazy" src="<?= View::e(Media::thumbUrl($photo['thumb_path'], $photo['stored_path'])) ?>" alt="<?= View::e($photo['caption'] ?? '') ?>">
      </a>
      <?php if (!empty($photo['caption'])): ?><div class="form-hint" style="margin-top:-10px"><?= View::e($photo['caption']) ?></div><?php endif; ?>
    <?php endforeach; ?>
    <?php if (empty($photos)): ?><div class="empty-state">لا توجد صور في هذا الألبوم بعد.</div><?php endif; ?>
  </div>
</div>
