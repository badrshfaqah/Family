<?php
/** @var array $poets */
use Core\Media;
use Core\Support\Url;
use Core\View;
?>
<div class="container section">
  <div class="breadcrumbs"><a href="<?= Url::to('') ?>">الرئيسية</a> / سماء الشعراء</div>
  <h1>🪶 سماء الشعراء</h1>
  <p class="form-hint">شعراء العائلة وقصائدهم — اضغط على الشاعر لتصفح قصائده</p>

  <?php if (empty($poets)): ?>
    <div class="empty-state">لا يوجد شعراء مضافون بعد.</div>
  <?php else: ?>
    <div class="poets-grid">
      <?php foreach ($poets as $p): ?>
      <a class="poet-card" href="<?= Url::to('poetry/' . $p['id']) ?>">
        <span class="poet-photo">
          <?php if (!empty($p['photo_path'])): ?>
            <img loading="lazy" src="<?= View::e(Media::url($p['photo_path'])) ?>" alt="<?= View::e($p['name']) ?>">
          <?php else: ?>
            <span class="poet-fallback">🪶</span>
          <?php endif; ?>
        </span>
        <b class="poet-name"><?= View::e($p['name']) ?></b>
        <?php if (!empty($p['bio'])): ?><span class="poet-bio"><?= View::e($p['bio']) ?></span><?php endif; ?>
        <span class="poet-count">📜 <?= (int) $p['poems_count'] ?> قصيدة</span>
      </a>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</div>
