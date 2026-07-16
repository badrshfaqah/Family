<?php
/** @var array $settings */
use Core\Support\Csrf;
use Core\Support\Url;
use Core\View;
include __DIR__ . '/_tabs.php';
?>
<form method="post" action="<?= Url::admin('settings/mail/save') ?>" class="card-box">
  <?= Csrf::field() ?>
  <div class="form-row cols-2">
    <div class="form-group"><label>اسم المرسل</label><input class="form-control" name="mail_from_name" value="<?= View::e($settings['mail_from_name'] ?? '') ?>"></div>
    <div class="form-group"><label>بريد المرسل</label><input class="form-control" type="email" name="mail_from_email" value="<?= View::e($settings['mail_from_email'] ?? '') ?>"></div>
  </div>

  <p class="form-hint">إعدادات SMTP اختيارية. إن تُركت فارغة، يستخدم النظام دالة الإرسال المدمجة في PHP (تعمل على معظم الاستضافات المشتركة عبر sendmail المحلي). إن كانت رسائلك لا تصل، فعّل SMTP باستخدام بيانات مزود بريدك.</p>

  <div class="form-row cols-2">
    <div class="form-group"><label>خادم SMTP</label><input class="form-control" dir="ltr" name="mail_smtp_host" value="<?= View::e($settings['mail_smtp_host'] ?? '') ?>" placeholder="mail.example.com"></div>
    <div class="form-group"><label>المنفذ</label><input class="form-control" type="number" name="mail_smtp_port" value="<?= View::e($settings['mail_smtp_port'] ?? '587') ?>"></div>
  </div>
  <div class="form-row cols-2">
    <div class="form-group"><label>اسم المستخدم</label><input class="form-control" dir="ltr" name="mail_smtp_user" value="<?= View::e($settings['mail_smtp_user'] ?? '') ?>"></div>
    <div class="form-group"><label>كلمة المرور</label><input class="form-control" type="password" dir="ltr" name="mail_smtp_pass" value="<?= View::e($settings['mail_smtp_pass'] ?? '') ?>"></div>
  </div>
  <div class="form-group" style="max-width:220px">
    <label>نوع التشفير</label>
    <select class="form-control" name="mail_smtp_encryption">
      <option value="tls" <?= ($settings['mail_smtp_encryption'] ?? 'tls') === 'tls' ? 'selected' : '' ?>>TLS (المنفذ 587 غالبًا)</option>
      <option value="ssl" <?= ($settings['mail_smtp_encryption'] ?? '') === 'ssl' ? 'selected' : '' ?>>SSL (المنفذ 465 غالبًا)</option>
      <option value="none" <?= ($settings['mail_smtp_encryption'] ?? '') === 'none' ? 'selected' : '' ?>>بدون تشفير</option>
    </select>
  </div>

  <button class="btn btn-primary" type="submit">حفظ</button>
</form>
