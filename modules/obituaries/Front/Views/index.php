<?php
/** @var array $rows */
use Core\Support\Url;
use Core\View;
?>
<div class="container section">
  <div class="breadcrumbs"><a href="<?= Url::to('') ?>">الرئيسية</a> / الوفيات</div>
  <h1>🕊 الوفيات</h1>
  <p class="form-hint">إنا لله وإنا إليه راجعون — نسأل الله لموتانا وموتى المسلمين الرحمة والمغفرة.</p>

  <?php if (empty($rows)): ?>
    <div class="empty-state">لا توجد إعلانات وفيات حاليًا.</div>
  <?php else: ?>
    <div class="obit-list">
      <?php foreach ($rows as $r): ?>
      <a class="obit-card" href="<?= Url::pretty('obituaries', (int) $r['id'], 'وفاة ' . $r['name']) ?>">
        <span class="obit-icon">🕊</span>
        <span class="obit-body">
          <b>وفاة <?= View::e($r['name']) ?></b>
          <span class="obit-meta">
            <?php if ($r['deceased_on']): ?><span>🗓 <?= View::e(date('Y/m/d', strtotime($r['deceased_on']))) ?></span><?php endif; ?>
            <?php if (!empty($r['condolence_venue'])): ?><span>📍 العزاء: <?= View::e($r['condolence_venue']) ?></span><?php endif; ?>
          </span>
        </span>
        <?php if (!empty($r['city_name'])): ?><span class="obit-city"><?= View::e($r['city_name']) ?></span><?php endif; ?>
      </a>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</div>
