<?php
/** @var array $settings */
use Core\Support\Csrf;
use Core\Support\Url;
use Core\View;
include __DIR__ . '/_tabs.php';
?>
<form method="post" action="<?= Url::admin('settings/contact/save') ?>" class="card-box">
  <?= Csrf::field() ?>
  <div class="form-row cols-2">
    <div class="form-group"><label>رقم التواصل الرسمي</label><input class="form-control" name="contact_phone" value="<?= View::e($settings['contact_phone'] ?? '') ?>"></div>
    <div class="form-group"><label>البريد الإلكتروني الرسمي</label><input class="form-control" type="email" name="contact_email" value="<?= View::e($settings['contact_email'] ?? '') ?>"></div>
  </div>
  <div class="form-row cols-3">
    <div class="form-group"><label>واتساب</label><input class="form-control" name="social_whatsapp" value="<?= View::e($settings['social_whatsapp'] ?? '') ?>" placeholder="رابط https://wa.me/..."></div>
    <div class="form-group"><label>X (تويتر)</label><input class="form-control" name="social_x" value="<?= View::e($settings['social_x'] ?? '') ?>"></div>
    <div class="form-group"><label>إنستغرام</label><input class="form-control" name="social_instagram" value="<?= View::e($settings['social_instagram'] ?? '') ?>"></div>
  </div>
  <div class="form-row cols-3">
    <div class="form-group"><label>سناب شات</label><input class="form-control" name="social_snapchat" value="<?= View::e($settings['social_snapchat'] ?? '') ?>"></div>
    <div class="form-group"><label>يوتيوب</label><input class="form-control" name="social_youtube" value="<?= View::e($settings['social_youtube'] ?? '') ?>"></div>
    <div class="form-group"><label>تيليجرام</label><input class="form-control" name="social_telegram" value="<?= View::e($settings['social_telegram'] ?? '') ?>"></div>
  </div>
  <button class="btn btn-primary" type="submit">حفظ</button>
</form>
