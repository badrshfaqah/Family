<?php
/** @var array $settings */
use Core\Support\Csrf;
use Core\Support\Url;
use Core\View;
include __DIR__ . '/_tabs.php';
?>
<form method="post" action="<?= Url::admin('settings/maintenance/save') ?>" class="card-box">
  <?= Csrf::field() ?>
  <div class="switch-row"><span>تفعيل وضع الصيانة (يبقى وصول لوحة التحكم متاحًا)</span>
    <input type="checkbox" name="maintenance_mode" <?= ($settings['maintenance_mode'] ?? '0') === '1' ? 'checked' : '' ?>></div>
  <div class="form-group" style="margin-top:14px">
    <label>رسالة الصيانة</label>
    <textarea class="form-control" name="maintenance_message" rows="3"><?= View::e($settings['maintenance_message'] ?? '') ?></textarea>
  </div>
  <button class="btn btn-primary" type="submit">حفظ</button>
</form>

<div class="card-box">
  <h2 style="margin-top:0;font-size:1rem">معلومات السيرفر</h2>
  <table>
    <tr><td>إصدار PHP</td><td><?= PHP_VERSION ?></td></tr>
    <tr><td>إصدار النواة</td><td><?= CORE_VERSION ?></td></tr>
    <tr><td>مكتبة GD</td><td><?= extension_loaded('gd') ? 'متوفرة' : 'غير متوفرة' ?></td></tr>
    <tr><td>حد رفع الملفات (upload_max_filesize)</td><td><?= ini_get('upload_max_filesize') ?></td></tr>
  </table>
</div>
