<?php
/** @var array $rows
 *  @var array $types
 *  @var array $cities
 *  @var string $selectedType
 *  @var array $selectedCityIds
 *  @var string $viewMode
 *  @var string $when
 *  @var array|null $month
 */
use Core\Support\Url;
use Core\View;

function fam_calendar_query(array $overrides, array $base): string
{
    $q = array_merge($base, $overrides);
    $q = array_filter($q, fn($v) => $v !== null && $v !== '' && $v !== []);
    return http_build_query($q);
}

$baseQuery = [
    'type' => $selectedType,
    'city' => $selectedCityIds,
    'when' => $when,
];
?>
<div class="container section">
  <div class="breadcrumbs"><a href="<?= Url::to('') ?>">الرئيسية</a> / رزنامة المناسبات</div>
  <h1>رزنامة المناسبات</h1>

  <div style="display:flex;gap:8px;margin:14px 0;flex-wrap:wrap">
    <a class="btn btn-sm <?= $viewMode === 'list' ? 'btn-primary' : 'btn-outline' ?>" href="?<?= fam_calendar_query(['view' => 'list'], $baseQuery) ?>">قائمة</a>
    <a class="btn btn-sm <?= $viewMode === 'month' ? 'btn-primary' : 'btn-outline' ?>" href="?<?= fam_calendar_query(['view' => 'month'], $baseQuery) ?>">عرض شهري</a>
    <?php if ($viewMode === 'list'): ?>
      <a class="btn btn-sm <?= $when === 'upcoming' ? 'btn-primary' : 'btn-outline' ?>" href="?<?= fam_calendar_query(['view' => 'list', 'when' => 'upcoming'], $baseQuery) ?>">القادمة</a>
      <a class="btn btn-sm <?= $when === 'past' ? 'btn-primary' : 'btn-outline' ?>" href="?<?= fam_calendar_query(['view' => 'list', 'when' => 'past'], $baseQuery) ?>">الماضية</a>
    <?php endif; ?>
  </div>

  <form method="get" class="card-box" style="display:flex;gap:12px;flex-wrap:wrap;align-items:end;margin-bottom:20px">
    <input type="hidden" name="view" value="<?= View::e($viewMode) ?>">
    <?php if ($viewMode === 'list'): ?><input type="hidden" name="when" value="<?= View::e($when) ?>"><?php endif; ?>
    <div class="form-group">
      <label>النوع</label>
      <select class="form-control" name="type">
        <option value="">كل الأنواع</option>
        <?php foreach ($types as $t): ?>
          <option value="<?= View::e($t) ?>" <?= $selectedType === $t ? 'selected' : '' ?>><?= View::e($t) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="form-group">
      <label>المدن (يمكن اختيار أكثر من مدينة)</label>
      <div style="display:flex;gap:10px;flex-wrap:wrap">
        <?php foreach ($cities as $c): ?>
          <label style="display:flex;gap:4px;align-items:center;font-size:.85rem">
            <input type="checkbox" name="city[]" value="<?= $c['id'] ?>" <?= in_array((int) $c['id'], $selectedCityIds, true) ? 'checked' : '' ?>>
            <?= View::e($c['name']) ?>
          </label>
        <?php endforeach; ?>
      </div>
    </div>
    <button class="btn btn-secondary" type="submit">تصفية</button>
  </form>

  <?php if ($viewMode === 'month' && $month): ?>
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:14px">
      <a class="btn btn-sm btn-outline" href="?<?= fam_calendar_query(['view' => 'month', 'month' => $month['prev']], $baseQuery) ?>">الشهر السابق ←</a>
      <b><?= View::e($month['label']) ?></b>
      <a class="btn btn-sm btn-outline" href="?<?= fam_calendar_query(['view' => 'month', 'month' => $month['next']], $baseQuery) ?>">→ الشهر التالي</a>
    </div>
    <?php
      $daysByDate = [];
      foreach ($rows as $r) {
          $daysByDate[date('Y-m-d', strtotime($r['entry_datetime']))][] = $r;
      }
    ?>
    <div style="display:grid;grid-template-columns:repeat(7,1fr);gap:6px;margin-bottom:24px">
      <?php foreach (['أحد','اثنين','ثلاثاء','أربعاء','خميس','جمعة','سبت'] as $wd): ?>
        <div style="text-align:center;font-weight:700;font-size:.8rem"><?= $wd ?></div>
      <?php endforeach; ?>
      <?php for ($i = 0; $i < $month['first_weekday']; $i++): ?><div></div><?php endfor; ?>
      <?php for ($d = 1; $d <= $month['days_in_month']; $d++):
        $dateKey = date('Y-m-d', strtotime($month['key'] . '-' . str_pad((string) $d, 2, '0', STR_PAD_LEFT)));
        $dayEntries = $daysByDate[$dateKey] ?? [];
      ?>
        <div style="border:1px solid var(--c-border);border-radius:8px;min-height:70px;padding:4px;font-size:.75rem">
          <div style="font-weight:700"><?= $d ?></div>
          <?php foreach (array_slice($dayEntries, 0, 2) as $e): ?>
            <a href="<?= Url::to('calendar/' . $e['id']) ?>" style="display:block;overflow:hidden;text-overflow:ellipsis;white-space:nowrap"><?= View::e($e['title']) ?></a>
          <?php endforeach; ?>
          <?php if (count($dayEntries) > 2): ?><span>+<?= count($dayEntries) - 2 ?></span><?php endif; ?>
        </div>
      <?php endfor; ?>
    </div>
  <?php endif; ?>

  <div class="grid grid-3">
    <?php foreach ($rows as $ev): ?>
      <a class="card" href="<?= Url::to('calendar/' . $ev['id']) ?>">
        <div class="card-body">
          <span class="card-meta"><?= View::e($ev['entry_type']) ?><?php if (!empty($ev['city_name'])): ?> · <?= View::e($ev['city_name']) ?><?php endif; ?></span>
          <p class="card-title"><?= View::e($ev['title']) ?></p>
          <div class="card-meta"><span><?= View::e(date('Y/m/d H:i', strtotime($ev['entry_datetime']))) ?></span></div>
        </div>
      </a>
    <?php endforeach; ?>
    <?php if (empty($rows)): ?><div class="empty-state">لا توجد مواعيد مطابقة حاليًا.</div><?php endif; ?>
  </div>
</div>
