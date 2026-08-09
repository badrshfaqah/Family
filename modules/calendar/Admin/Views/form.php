<?php
/** @var array|null $entryRow
 *  @var array $cities
 *  @var array $events
 */
use Core\Media;
use Core\Support\Csrf;
use Core\Support\Url;
use Core\View;
use Modules\Calendar\Admin\Controllers\CalendarAdminController;

$isEdit = $entryRow !== null;
$action = $isEdit ? Url::admin('calendar/' . $entryRow['id'] . '/update') : Url::admin('calendar/store');
$currentType = $entryRow['entry_type'] ?? '';
$isKnownType = in_array($currentType, CalendarAdminController::TYPES, true);

function fam_calendar_media_thumb($mediaId)
{
    if (!$mediaId) return null;
    $row = \Core\Database::fetchOne('SELECT stored_path FROM ' . \Core\Database::table('media') . ' WHERE id = ?', [$mediaId]);
    return $row ? Media::url($row['stored_path']) : null;
}
?>
<form method="post" action="<?= $action ?>" class="card-box" enctype="multipart/form-data">
  <?= Csrf::field() ?>
  <div class="form-group"><label>عنوان الموعد</label><input class="form-control" name="title" value="<?= View::e($entryRow['title'] ?? '') ?>" required></div>

  <div class="form-row cols-2">
    <div class="form-group">
      <label>النوع</label>
      <select class="form-control" name="entry_type">
        <?php foreach (CalendarAdminController::TYPES as $t): ?>
          <option value="<?= View::e($t) ?>" <?= $currentType === $t ? 'selected' : '' ?>><?= View::e($t) ?></option>
        <?php endforeach; ?>
        <option value="مخصص" <?= (!$isKnownType && $currentType !== '') ? 'selected' : '' ?>>نوع آخر (مخصص)</option>
      </select>
    </div>
    <div class="form-group"><label>نوع مخصص (عند اختيار "مخصص")</label><input class="form-control" name="entry_type_custom" value="<?= View::e(!$isKnownType ? $currentType : '') ?>"></div>
  </div>

  <div class="form-row cols-2">
    <div class="form-group"><label>التاريخ</label><input class="form-control" type="date" name="entry_date" value="<?= View::e($entryRow['entry_datetime'] ?? '' ? date('Y-m-d', strtotime($entryRow['entry_datetime'])) : '') ?>" required></div>
  </div>

  <div class="form-row cols-3">
    <div class="form-group">
      <label>المدينة</label>
      <select class="form-control" name="city_id">
        <option value="">بدون مدينة</option>
        <?php foreach ($cities as $c): ?>
          <option value="<?= $c['id'] ?>" <?= ($entryRow['city_id'] ?? null) == $c['id'] ? 'selected' : '' ?>><?= View::e($c['name']) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="form-group"><label>اسم المكان</label><input class="form-control" name="venue_name" value="<?= View::e($entryRow['venue_name'] ?? '') ?>"></div>
    <div class="form-group"><label>رابط خرائط جوجل (اختياري)</label><input class="form-control" dir="ltr" name="maps_url" value="<?= View::e($entryRow['maps_url'] ?? '') ?>"></div>
  </div>

  <div class="form-group"><label>ملاحظات مختصرة</label><textarea class="form-control" name="notes" rows="2"><?= View::e($entryRow['notes'] ?? '') ?></textarea></div>

  <div class="form-row cols-2">
    <div class="form-group">
      <label>بوستر المناسبة (اختياري)</label>
      <?php $cover = fam_calendar_media_thumb($entryRow['cover_media_id'] ?? null); if ($cover): ?><img src="<?= View::e($cover) ?>" style="height:70px;border-radius:8px;margin-bottom:8px"><?php endif; ?>
      <input class="form-control" type="file" name="cover" accept="image/*">
      <div class="form-hint">يظهر أعلى صفحة المناسبة وفي معاينة الرابط عند المشاركة.</div>
    </div>
    <div class="form-group">
      <label>صورة صاحب المناسبة (اختياري)</label>
      <?php $personThumb = fam_calendar_media_thumb($entryRow['person_media_id'] ?? null); if ($personThumb): ?><img src="<?= View::e($personThumb) ?>" style="height:70px;border-radius:8px;margin-bottom:8px"><?php endif; ?>
      <input class="form-control" type="file" name="person_photo" accept="image/*">
      <div class="form-hint">صورة العريس أو الخريج مثلًا — تظهر داخل البطاقة المولّدة عند "المشاركة كصورة".</div>
    </div>
  </div>

  <div class="form-row cols-2">
    <?php if ($events): ?>
    <div class="form-group">
      <label>ربط بصفحة مناسبة موجودة (اختياري)</label>
      <select class="form-control" name="event_id">
        <option value="">بدون ربط</option>
        <?php foreach ($events as $e): ?>
          <option value="<?= $e['id'] ?>" <?= ($entryRow['event_id'] ?? null) == $e['id'] ? 'selected' : '' ?>><?= View::e($e['title']) ?></option>
        <?php endforeach; ?>
      </select>
      <div class="form-hint">يمكن ترك هذا الحقل فارغًا وإضافة أو ربط صفحة المناسبة لاحقًا.</div>
    </div>
    <?php else: ?>
      <div class="form-group"><div class="form-hint">إضافة "المناسبات" غير مثبتة حاليًا، فلا يمكن الربط بصفحة مناسبة كاملة.</div></div>
    <?php endif; ?>
  </div>

  <div class="form-group">
    <label>الحالة</label>
    <?php $status = $entryRow['status'] ?? 'draft'; ?>
    <select class="form-control" name="status">
      <option value="draft" <?= $status === 'draft' ? 'selected' : '' ?>>مسودة</option>
      <option value="published" <?= $status === 'published' ? 'selected' : '' ?>>منشور</option>
      <option value="cancelled" <?= $status === 'cancelled' ? 'selected' : '' ?>>ملغى</option>
    </select>
  </div>

  <button class="btn btn-primary" type="submit"><?= $isEdit ? 'حفظ التعديلات' : 'إضافة' ?></button>
</form>
