<?php
/** @var array $branch */
use Core\Media;
use Core\Support\Url;
use Core\Terms;
use Core\View;

function fam_branch_cover($mediaId)
{
    if (!$mediaId) return '';
    $row = \Core\Database::fetchOne('SELECT stored_path FROM ' . \Core\Database::table('media') . ' WHERE id = ?', [$mediaId]);
    return $row ? Media::url($row['stored_path']) : '';
}

$cover = fam_branch_cover($branch['image_media_id'] ?? null);
?>
<div class="container section">
  <div class="breadcrumbs"><a href="<?= Url::to('') ?>">الرئيسية</a> / <a href="<?= Url::to('branches') ?>"><?= View::e(Terms::phrase('branches')) ?></a> / <?= View::e($branch['name']) ?></div>

  <article class="card" style="padding:0;overflow:hidden">
    <?php if ($cover): ?><div class="card-img" style="aspect-ratio:16/8"><img src="<?= View::e($cover) ?>" alt=""></div><?php endif; ?>
    <div style="padding:22px">
      <h1 style="margin:8px 0"><?= View::e($branch['name']) ?></h1>
      <?php if (!empty($branch['description'])): ?>
        <div class="page-content"><?= nl2br(View::e($branch['description'])) ?></div>
      <?php endif; ?>
    </div>
  </article>

  <div style="display:flex;gap:10px;margin-top:16px">
    <a class="btn btn-secondary" target="_blank" rel="noopener" href="https://wa.me/?text=<?= urlencode($branch['name'] . ' ' . Url::current()) ?>">مشاركة عبر واتساب</a>
    <button class="btn btn-secondary" data-share-native data-url="<?= View::e(Url::current()) ?>" data-title="<?= View::e($branch['name']) ?>">مشاركة</button>
    <button class="btn btn-secondary" data-copy-link data-url="<?= View::e(Url::current()) ?>">نسخ الرابط</button>
  </div>
</div>
