<?php
/** @var array|null $item */
use Core\Support\Csrf;
use Core\Support\Url;
use Core\View;
use Modules\Announcements\Admin\Controllers\AnnouncementsAdminController;

$isEdit = $item !== null;
$action = $isEdit ? Url::admin('announcements/' . $item['id'] . '/update') : Url::admin('announcements/store');
$currentType = $item['announcement_type'] ?? '';
$isKnownType = in_array($currentType, AnnouncementsAdminController::TYPES, true);
$placement = $item['placement'] ?? 'home_card';
$popupFrequency = $item['popup_frequency'] ?? 'once';

function fam_datetime_local_a($value)
{
    return $value ? date('Y-m-d\TH:i', strtotime($value)) : '';
}
?>
<form method="post" action="<?= $action ?>" class="card-box">
  <?= Csrf::field() ?>
  <div class="form-group"><label>عنوان الإعلان</label><input class="form-control" name="title" value="<?= View::e($item['title'] ?? '') ?>" required></div>
  <div class="form-group"><label>نص الإعلان</label><textarea class="form-control" name="message" rows="4" required><?= View::e($item['message'] ?? '') ?></textarea></div>

  <div class="form-row cols-2">
    <div class="form-group">
      <label>نوع الإعلان</label>
      <select class="form-control" name="announcement_type">
        <?php foreach (AnnouncementsAdminController::TYPES as $t): ?>
          <option value="<?= View::e($t) ?>" <?= $currentType === $t ? 'selected' : '' ?>><?= View::e($t) ?></option>
        <?php endforeach; ?>
        <option value="مخصص" <?= (!$isKnownType && $currentType !== '') ? 'selected' : '' ?>>نوع آخر (مخصص)</option>
      </select>
    </div>
    <div class="form-group"><label>نوع مخصص (عند اختيار "مخصص")</label><input class="form-control" name="announcement_type_custom" value="<?= View::e(!$isKnownType ? $currentType : '') ?>"></div>
  </div>

  <div class="form-row cols-2">
    <div class="form-group">
      <label>مكان العرض</label>
      <select class="form-control" name="placement" id="placement-select">
        <?php foreach (AnnouncementsAdminController::PLACEMENTS as $p): ?>
          <option value="<?= $p ?>" <?= $placement === $p ? 'selected' : '' ?>><?= View::e(AnnouncementsAdminController::PLACEMENT_LABELS[$p]) ?></option>
        <?php endforeach; ?>
      </select>
      <div class="form-hint">ملاحظة: عرض "بطاقة في الرئيسية" مفعّل تلقائيًا في الصفحة الرئيسية. أما الشريط العلوي والنافذة المنبثقة والعرض داخل صفحة محددة فتُحفظ بياناتها هنا تمهيدًا لربطها لاحقًا بقالب الموقع.</div>
    </div>
    <div class="form-group"><label>رابط صفحة محددة (لعرض "داخل صفحة محددة")</label><input class="form-control" dir="ltr" name="target_page_slug" value="<?= View::e($item['target_page_slug'] ?? '') ?>" placeholder="مثال: about"></div>
  </div>

  <div class="form-row cols-3">
    <div class="form-group">
      <label>الحالة</label>
      <?php $status = $item['status'] ?? 'active'; ?>
      <select class="form-control" name="status">
        <option value="active" <?= $status === 'active' ? 'selected' : '' ?>>مفعّل</option>
        <option value="inactive" <?= $status === 'inactive' ? 'selected' : '' ?>>غير مفعّل</option>
      </select>
    </div>
    <div class="form-group"><label>تاريخ ووقت البداية (اختياري)</label><input class="form-control" type="datetime-local" name="starts_at" value="<?= fam_datetime_local_a($item['starts_at'] ?? null) ?>"></div>
    <div class="form-group"><label>تاريخ ووقت النهاية (اختياري)</label><input class="form-control" type="datetime-local" name="ends_at" value="<?= fam_datetime_local_a($item['ends_at'] ?? null) ?>"></div>
  </div>

  <h3 style="font-size:.95rem;margin-top:20px">إعدادات النافذة المنبثقة (عند اختيار "نافذة منبثقة")</h3>
  <div class="form-row cols-3">
    <div class="form-group">
      <label>تكرار الظهور</label>
      <select class="form-control" name="popup_frequency">
        <option value="once" <?= $popupFrequency === 'once' ? 'selected' : '' ?>>مرة واحدة لكل زائر</option>
        <option value="every_visit" <?= $popupFrequency === 'every_visit' ? 'selected' : '' ?>>في كل زيارة</option>
        <option value="interval_days" <?= $popupFrequency === 'interval_days' ? 'selected' : '' ?>>كل عدد أيام محدد</option>
      </select>
    </div>
    <div class="form-group"><label>عدد الأيام (عند اختيار "كل عدد أيام محدد")</label><input class="form-control" type="number" min="1" name="popup_interval_days" value="<?= View::e((string) ($item['popup_interval_days'] ?? '')) ?>"></div>
    <div class="form-group" style="display:flex;gap:16px;align-items:end">
      <label style="display:flex;gap:6px;align-items:center"><input type="checkbox" name="show_on_desktop" <?= ($item === null || !empty($item['show_on_desktop'])) ? 'checked' : '' ?>> عرض على الحاسوب</label>
      <label style="display:flex;gap:6px;align-items:center"><input type="checkbox" name="show_on_mobile" <?= ($item === null || !empty($item['show_on_mobile'])) ? 'checked' : '' ?>> عرض على الجوال</label>
    </div>
  </div>

  <button class="btn btn-primary" type="submit"><?= $isEdit ? 'حفظ التعديلات' : 'إضافة' ?></button>
</form>
