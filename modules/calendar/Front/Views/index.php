<?php
/** @var array $rows
 *  @var array $coverageMap
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
    'when' => $when,
];

$arMonths = [1 => 'يناير', 'فبراير', 'مارس', 'أبريل', 'مايو', 'يونيو', 'يوليو', 'أغسطس', 'سبتمبر', 'أكتوبر', 'نوفمبر', 'ديسمبر'];
$arDays = ['الأحد', 'الاثنين', 'الثلاثاء', 'الأربعاء', 'الخميس', 'الجمعة', 'السبت'];
$todayTs = strtotime(date('Y-m-d'));
?>
<div class="container section">
  <div class="breadcrumbs"><a href="<?= Url::to('') ?>">الرئيسية</a> / رزنامة المناسبات</div>
  <div style="display:flex;justify-content:space-between;align-items:center;gap:10px;flex-wrap:wrap">
    <h1 style="margin:0">📅 رزنامة المناسبات</h1>
    <a class="btn btn-primary btn-sm" href="<?= Url::to('calendar/suggest') ?>">＋ أضف مناسبتك</a>
  </div>

  <div class="calx-tabs">
    <a class="calx-tab <?= $viewMode === 'list' && $when === 'upcoming' ? 'active' : '' ?>" href="?<?= fam_calendar_query(['view' => 'list', 'when' => 'upcoming'], $baseQuery) ?>">القادمة</a>
    <a class="calx-tab <?= $viewMode === 'list' && $when === 'past' ? 'active' : '' ?>" href="?<?= fam_calendar_query(['view' => 'list', 'when' => 'past'], $baseQuery) ?>">الماضية</a>
    <a class="calx-tab <?= $viewMode === 'month' ? 'active' : '' ?>" href="?<?= fam_calendar_query(['view' => 'month'], $baseQuery) ?>">عرض شهري</a>
  </div>

  <?php if (!empty($types)): ?>
  <div class="calx-types">
    <a class="calx-type <?= $selectedType === '' ? 'active' : '' ?>" href="?<?= fam_calendar_query(['view' => $viewMode, 'type' => null], $baseQuery) ?>">الكل</a>
    <?php foreach ($types as $t): ?>
      <a class="calx-type <?= $selectedType === $t ? 'active' : '' ?>" href="?<?= fam_calendar_query(['view' => $viewMode, 'type' => $t], $baseQuery) ?>"><?= View::e($t) ?></a>
    <?php endforeach; ?>
  </div>
  <?php endif; ?>

  <?php if ($viewMode === 'month' && $month): ?>
    <div class="calx-nav">
      <a class="btn btn-sm btn-outline-dark" href="?<?= fam_calendar_query(['view' => 'month', 'month' => $month['prev']], $baseQuery) ?>">→ السابق</a>
      <b style="font-family:var(--font-heading);color:var(--c-primary)"><?= View::e($month['label']) ?></b>
      <a class="btn btn-sm btn-outline-dark" href="?<?= fam_calendar_query(['view' => 'month', 'month' => $month['next']], $baseQuery) ?>">التالي ←</a>
    </div>
    <?php
      $daysByDate = [];
      foreach ($rows as $r) {
          $daysByDate[date('Y-m-d', strtotime($r['entry_datetime']))][] = $r;
      }
    ?>
    <div class="calx-grid">
      <?php foreach (['أحد', 'اثنين', 'ثلاثاء', 'أربعاء', 'خميس', 'جمعة', 'سبت'] as $wd): ?>
        <div class="calx-grid-head"><?= $wd ?></div>
      <?php endforeach; ?>
      <?php for ($i = 0; $i < $month['first_weekday']; $i++): ?><div></div><?php endfor; ?>
      <?php for ($d = 1; $d <= $month['days_in_month']; $d++):
        $dateKey = date('Y-m-d', strtotime($month['key'] . '-' . str_pad((string) $d, 2, '0', STR_PAD_LEFT)));
        $dayEntries = $daysByDate[$dateKey] ?? [];
      ?>
        <div class="calx-cell<?= $dayEntries ? ' has-events' : '' ?>">
          <div class="d"><?= $d ?></div>
          <?php foreach (array_slice($dayEntries, 0, 2) as $e): ?>
            <a href="<?= Url::pretty('calendar', (int) $e['id'], $e['title']) ?>"><?= View::e($e['title']) ?></a>
          <?php endforeach; ?>
          <?php if (count($dayEntries) > 2): ?><span>+<?= count($dayEntries) - 2 ?></span><?php endif; ?>
        </div>
      <?php endfor; ?>
    </div>
  <?php endif; ?>

  <?php if ($viewMode === 'list'): ?>
    <?php if (empty($rows)): ?>
      <div class="empty-state">لا توجد مناسبات <?= $when === 'past' ? 'ماضية' : 'قادمة' ?> حاليًا.</div>
    <?php else: ?>
      <?php
        // تجميع حسب الشهر لعرض أوضح
        $byMonth = [];
        foreach ($rows as $ev) {
            $ts = strtotime($ev['entry_datetime']);
            $byMonth[date('Y-m', $ts)][] = $ev;
        }
      ?>
      <?php foreach ($byMonth as $monthKey => $monthRows):
        $mTs = strtotime($monthKey . '-01');
      ?>
        <div class="calx-month"><span><?= $arMonths[(int) date('n', $mTs)] ?> <?= date('Y', $mTs) ?></span></div>
        <div class="cal-list">
          <?php foreach ($monthRows as $ev):
            $ts = strtotime($ev['entry_datetime']);
            $daysLeft = (int) floor((strtotime(date('Y-m-d', $ts)) - $todayTs) / 86400);
            $isPast = $when === 'past';
            if ($isPast) { $leftLabel = date('Y/m/d', $ts); }
            elseif ($daysLeft <= 0) { $leftLabel = 'اليوم'; }
            elseif ($daysLeft === 1) { $leftLabel = 'غدًا'; }
            elseif ($daysLeft === 2) { $leftLabel = 'باقي يومين'; }
            elseif ($daysLeft <= 10) { $leftLabel = 'باقي ' . $daysLeft . ' أيام'; }
            else { $leftLabel = 'باقي ' . $daysLeft . ' يوم'; }
            $isNext = !$isPast && $ev === $rows[0];
          ?>
          <a class="cal-item<?= $isNext ? ' cal-next' : '' ?><?= $isPast ? ' cal-past' : '' ?>" href="<?= Url::pretty('calendar', (int) $ev['id'], $ev['title']) ?>">
            <div class="cal-date">
              <span><?= $arDays[(int) date('w', $ts)] ?></span>
              <b><?= (int) date('j', $ts) ?></b>
              <span><?= $arMonths[(int) date('n', $ts)] ?></span>
            </div>
            <div class="cal-info">
              <p class="cal-title"><?= View::e($ev['title']) ?></p>
              <span class="cal-meta">
                <span>🏷 <?= View::e($ev['entry_type']) ?></span>
                <?php if (!empty($ev['venue_name'])): ?><span>📍 <?= View::e($ev['venue_name']) ?><?= !empty($ev['city_name']) ? ' — ' . View::e($ev['city_name']) : '' ?></span><?php endif; ?>
                <?php if (!empty($coverageMap[(int) $ev['id']])): ?><span style="color:var(--c-primary);font-weight:800">📸 تغطية متوفرة</span><?php endif; ?>
              </span>
            </div>
            <span class="cal-left"><?= $leftLabel ?></span>
          </a>
          <?php endforeach; ?>
        </div>
      <?php endforeach; ?>
    <?php endif; ?>
  <?php endif; ?>
</div>
