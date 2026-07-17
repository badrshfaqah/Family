<?php
/** @var array $settings */
use Core\Database;
use Core\Support\Csrf;
use Core\Support\Url;
use Core\View;
include __DIR__ . '/_tabs.php';
?>
<form method="post" action="<?= Url::admin('settings/identity/save') ?>" class="card-box" enctype="multipart/form-data">
  <?= Csrf::field() ?>
  <div class="form-row cols-2">
    <div class="form-group">
      <label>الاسم الرسمي</label>
      <input class="form-control" name="identity_official_name" value="<?= View::e($settings['identity_official_name'] ?? '') ?>" required>
    </div>
    <div class="form-group">
      <label>الاسم المختصر</label>
      <input class="form-control" name="identity_short_name" value="<?= View::e($settings['identity_short_name'] ?? '') ?>">
    </div>
  </div>
  <div class="form-row cols-2">
    <div class="form-group">
      <label>الشعار</label>
      <?php if (!empty($settings['identity_logo_media_id'])):
        $logo = Database::fetchOne('SELECT stored_path FROM ' . Database::table('media') . ' WHERE id = ?', [$settings['identity_logo_media_id']]);
        if ($logo): ?><img src="<?= View::e(\Core\Media::url($logo['stored_path'])) ?>" alt="" style="height:60px;border-radius:8px;margin-bottom:8px"><?php endif;
      endif; ?>
      <input class="form-control" type="file" name="identity_logo" accept="image/*">
    </div>
    <div class="form-group">
      <label>صورة الغلاف</label>
      <?php if (!empty($settings['identity_cover_media_id'])):
        $cover = Database::fetchOne('SELECT stored_path FROM ' . Database::table('media') . ' WHERE id = ?', [$settings['identity_cover_media_id']]);
        if ($cover): ?><img src="<?= View::e(\Core\Media::url($cover['stored_path'])) ?>" alt="" style="height:60px;border-radius:8px;margin-bottom:8px"><?php endif;
      endif; ?>
      <input class="form-control" type="file" name="identity_cover" accept="image/*">
    </div>
  </div>
  <div class="form-group">
    <label>نبذة مختصرة</label>
    <textarea class="form-control" name="identity_brief" rows="2"><?= View::e($settings['identity_brief'] ?? '') ?></textarea>
  </div>
  <div class="form-group">
    <label>نبذة موسعة</label>
    <textarea class="form-control" name="identity_extended_bio" rows="5"><?= View::e($settings['identity_extended_bio'] ?? '') ?></textarea>
  </div>
  <div class="form-row cols-2">
    <div class="form-group">
      <label>أصل النسب</label>
      <textarea class="form-control" name="identity_lineage_origin" rows="3"><?= View::e($settings['identity_lineage_origin'] ?? '') ?></textarea>
    </div>
    <div class="form-group">
      <label>تسلسل النسب</label>
      <textarea class="form-control" name="identity_lineage_sequence" rows="3"><?= View::e($settings['identity_lineage_sequence'] ?? '') ?></textarea>
      <div class="form-hint">اكتب سلسلة الأجداد كما تحب أن تظهر، جد بعد جد.</div>
    </div>
  </div>
  <div class="form-group">
    <label>كلمة أو رسالة ترحيبية</label>
    <textarea class="form-control" name="identity_welcome_message" rows="2"><?= View::e($settings['identity_welcome_message'] ?? '') ?></textarea>
  </div>
  <div class="form-group">
    <label>ملاحظة توثيق (بديل عن تاريخ التأسيس، اختياري)</label>
    <input class="form-control" name="identity_documentation_note" value="<?= View::e($settings['identity_documentation_note'] ?? '') ?>" placeholder="مثال: تم توثيق هذا الموقع الرسمي عام 1446هـ">
  </div>
  <button class="btn btn-primary" type="submit">حفظ</button>
</form>
