<?php
/** @var array $branches */
use Core\Media;
use Core\Support\Url;
use Core\Terms;
use Core\View;

function fam_branch_media_url($mediaId)
{
    if (!$mediaId) return Url::theme('assets/img/placeholder.svg');
    $row = \Core\Database::fetchOne('SELECT stored_path, thumb_path FROM ' . \Core\Database::table('media') . ' WHERE id = ?', [$mediaId]);
    return $row ? Media::thumbUrl($row['thumb_path'], $row['stored_path']) : Url::theme('assets/img/placeholder.svg');
}
?>
<div class="container section">
  <div class="breadcrumbs"><a href="<?= Url::to('') ?>">الرئيسية</a> / <?= View::e(Terms::phrase('branches')) ?></div>
  <h1><?= View::e(Terms::phrase('branches')) ?></h1>

  <div class="grid grid-3">
    <?php foreach ($branches as $b): ?>
      <?php if (!empty($b['show_public_page'])): ?>
        <a class="card" href="<?= Url::to('branches/' . $b['id']) ?>">
          <div class="card-img"><img loading="lazy" src="<?= View::e(fam_branch_media_url($b['image_media_id'])) ?>" alt=""></div>
          <div class="card-body">
            <p class="card-title"><?= View::e($b['name']) ?></p>
            <?php if (!empty($b['description'])): ?><p class="card-meta"><?= View::e($b['description']) ?></p><?php endif; ?>
          </div>
        </a>
      <?php else: ?>
        <div class="card" style="cursor:default">
          <div class="card-body">
            <p class="card-title"><?= View::e($b['name']) ?></p>
            <?php if (!empty($b['description'])): ?><p class="card-meta"><?= View::e($b['description']) ?></p><?php endif; ?>
          </div>
        </div>
      <?php endif; ?>
    <?php endforeach; ?>
    <?php if (empty($branches)): ?><div class="empty-state">لا توجد فروع حاليًا.</div><?php endif; ?>
  </div>
</div>
