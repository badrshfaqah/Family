<?php
/** @var array $rows الجمعات النشطة مرتبة حسب المدينة
 *  @var array $cities مدن الجمعات النشطة مع العدد
 *  @var int $selectedCityId
 *  @var string $selectedCityName
 */
use Core\Support\Url;
use Core\View;

// تجميع الجمعات حسب المدينة للعرض تحت بعضها
$byCity = [];
foreach ($rows as $item) {
    $byCity[$item['city_name'] ?: 'مدن أخرى'][] = $item;
}
?>
<div class="container section">
  <div class="breadcrumbs"><a href="<?= Url::to('') ?>">الرئيسية</a> / <?php if ($selectedCityName !== ''): ?><a href="<?= Url::to('gatherings') ?>">الجمعات</a> / <?= View::e($selectedCityName) ?><?php else: ?>الجمعات<?php endif; ?></div>
  <div style="display:flex;justify-content:space-between;align-items:center;gap:10px;flex-wrap:wrap">
    <h1 style="margin:0">☕ <?= $selectedCityName !== '' ? 'جمعات ' . View::e($selectedCityName) : 'الجمعات' ?></h1>
    <a class="btn btn-primary btn-sm" href="<?= Url::to('gatherings/suggest') ?>">＋ أضف جمعتك</a>
  </div>

  <?php if (!empty($cities)): ?>
  <div class="gath-cities" style="margin-top:12px">
    <a class="gath-city-chip<?= $selectedCityId === 0 ? ' is-active' : '' ?>" href="<?= Url::to('gatherings') ?>">الكل</a>
    <?php foreach ($cities as $c): ?>
      <a class="gath-city-chip<?= $selectedCityId === (int) $c['id'] ? ' is-active' : '' ?>" href="<?= Url::to('gatherings') ?>?city=<?= (int) $c['id'] ?>">📍 <?= View::e($c['name']) ?> <b><?= (int) $c['cnt'] ?></b></a>
    <?php endforeach; ?>
  </div>
  <?php endif; ?>

  <?php if (empty($rows)): ?>
    <div class="empty-state">لا توجد جمعات نشطة حاليًا<?= $selectedCityName !== '' ? ' في ' . View::e($selectedCityName) : '' ?>.</div>
  <?php else: ?>
    <?php foreach ($byCity as $cityName => $cityRows): ?>
      <div class="gath-city-head"><span>📍 <?= View::e($cityName) ?></span></div>
      <div class="gath-list">
        <?php foreach ($cityRows as $item): ?>
        <div class="gath-card">
          <div class="gath-icon">☕</div>
          <div class="gath-body">
            <p class="gath-title"><a class="gath-title-link" href="<?= Url::to('gatherings/' . $item['id']) ?>"><?= View::e($item['title']) ?></a></p>
            <?php if (!empty($item['recurrence_label'])): ?><span class="gath-chip">🗓 <?= View::e($item['recurrence_label']) ?></span><?php endif; ?>
            <?php if (!empty($item['venue'])): ?><span class="gath-meta">📍 <?= View::e($item['venue']) ?></span><?php endif; ?>
            <?php if (!empty($item['description'])): ?><span class="gath-meta"><?= View::e($item['description']) ?></span><?php endif; ?>
            <?php if (!empty($item['map_url'])): ?><a class="gath-map" href="<?= View::e($item['map_url']) ?>" target="_blank" rel="noopener">🗺 الموقع على الخريطة ←</a><?php endif; ?>
            <a class="gath-map" href="<?= Url::to('gatherings/' . $item['id']) ?>">☕ صفحة الجمعة والتفاصيل ←</a>
          </div>
        </div>
        <?php endforeach; ?>
      </div>
    <?php endforeach; ?>
  <?php endif; ?>
</div>
