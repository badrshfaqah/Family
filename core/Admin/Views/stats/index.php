<?php
/** @var bool $tracking
 *  @var array|null $cards
 *  @var array $daily
 *  @var array $topPages
 *  @var array $referrers
 *  @var array $devices
 */
use Core\View;

// أسماء عربية واضحة لمسارات الموقع
function fam_stats_label(string $path): string
{
    if ($path === '/' || $path === '') {
        return 'الصفحة الرئيسية';
    }
    $seg = explode('/', trim($path, '/'));
    $map = [
        'calendar' => 'الرزنامة', 'gatherings' => 'الجمعات', 'news' => 'الأخبار',
        'gallery' => 'مكتبة الصور', 'archive' => 'الأرشيف', 'poetry' => 'سماء الشعراء',
        'poems' => 'قصيدة', 'obituaries' => 'الوفيات', 'tree' => 'شجرة النسب',
        'directory' => 'جوال القبيلة', 'install-app' => 'إضافة للشاشة الرئيسية',
        'family' => 'عن البرنامج', 'search' => 'البحث', 'p' => 'صفحة تعريفية', 'branches' => 'فرع',
    ];
    $base = $map[$seg[0]] ?? $seg[0];
    return count($seg) > 1 ? $base . ' — ' . urldecode($seg[1]) : $base;
}

