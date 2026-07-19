<?php
use Core\Support\Csrf;
use Core\Support\Url;
use Core\View;
?>
<?php if (!empty($recoveryCodes)): ?>
<div class="alert alert-success"><strong>احفظ هذه الرموز الآن؛ لن تظهر مرة أخرى:</strong><br><code dir="ltr"><?= View::e(implode('  ', $recoveryCodes)) ?></code></div>
<?php endif; ?>

<?php if ($enabled): ?>
<div class="card-box">
  <h2 style="margin-top:0">المصادقة الثنائية مفعّلة</h2>
  <p>سيُطلب رمز إضافي بعد كلمة المرور عند كل تسجيل دخول جديد.</p>
  <form method="post" action="<?= Url::admin('two-factor/disable') ?>">
    <?= Csrf::field() ?>
    <div class="form-group"><label>رمز التطبيق أو رمز استرداد للتعطيل</label><input class="form-control" name="code" required autocomplete="one-time-code"></div>
    <button class="btn btn-danger" type="submit" data-confirm="هل تريد تعطيل المصادقة الثنائية؟">تعطيل المصادقة الثنائية</button>
  </form>
</div>
<?php else: ?>
<div class="card-box">
  <h2 style="margin-top:0">تفعيل المصادقة الثنائية</h2>
  <ol>
    <li>افتح تطبيق Authenticator وأضف حسابًا يدويًا.</li>
    <li>اختر مفتاحًا زمنيًا TOTP من 6 أرقام كل 30 ثانية.</li>
    <li>أدخل المفتاح التالي: <code dir="ltr"><?= View::e($secret) ?></code></li>
  </ol>
  <details><summary>رابط الإعداد المتقدم</summary><code dir="ltr" style="overflow-wrap:anywhere"><?= View::e($provisioningUri) ?></code></details>
  <form method="post" action="<?= Url::admin('two-factor/enable') ?>" style="margin-top:18px">
    <?= Csrf::field() ?>
    <div class="form-group"><label>رمز التطبيق للتأكيد</label><input class="form-control" name="code" inputmode="numeric" autocomplete="one-time-code" required></div>
    <button class="btn btn-primary" type="submit">تفعيل</button>
  </form>
</div>
<?php endif; ?>
