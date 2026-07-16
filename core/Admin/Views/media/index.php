<?php
/** @var array $items
 *  @var int $total
 *  @var int $page
 *  @var int $perPage
 */
use Core\Media;
use Core\Support\Csrf;
use Core\Support\Url;
use Core\View;

$totalPages = max(1, (int) ceil($total / $perPage));
?>
<div class="card-box">
  <form method="post" action="<?= Url::admin('media/upload') ?>" enctype="multipart/form-data" style="display:flex;gap:10px;align-items:end;flex-wrap:wrap">
    <?= Csrf::field() ?>
    <div class="form-group" style="flex:1;min-width:220px;margin:0">
      <input class="form-control" type="file" name="files[]" multiple required>
    </div>
    <button class="btn btn-primary" type="submit">رفع</button>
  </form>
</div>

<div class="grid-cards" style="grid-template-columns:repeat(auto-fill,minmax(150px,1fr))">
  <?php foreach ($items as $m): ?>
    <div class="card-box" style="padding:10px">
      <?php if (Media::isImageExt($m['extension'] ?? '')): ?>
        <img src="<?= View::e(Media::thumbUrl($m['thumb_path'], $m['stored_path'])) ?>" alt="<?= View::e($m['alt_text'] ?? '') ?>" style="width:100%;aspect-ratio:1;object-fit:cover;border-radius:8px;margin-bottom:8px">
      <?php else: ?>
        <div style="width:100%;aspect-ratio:1;background:#eef1ef;border-radius:8px;display:flex;align-items:center;justify-content:center;margin-bottom:8px;font-size:1.6rem">📄</div>
      <?php endif; ?>
      <p style="font-size:.78rem;word-break:break-word;margin:0 0 8px"><?= View::e($m['file_name']) ?></p>
      <form method="post" action="<?= Url::admin('media/' . $m['id'] . '/delete') ?>" data-confirm="حذف هذا الملف؟">
        <?= Csrf::field() ?>
        <button class="btn btn-danger btn-sm" type="submit" style="width:100%">حذف</button>
      </form>
    </div>
  <?php endforeach; ?>
  <?php if (empty($items)): ?><p class="form-hint">لا توجد ملفات مرفوعة بعد.</p><?php endif; ?>
</div>

<?php if ($totalPages > 1): ?>
<div style="display:flex;gap:6px;margin-top:14px">
  <?php for ($p = 1; $p <= $totalPages; $p++): ?>
    <a class="btn btn-sm <?= $p === $page ? 'btn-primary' : 'btn-secondary' ?>" href="<?= Url::admin('media?page=' . $p) ?>"><?= $p ?></a>
  <?php endfor; ?>
</div>
<?php endif; ?>
