<?php
/** @var array $settings */
use Core\Support\Csrf;
use Core\Support\Url;
use Core\Terms;
use Core\View;
include __DIR__ . '/_tabs.php';
?>
<form method="post" action="<?= Url::admin('settings/directory/save') ?>" class="card-box">
  <?= Csrf::field() ?>
  <div class="form-group">
    <label>نص الموافقة الظاهر في نموذج <?= View::e(Terms::phrase('family_directory_register')) ?></label>
    <textarea class="form-control" name="directory_consent_text" rows="3"><?= View::e($settings['directory_consent_text'] ?: Terms::phrase('consent_directory')) ?></textarea>
  </div>
  <div class="switch-row"><span>إظهار عدد الأرقام المسجلة في الصفحة الرئيسية (دون عرض أي رقم أو اسم)</span>
    <input type="checkbox" name="directory_show_public_count" <?= ($settings['directory_show_public_count'] ?? '1') === '1' ? 'checked' : '' ?>></div>
  <div class="switch-row"><span>تفعيل حقل المدينة في نموذج التسجيل</span>
    <input type="checkbox" name="directory_city_enabled" <?= ($settings['directory_city_enabled'] ?? '1') === '1' ? 'checked' : '' ?>></div>
  <div class="switch-row"><span>منع تسجيل نفس رقم الجوال أكثر من مرة</span>
    <input type="checkbox" name="directory_prevent_duplicates" <?= ($settings['directory_prevent_duplicates'] ?? '1') === '1' ? 'checked' : '' ?>></div>
  <div class="switch-row"><span>تفعيل نظام الفروع (يظهر تلقائيًا عند إضافة أول فرع أيضًا)</span>
    <input type="checkbox" name="branches_enabled" <?= ($settings['branches_enabled'] ?? '0') === '1' ? 'checked' : '' ?>></div>
  <br>
  <button class="btn btn-primary" type="submit">حفظ</button>
</form>
