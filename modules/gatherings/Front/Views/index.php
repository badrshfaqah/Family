<?php
/** @var array $rows
 *  @var array $cities
 *  @var int $selectedCity
 *  @var int $page
 *  @var int $perPage
 *  @var int $total
 */
use Core\Media;
use Core\Support\Url;
use Core\View;

function fam_gatherings_media_url($mediaId)
{
    if (!$mediaId) return Url::theme('assets/img/placeholder.svg');
    $row = \Core\Database::fetchOne('SELECT stored_path, thumb_path FROM ' . \Core\Database::table('media') . ' WHERE id = ?', [$mediaId]);
    return $row ? Media::thumbUrl($row['thumb_path'], $row['stored_path']) : Url::theme('assets/img/placeholder.svg');
}

$totalPages = max(1, (int) ceil($total / $perPage));
$pageLink = function (int $p) use ($selectedCity) {
    $q = ['page' => $p];
    if ($selectedCity) {
        $q['city_id'] = $selectedCity;
    }
    return Url::to('gatherings?' . http_build_query($q));
};
?>
<div class="container section">
  <div class="breadcrumbs"><a href="<?= Url::to('') ?>">الرئيسية</a> / الجمعات</div>
  <h1>الجمعات</h1>

  <?php if (!empty($cities)): ?>
  <form method="get" action="<?= Url::to('gatherings') ?>" style="margin-bottom:18px;display:flex;gap:8px;align-items:center;flex-wrap:wrap">
    <label for="city_id" style="font-weight:600">تصفية حسب المدينة</label>
    <select class="form-control" style="max-width:220px" name="city_id" id="city_id" onchange="this.form.submit()">
      <option value="">كل المدن</option>
      <?php foreach ($cities as $c): ?>
        <option value="<?= $c['id'] ?>" <?= $selectedCity == $c['id'] ? 'selected' : '' ?>><?= View::e($c['name']) ?></option>
      <?php endforeach; ?>
    </select>
    <noscript><button class="btn btn-outline btn-sm" type="submit">تصفية</button></noscript>
  </form>
  <?php endif; ?>

  <div class="grid grid-3">
    <?php foreach ($rows as $item): ?>
      <div class="card">
        <div class="card-img"><img loading="lazy" src="<?= View::e(fam_gatherings_media_url($item['cover_media_id'])) ?>" alt=""></div>
        <div class="card-body">
          <p class="card-title"><?= View::e($item['title']) ?></p>
          <?php if (!empty($item['description'])): ?><p><?= View::e($item['description']) ?></p><?php endif; ?>
          <div class="card-meta">
            <span><?= View::e($item['recurrence_label'] ?? '') ?></span>
            <?php if (!empty($item['city_name'])): ?><span><?= View::e($item['city_name']) ?></span><?php endif; ?>
            <?php if (!empty($item['venue'])): ?><span><?= View::e($item['venue']) ?></span><?php endif; ?>
          </div>
        </div>
      </div>
    <?php endforeach; ?>
    <?php if (empty($rows)): ?><div class="empty-state">لا توجد جمعات نشطة حاليًا.</div><?php endif; ?>
  </div>

  <?php if ($totalPages > 1): ?>
  <div style="display:flex;gap:6px;margin-top:20px;flex-wrap:wrap">
    <?php for ($p = 1; $p <= $totalPages; $p++): ?>
      <a class="btn btn-sm <?= $p === $page ? 'btn-primary' : 'btn-outline' ?>" style="<?= $p === $page ? '' : 'color:var(--c-text);border-color:var(--c-border)' ?>" href="<?= $pageLink($p) ?>"><?= $p ?></a>
    <?php endfor; ?>
  </div>
  <?php endif; ?>
</div>
