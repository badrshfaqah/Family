<?php
/** @var array|null $eventItem
 *  @var array $cities
 */
use Core\Database;
use Core\Media;
use Core\Support\Csrf;
use Core\Support\Url;
use Core\View;
use Modules\Events\Admin\Controllers\EventsAdminController;

$isEdit = $eventItem !== null;
$action = $isEdit ? Url::admin('events/' . $eventItem['id'] . '/update') : Url::admin('events/store');
$currentType = $eventItem['event_type'] ?? '';
$isKnownType = in_array($currentType, EventsAdminController::TYPES, true);

function fam_event_media_thumb($mediaId)
{
    if (!$mediaId) return null;
    $row = \Core\Database::fetchOne('SELECT stored_path FROM ' . \Core\Database::table('media') . ' WHERE id = ?', [$mediaId]);
    return $row ? Media::url($row['stored_path']) : null;
}

function fam_datetime_local($value)
{
    return $value ? date('Y-m-d\TH:i', strtotime($value)) : '';
}
?>
<form method="post" action="<?= $action ?>" class="card-box" enctype="multipart/form-data">
  <?= Csrf::field() ?>
  <div class="form-row cols-2">
    <div class="form-group"><label>العنوان</label><input class="form-control" name="title" value="<?= View::e($eventItem['title'] ?? '') ?>" required></div>
    <div class="form-group"><label>الرابط المختصر</label><input class="form-control" dir="ltr" name="slug" value="<?= View::e($eventItem['slug'] ?? '') ?>"></div>
  </div>

  <div class="form-row cols-2">
    <div class="form-group">
      <label>نوع المناسبة</label>
      <select class="form-control" name="event_type" id="event-type-select">
        <?php foreach (EventsAdminController::TYPES as $t): ?>
          <option value="<?= View::e($t) ?>" <?= $currentType === $t ? 'selected' : '' ?>><?= View::e($t) ?></option>
        <?php endforeach; ?>
        <option value="مخصص" <?= (!$isKnownType && $currentType !== '') ? 'selected' : '' ?>>نوع آخر (مخصص)</option>
      </select>
    </div>
    <div class="form-group"><label>نوع مخصص (عند اختيار "مخصص")</label><input class="form-control" name="event_type_custom" value="<?= View::e(!$isKnownType ? $currentType : '') ?>"></div>
  </div>

  <div class="form-group"><label>وصف مختصر</label><textarea class="form-control" name="excerpt" rows="2"><?= View::e($eventItem['excerpt'] ?? '') ?></textarea></div>
  <div class="form-group"><label>المحتوى الكامل</label><textarea class="form-control" name="content" rows="10"><?= View::e($eventItem['content'] ?? '') ?></textarea></div>

  <div class="form-row cols-3">
    <div class="form-group">
      <label>المدينة</label>
      <select class="form-control" name="city_id">
        <option value="">بدون مدينة</option>
        <?php foreach ($cities as $c): ?>
          <option value="<?= $c['id'] ?>" <?= ($eventItem['city_id'] ?? null) == $c['id'] ? 'selected' : '' ?>><?= View::e($c['name']) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="form-group"><label>اسم المكان</label><input class="form-control" name="venue_name" value="<?= View::e($eventItem['venue_name'] ?? '') ?>"></div>
    <div class="form-group"><label>رابط خرائط جوجل</label><input class="form-control" dir="ltr" name="maps_url" value="<?= View::e($eventItem['maps_url'] ?? '') ?>"></div>
  </div>

  <div class="form-row cols-2">
    <div class="form-group"><label>تاريخ ووقت البداية</label><input class="form-control" type="datetime-local" name="starts_at" value="<?= fam_datetime_local($eventItem['starts_at'] ?? null) ?>"></div>
    <div class="form-group"><label>تاريخ ووقت النهاية (عند الحاجة)</label><input class="form-control" type="datetime-local" name="ends_at" value="<?= fam_datetime_local($eventItem['ends_at'] ?? null) ?>"></div>
  </div>

  <div class="form-row cols-2">
    <div class="form-group">
      <label>الصورة الرئيسية</label>
      <?php $cover = fam_event_media_thumb($eventItem['cover_media_id'] ?? null); if ($cover): ?><img src="<?= View::e($cover) ?>" style="height:70px;border-radius:8px;margin-bottom:8px"><?php endif; ?>
      <input class="form-control" type="file" name="cover" accept="image/*">
    </div>
    <div class="form-group"><label>معرض صور إضافي (يمكن اختيار عدة صور)</label><input class="form-control" type="file" name="gallery[]" accept="image/*" multiple>
      <?php if (!empty($eventItem['gallery_media_ids'])): ?><div class="form-hint">يوجد <?= count(array_filter(explode(',', $eventItem['gallery_media_ids']))) ?> صورة محفوظة، سيتم إضافة الجديدة إليها.</div><?php endif; ?>
    </div>
  </div>

  <div class="form-row cols-2">
    <div class="form-group"><label>رابط فيديو خارجي</label><input class="form-control" dir="ltr" name="video_url" value="<?= View::e($eventItem['video_url'] ?? '') ?>" placeholder="رابط يوتيوب مثلًا"></div>
    <div class="form-group"><label>رابط Google Drive (ألبوم / ملفات)</label><input class="form-control" dir="ltr" name="drive_url" value="<?= View::e($eventItem['drive_url'] ?? '') ?>"></div>
  </div>

  <div class="form-group"><label>روابط خارجية</label><textarea class="form-control" name="external_links" rows="2"><?= View::e($eventItem['external_links'] ?? '') ?></textarea></div>

  <div class="form-group"><label>مرفقات (ملفات / صور إضافية)</label><input class="form-control" type="file" name="attachments[]" multiple>
    <?php if (!empty($eventItem['attachment_media_ids'])): ?><div class="form-hint">يوجد <?= count(array_filter(explode(',', $eventItem['attachment_media_ids']))) ?> مرفق محفوظ، سيتم إضافة الجديد إليها.</div><?php endif; ?>
  </div>

  <div class="form-row cols-3">
    <div class="form-group">
      <label>الحالة</label>
      <?php $status = $eventItem['status'] ?? 'draft'; ?>
      <select class="form-control" name="status">
        <option value="draft" <?= $status === 'draft' ? 'selected' : '' ?>>مسودة</option>
        <option value="published" <?= $status === 'published' ? 'selected' : '' ?>>منشورة</option>
        <option value="archived" <?= $status === 'archived' ? 'selected' : '' ?>>مؤرشفة</option>
      </select>
    </div>
    <div class="form-group"><label>الوسوم (افصل بفاصلة)</label><input class="form-control" name="tags" value="<?= View::e($eventItem['tags'] ?? '') ?>"></div>
    <div class="form-group" style="display:flex;gap:16px;align-items:end">
      <label style="display:flex;gap:6px;align-items:center"><input type="checkbox" name="is_featured" <?= !empty($eventItem['is_featured']) ? 'checked' : '' ?>> مميزة</label>
      <label style="display:flex;gap:6px;align-items:center"><input type="checkbox" name="is_pinned" <?= !empty($eventItem['is_pinned']) ? 'checked' : '' ?>> مثبتة</label>
    </div>
  </div>

  <h3 style="font-size:.95rem;margin-top:20px">إعدادات SEO</h3>
  <div class="form-group"><label>عنوان SEO</label><input class="form-control" name="seo_title" value="<?= View::e($eventItem['seo_title'] ?? '') ?>"></div>
  <div class="form-group"><label>وصف SEO</label><textarea class="form-control" name="seo_description" rows="2"><?= View::e($eventItem['seo_description'] ?? '') ?></textarea></div>

  <button class="btn btn-primary" type="submit"><?= $isEdit ? 'حفظ التعديلات' : 'إضافة' ?></button>
</form>