$wd = ['الأحد', 'الاثنين', 'الثلاثاء', 'الأربعاء', 'الخميس', 'الجمعة', 'السبت'];
$maxViews = 1;
foreach ($daily as $d) {
    $maxViews = max($maxViews, $d['views']);
}
$maxPage = 1;
foreach ($topPages as $p) {
    $maxPage = max($maxPage, (int) $p['views']);
}
$devTotal = max(1, $devices['mobile'] + $devices['desktop']);
?>
<style>
.st-cards{display:grid;grid-template-columns:repeat(auto-fit,minmax(150px,1fr));gap:10px;margin-bottom:20px}
.st-card{background:#fff;border:1px solid #e5e5e5;border-radius:12px;padding:14px;text-align:center}
.st-card b{display:block;font-size:1.45rem;color:#1c6e3c}
.st-card i{font-style:normal;font-size:.78rem;color:#888;display:block}
.st-card small{color:#666;font-size:.72rem}
.st-panel{background:#fff;border:1px solid #e5e5e5;border-radius:12px;padding:16px;margin-bottom:18px}
.st-panel h3{margin:0 0 14px;font-size:1rem}
/* مخطط الزيارات اليومي */
.st-chart{display:flex;align-items:flex-end;gap:6px;height:170px;padding-top:22px}
.st-col{flex:1;display:flex;flex-direction:column;align-items:center;gap:5px;min-width:0;height:100%;justify-content:flex-end;position:relative}
.st-bar{
  width:100%;max-width:34px;border-radius:4px 4px 0 0;background:#1c6e3c;
  min-height:3px;position:relative;transition:filter .15s;
}
.st-col:hover .st-bar{filter:brightness(1.15)}
.st-bar.is-today{background:#c9a24b}
.st-val{
  position:absolute;top:-21px;inset-inline:0;text-align:center;
  font-size:.68rem;font-weight:800;color:#333;opacity:0;transition:opacity .15s;
}
.st-col:hover .st-val,.st-val.always{opacity:1}
.st-day{font-size:.64rem;color:#888;white-space:nowrap}
.st-legend{display:flex;gap:16px;margin-top:10px;font-size:.75rem;color:#666;flex-wrap:wrap}
.st-legend span::before{content:"";display:inline-block;width:10px;height:10px;border-radius:3px;margin-inline-end:5px;vertical-align:-1px}
.st-legend .lg-views::before{background:#1c6e3c}
.st-legend .lg-today::before{background:#c9a24b}
/* أكثر الصفحات */
.st-row{display:flex;align-items:center;gap:10px;padding:8px 0;border-bottom:1px dashed #eee}
.st-row:last-child{border-bottom:none}
.st-row .nm{flex:1;min-width:0;font-size:.86rem;font-weight:700;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
.st-row .nm small{display:block;color:#999;font-weight:400;font-size:.68rem;direction:ltr;text-align:end}
.st-meter{flex:2;height:8px;background:#f0efe9;border-radius:999px;overflow:hidden}
.st-meter i{display:block;height:100%;background:#1c6e3c;border-radius:999px}
.st-row .ct{flex:none;font-weight:800;font-size:.82rem;color:#1c6e3c;min-width:44px;text-align:end}
.st-two{display:grid;grid-template-columns:1fr;gap:18px}
@media(min-width:900px){.st-two{grid-template-columns:1.2fr .8fr}}
.st-dev{display:flex;gap:10px;flex-wrap:wrap}
.st-dev span{background:#f0efe9;border-radius:999px;padding:6px 14px;font-size:.82rem;font-weight:700}
</style>

<?php if (!$tracking): ?>
  <div class="alert alert-error">جدول الإحصائيات غير مهيأ بعد — سيُنشأ تلقائيًا مع أول تحديث للنظام.</div>
<?php else: ?>

<p class="form-hint" style="margin:0 0 14px">إحصائيات خاصة بالموقع بلا أي خدمة خارجية — الزائر يُحتسب مرة واحدة يوميًا ببصمة مجهّلة (لا تُخزن عناوين IP)، وتُستبعد الزواحف والبوتات تلقائيًا.</p>

<div class="st-cards">
  <div class="st-card"><i>اليوم</i><b><?= (int) $cards['today']['views'] ?></b><small>زيارة · <?= (int) $cards['today']['visitors'] ?> زائر</small></div>
  <div class="st-card"><i>أمس</i><b><?= (int) $cards['yesterday']['views'] ?></b><small>زيارة · <?= (int) $cards['yesterday']['visitors'] ?> زائر</small></div>
  <div class="st-card"><i>آخر 7 أيام</i><b><?= (int) $cards['week']['views'] ?></b><small>زيارة · <?= (int) $cards['week']['visitors'] ?> زائر</small></div>
  <div class="st-card"><i>آخر 30 يومًا</i><b><?= (int) $cards['month']['views'] ?></b><small>زيارة · <?= (int) $cards['month']['visitors'] ?> زائر</small></div>
  <div class="st-card"><i>الإجمالي</i><b><?= (int) $cards['total']['views'] ?></b><small>زيارة منذ التفعيل</small></div>
</div>

<div class="st-panel">
  <h3>📊 الزيارات اليومية — آخر 14 يومًا</h3>
  <div class="st-chart">
    <?php foreach ($daily as $idx => $d):
      $h = (int) round($d['views'] / $maxViews * 100);
      $isToday = $idx === count($daily) - 1;
      $ts = strtotime($d['date']);
    ?>
    <div class="st-col" title="<?= View::e($wd[(int) date('w', $ts)] . ' ' . date('Y/m/d', $ts)) ?> — <?= (int) $d['views'] ?> زيارة، <?= (int) $d['visitors'] ?> زائر">
      <span class="st-val<?= $isToday || $d['views'] === $maxViews ? ' always' : '' ?>"><?= (int) $d['views'] ?></span>
      <span class="st-bar<?= $isToday ? ' is-today' : '' ?>" style="height:<?= max(2, $h) ?>%"></span>
      <span class="st-day"><?= (int) date('j', $ts) ?>/<?= (int) date('n', $ts) ?></span>
    </div>
    <?php endforeach; ?>
  </div>
  <div class="st-legend"><span class="lg-views">الزيارات</span><span class="lg-today">اليوم الحالي</span></div>
</div>

<div class="st-two">
  <div class="st-panel">
    <h3>🔥 أكثر الصفحات دخولًا — آخر 30 يومًا</h3>
    <?php if (empty($topPages)): ?><p class="form-hint">لا توجد زيارات مسجلة بعد.</p><?php endif; ?>
    <?php foreach ($topPages as $p): ?>
    <div class="st-row">
      <span class="nm"><?= View::e(fam_stats_label($p['path'])) ?><small><?= View::e($p['path']) ?></small></span>
      <span class="st-meter"><i style="width:<?= (int) round((int) $p['views'] / $maxPage * 100) ?>%"></i></span>
      <span class="ct"><?= (int) $p['views'] ?></span>
    </div>
    <?php endforeach; ?>
  </div>

  <div>
    <div class="st-panel">
      <h3>🌍 مصادر الدخول الخارجية</h3>
      <?php if (empty($referrers)): ?><p class="form-hint">لا توجد مصادر خارجية بعد — أغلب الدخول مباشر (رابط محفوظ أو التطبيق المثبت).</p><?php endif; ?>
      <?php foreach ($referrers as $r): ?>
      <div class="st-row">
        <span class="nm" style="direction:ltr;text-align:end"><?= View::e($r['referrer']) ?></span>
        <span class="ct"><?= (int) $r['views'] ?></span>
      </div>
      <?php endforeach; ?>
    </div>
    <div class="st-panel">
      <h3>📱 أجهزة الزوار — آخر 30 يومًا</h3>
      <div class="st-dev">
        <span>📱 جوال <?= (int) round($devices['mobile'] / $devTotal * 100) ?>٪</span>
        <span>🖥 كمبيوتر <?= (int) round($devices['desktop'] / $devTotal * 100) ?>٪</span>
      </div>
    </div>
  </div>
</div>

<?php endif; ?>
