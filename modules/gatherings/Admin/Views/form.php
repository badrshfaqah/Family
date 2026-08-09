<?php
/** @var array|null $gathering
 *  @var array $cities
 *
 * ملاحظة: يُستخدم اسم المتغير $gathering عمدًا بدلًا من $item هنا،
 * لأن قالب التخطيط الإداري المشترك core/Admin/Views/layouts/admin.php
 * يستخدم $item كمتغير حلقة (foreach) في قوائم الشريط الجانبي دون unset()،
 * فيُعاد تعريفه ويطغى على أي $item يُمرَّر من المتحكم قبل تضمين contentView.
 */
use Core\Database;
use Core\Media;
use Core\Support\Csrf;
use Core\Support\Url;
use Core\View;

$isEdit = $gathering !== null;
$action = $isEdit ? Url::admin('gatherings/' . $gathering['id'] . '/update') : Url::admin('gatherings/store');

$selectedDays = [];
if (!empty($gathering['recurrence_days'])) {
    $selectedDays = array_map('intval', explode(',', $gathering['recurrence_days']));
}

$dayLabels = [0 => 'الأحد', 1 => 'الاثنين', 2 => 'الثلاثاء', 3 => 'الأربعاء', 4 => 'الخميس', 5 => 'الجمعة', 6 => 'السبت'];
$ordinalLabels = [1 => 'الأول', 2 => 'الثاني', 3 => 'الثالث', 4 => 'الرابع', 5 => 'الأخير'];
$type = $gathering['recurrence_type'] ?? 'weekly';
$ordinal = (int) ($gathering['recurrence_ordinal'] ?? 1);
$status = $gathering['status'] ?? 'active';
$selectedPeriods = array_filter(array_map('trim', explode(',', (string) ($gathering['time_period'] ?? ''))));
?>
<form method="post" action="<?= $action ?>" class="card-box" enctype="multipart/form-data">
  <?= Csrf::field() ?>

  <div class="form-row cols-2">
    <div class="form-group"><label>اسم الجمعة أو المجلس</label><input class="form-control" name="title" value="<?= View::e($gathering['title'] ?? '') ?>" required></div>
    <div class="form-group">
      <label>المدينة</label>
      <select class="form-control" name="city_id">
        <option value="">بدون تحديد</option>
        <?php foreach ($cities as $c): ?>
          <option value="<?= $c['id'] ?>" <?= ($gathering['city_id'] ?? null) == $c['id'] ? 'selected' : '' ?>><?= View::e($c['name']) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
  </div>

  <div class="form-group"><label>وصف مختصر</label><textarea class="form-control" name="description" rows="2"><?= View::e($gathering['description'] ?? '') ?></textarea></div>

  <div class="form-row cols-2">
    <div class="form-group"><label>المكان</label><input class="form-control" name="venue" value="<?= View::e($gathering['venue'] ?? '') ?>"></div>
    <div class="form-group"><label>رابط خرائط جوجل (اختياري)</label><input class="form-control" dir="ltr" name="map_url" value="<?= View::e($gathering['map_url'] ?? '') ?>" placeholder="https://maps.google.com/..."></div>
  </div>

  <div class="form-group">
    <label>الصورة (اختياري)</label>
    <?php if (!empty($gathering['cover_media_id'])):
      $cover = Database::fetchOne('SELECT stored_path FROM ' . Database::table('media') . ' WHERE id = ?', [$gathering['cover_media_id']]);
      if ($cover): ?><img src="<?= View::e(Media::url($cover['stored_path'])) ?>" style="height:70px;border-radius:8px;margin-bottom:8px"><?php endif;
    endif; ?>
    <input class="form-control" type="file" name="cover" accept="image/*">
  </div>

  <div class="form-group">
    <label>وقت الجمعة (يمكن اختيار أكثر من فترة)</label>
    <div style="display:flex;flex-wrap:wrap;gap:12px">
      <?php foreach (\Modules\Gatherings\Support\RecurrenceLabel::PERIODS as $pKey => $pLabel): ?>
        <label style="display:flex;gap:6px;align-items:center">
          <input type="checkbox" name="time_period[]" value="<?= $pKey ?>" <?= in_array($pKey, $selectedPeriods, true) ? 'checked' : '' ?>>
          <?= View::e($pLabel) ?>
        </label>
      <?php endforeach; ?>
    </div>
  </div>

  <h3 style="font-size:.95rem;margin-top:10px">التكرار</h3>
  <div class="form-hint" style="margin-bottom:10px">اختر نوع التكرار، ثم املأ الحقول المرتبطة به فقط (الأيام لأنواع "أسبوعية" و"أيام محددة"، والترتيب واليوم لنوع "شهرية"، والنص الحر لنوع "تكرار مخصص").</div>

  <div class="form-group">
    <label>نوع التكرار</label>
    <select class="form-control" name="recurrence_type">
      <option value="daily" <?= $type === 'daily' ? 'selected' : '' ?>>يومية</option>
      <option value="weekly" <?= $type === 'weekly' ? 'selected' : '' ?>>أسبوعية (يوم واحد)</option>
      <option value="specific_weekdays" <?= $type === 'specific_weekdays' ? 'selected' : '' ?>>في أيام محددة (أكثر من يوم)</option>
      <option value="monthly" <?= $type === 'monthly' ? 'selected' : '' ?>>شهرية (مثل: أول خميس من كل شهر)</option>
      <option value="custom" <?= $type === 'custom' ? 'selected' : '' ?>>بتكرار مخصص</option>
    </select>
  </div>

  <div class="form-group">
    <label>أيام التكرار (لنوعي "أسبوعية" و"أيام محددة"، ولليوم الأول من نوع "شهرية")</label>
    <div style="display:flex;flex-wrap:wrap;gap:12px">
      <?php foreach ($dayLabels as $num => $label): ?>
        <label style="display:flex;gap:6px;align-items:center">
          <input type="checkbox" name="recurrence_days[]" value="<?= $num ?>" <?= in_array($num, $selectedDays, true) ? 'checked' : '' ?>>
          <?= View::e($label) ?>
        </label>
      <?php endforeach; ?>
    </div>
  </div>

  <div class="form-group">
    <label>ترتيب الأسبوع من الشهر (لنوع "شهرية" فقط)</label>
    <select class="form-control" name="recurrence_ordinal">
      <?php foreach ($ordinalLabels as $num => $label): ?>
        <option value="<?= $num ?>" <?= $ordinal === $num ? 'selected' : '' ?>><?= View::e($label) ?></option>
      <?php endforeach; ?>
    </select>
  </div>

  <div class="form-group"><label>نص التكرار المخصص (لنوع "بتكرار مخصص" فقط)</label><input class="form-control" name="recurrence_custom_text" value="<?= View::e($gathering['recurrence_custom_text'] ?? '') ?>" placeholder="مثال: كل ثلاثة أيام"></div>

  <div class="form-row cols-2">
    <div class="form-group"><label>تاريخ بداية التكرار</label><input class="form-control" type="date" name="starts_on" value="<?= View::e($gathering['starts_on'] ?? date('Y-m-d')) ?>" required></div>
    <div class="form-group"><label>تاريخ نهاية التكرار (اختياري)</label><input class="form-control" type="date" name="ends_on" value="<?= View::e($gathering['ends_on'] ?? '') ?>"></div>
  </div>

  <div class="form-row cols-2">
    <div class="form-group">
      <label>حالة الجمعة</label>
      <select class="form-control" name="status">
        <option value="active" <?= $status === 'active' ? 'selected' : '' ?>>نشطة</option>
        <option value="paused" <?= $status === 'paused' ? 'selected' : '' ?>>متوقفة مؤقتًا</option>
        <option value="ended" <?= $status === 'ended' ? 'selected' : '' ?>>منتهية</option>
      </select>
    </div>
    <div class="form-group"><label>اسم المسؤول / وسيلة التواصل (اختياري)</label><input class="form-control" name="contact_name" value="<?= View::e($gathering['contact_name'] ?? '') ?>"></div>
  </div>

  <div class="form-group"><label>ملاحظات</label><textarea class="form-control" name="notes" rows="2"><?= View::e($gathering['notes'] ?? '') ?></textarea></div>

  <button class="btn btn-primary" type="submit"><?= $isEdit ? 'حفظ التعديلات' : 'إضافة' ?></button>
</form>
