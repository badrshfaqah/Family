<?php
/** @var array $rows الجمعات النشطة مرتبة حسب المدينة */
use Core\Support\Url;
use Core\View;

// تجميع الجمعات حسب المدينة للعرض تحت بعضها
$byCity = [];
foreach ($rows as $item) {
    $byCity[$item['city_name'] ?: 'مدن أخرى'][] = $item;
}
?>
<div class="container section">
  <div class="breadcrumbs"><a href="<?= Url::to('') ?>">الرئيسية</a> / الجمعات</div>
  <div style="display:flex;justify-content:space-between;align-items:center;gap:10px;flex-wrap:wrap">
    <h1 style="margin:0">☕ الجمعات</h1>
    <a class="btn btn-primary btn-sm" href="<?= Url::to('gatherings/suggest') ?>">＋ أضف جمعتك</a>
  </div>

  <?php if (empty($rows)): ?>
    <div class="empty-state">لا توجد جمعات نشطة حاليًا.</div>
  <?php else: ?>
    <?php foreach ($byCity as $cityName => $cityRows): ?>
      <div class="gath-city-head"><span>📍 <?= View::e($cityName) ?></span></div>
      <div class="gath-list">
        <?php foreach ($cityRows as $item): ?>
        <div class="gath-card">
          <div class="gath-icon">☕</div>
          <div class="gath-body">
            <p class="gath-title"><?= View::e($item['title']) ?></p>
            <?php if (!empty($item['recurrence_label'])): ?><span class="gath-chip">🗓 <?= View::e($item['recurrence_label']) ?></span><?php endif; ?>
            <?php if (!empty($item['venue'])): ?><span class="gath-meta">📍 <?= View::e($item['venue']) ?></span><?php endif; ?>
            <?php if (!empty($item['description'])): ?><span class="gath-meta"><?= View::e($item['description']) ?></span><?php endif; ?>
          </div>
        </div>
        <?php endforeach; ?>
      </div>
    <?php endforeach; ?>
  <?php endif; ?>
</div>
