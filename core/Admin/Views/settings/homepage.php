<?php
/** @var array $settings */
use Core\Support\Csrf;
use Core\Support\Url;
use Core\View;
include __DIR__ . '/_tabs.php';

$order = $settings['home_sections_order'] ?? [];
if (is_string($order)) { $decoded = json_decode($order, true); $order = is_array($decoded) ? $decoded : []; }
$visible = $settings['home_sections_visible'] ?? [];
if (is_string($visible)) { $decoded = json_decode($visible, true); $visible = is_array($decoded) ? $decoded : []; }

$sectionLabels = [
    'hero' => 'المنطقة الرئيسية (Hero)', 'next_event' => 'المناسبة القادمة', 'coming_soon' => 'قسم قريبًا',
    'ticker' => 'الشريط المتحرك', 'news' => 'آخر الأخبار', 'events' => 'آخر المناسبات',
    'calendar_mini' => 'رزنامة مختصرة', 'gatherings' => 'الجمعات النشطة', 'announcements' => 'إعلانات الإدارة',
    'gallery' => 'صور من المعرض', 'quick_links' => 'روابط سريعة',
];
?>
<form method="post" action="<?= Url::admin('settings/homepage/save') ?>" class="card-box">
  <?= Csrf::field() ?>
  <div class="form-group">
    <label>ترتيب أقسام الصفحة الرئيسية (افصل بينها بفاصلة، بنفس المفاتيح البرمجية)</label>
    <input class="form-control" name="sections_order" value="<?= View::e(implode(',', $order)) ?>">
    <div class="form-hint">المفاتيح المتاحة: <?= View::e(implode(', ', array_keys($sectionLabels))) ?></div>
  </div>

  <div class="form-group">
    <label>إظهار/إخفاء الأقسام</label>
    <?php foreach ($sectionLabels as $key => $label): ?>
      <div class="switch-row">
        <span><?= View::e($label) ?></span>
        <label><input type="checkbox" name="visible[]" value="<?= $key ?>" <?= !empty($visible[$key]) ? 'checked' : '' ?>> إظهار</label>
      </div>
    <?php endforeach; ?>
  </div>

  <div class="form-row cols-2">
    <div class="form-group">
      <label>عدد العناصر الظاهرة في كل قسم</label>
      <input class="form-control" type="number" min="3" max="20" name="home_items_count" value="<?= (int) ($settings['home_items_count'] ?? 6) ?>">
    </div>
    <div class="form-group">
      <label>الشريط المتحرك</label>
      <label class="switch-row"><span>تفعيل الشريط المتحرك</span><input type="checkbox" name="ticker_enabled" <?= ($settings['ticker_enabled'] ?? '1') === '1' ? 'checked' : '' ?>></label>
    </div>
  </div>

  <button class="btn btn-primary" type="submit">حفظ</button>
</form>
