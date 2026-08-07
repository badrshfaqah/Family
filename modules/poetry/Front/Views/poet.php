<?php
/** @var array $poet
 *  @var array $poems
 */
use Core\Media;
use Core\Support\Url;
use Core\View;
?>
<div class="container section">
  <div class="breadcrumbs"><a href="<?= Url::to('') ?>">الرئيسية</a> / <a href="<?= Url::to('poetry') ?>">سماء الشعراء</a> / <?= View::e($poet['name']) ?></div>

  <div class="poet-hero">
    <span class="poet-photo poet-photo-lg">
      <?php if (!empty($poet['photo_path'])): ?>
        <img src="<?= View::e(Media::url($poet['photo_path'])) ?>" alt="<?= View::e($poet['name']) ?>">
      <?php else: ?>
        <span class="poet-fallback">🪶</span>
      <?php endif; ?>
    </span>
    <div>
      <h1 style="margin:0 0 4px"><?= View::e($poet['name']) ?></h1>
      <?php if (!empty($poet['bio'])): ?><p class="form-hint" style="margin:0"><?= View::e($poet['bio']) ?></p><?php endif; ?>
      <span class="poet-count" style="margin-top:8px">📜 <?= count($poems) ?> قصيدة</span>
    </div>
  </div>

  <?php if (empty($poems)): ?>
    <div class="empty-state">لا توجد قصائد منشورة لهذا الشاعر بعد.</div>
  <?php else: ?>
    <div class="poems-grid">
      <?php foreach ($poems as $po):
        $lines = preg_split('/\r\n|\r|\n/', trim($po['content']));
        $preview = implode("\n", array_slice(array_filter($lines, fn($l) => trim($l) !== ''), 0, 2));
      ?>
      <a class="poem-card" href="<?= Url::to('poems/' . $po['id']) ?>">
        <span class="poem-mark">🪶</span>
        <b class="poem-title"><?= View::e($po['title']) ?></b>
        <?php if (!empty($po['occasion'])): ?><span class="poem-occasion"><?= View::e($po['occasion']) ?></span><?php endif; ?>
        <span class="poem-preview"><?= nl2br(View::e($preview)) ?> ...</span>
        <span class="poem-open">اقرأ القصيدة ←</span>
      </a>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</div>
