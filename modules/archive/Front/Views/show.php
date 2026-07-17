<?php
/** @var array $item
 *  @var array|null $file
 */
use Core\Media;
use Core\Support\Url;
use Core\Terms;
use Core\View;

$cover = $item['cover_media_id']
    ? \Core\Database::fetchOne('SELECT stored_path FROM ' . \Core\Database::table('media') . ' WHERE id = ?', [$item['cover_media_id']])
    : null;

$tags = array_filter(array_map('trim', explode(',', $item['tags'] ?? '')));
$fileUrl = $file ? Media::url($file['stored_path']) : null;
$fileExt = $file ? strtolower($file['extension'] ?? '') : '';
$isPdf = $fileExt === 'pdf';
$isImage = Media::isImageExt($fileExt);
?>
<div class="container section">
  <div class="breadcrumbs"><a href="<?= Url::to('') ?>">الرئيسية</a> / <a href="<?= Url::to('archive') ?>"><?= View::e(Terms::phrase('archive')) ?></a> / <?= View::e($item['title']) ?></div>

  <article class="card" style="padding:0;overflow:hidden">
    <?php if ($cover): ?><div class="card-img" style="aspect-ratio:16/8"><img src="<?= View::e(Media::url($cover['stored_path'])) ?>" alt=""></div><?php endif; ?>
    <div style="padding:22px">
      <?php if (!empty($item['category_name'])): ?><span class="card-meta"><?= View::e($item['category_name']) ?></span><?php endif; ?>
      <h1 style="margin:8px 0"><?= View::e($item['title']) ?></h1>
      <div class="card-meta" style="margin-bottom:16px">
        <?php if (!empty($item['period_label'])): ?><span><?= View::e($item['period_label']) ?></span><?php endif; ?>
        <?php if (!empty($item['source'])): ?><span>المصدر: <?= View::e($item['source']) ?></span><?php endif; ?>
      </div>

      <?php if (!empty($item['description'])): ?><div class="page-content"><?= nl2br(View::e($item['description'])) ?></div><?php endif; ?>

      <?php if ($file): ?>
        <div style="margin-top:20px">
          <?php if ($isPdf): ?>
            <iframe src="<?= View::e($fileUrl) ?>" style="width:100%;height:70vh;border:1px solid var(--c-border);border-radius:8px" title="<?= View::e($item['title']) ?>"></iframe>
          <?php elseif ($isImage): ?>
            <img src="<?= View::e($fileUrl) ?>" style="max-width:100%;border-radius:8px" alt="<?= View::e($item['title']) ?>">
          <?php endif; ?>
          <div style="margin-top:12px">
            <a class="btn btn-primary" href="<?= View::e($fileUrl) ?>" download target="_blank" rel="noopener">تحميل الملف</a>
          </div>
        </div>
      <?php endif; ?>

      <?php if (!empty($item['notes'])): ?>
        <div style="margin-top:16px"><strong>ملاحظات:</strong> <?= View::e($item['notes']) ?></div>
      <?php endif; ?>

      <?php if ($tags): ?>
        <div style="margin-top:18px;display:flex;gap:8px;flex-wrap:wrap">
          <?php foreach ($tags as $tag): ?><span class="badge badge-gray">#<?= View::e($tag) ?></span><?php endforeach; ?>
        </div>
      <?php endif; ?>
    </div>
  </article>
</div>
