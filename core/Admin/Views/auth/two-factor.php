<?php
use Core\Settings;
use Core\Support\Csrf;
use Core\Support\Url;
use Core\View;
?><!doctype html>
<html lang="ar" dir="rtl">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>التحقق بخطوتين — لوحة التحكم</title>
<meta name="robots" content="noindex, nofollow">
<link rel="stylesheet" href="<?= Url::to('admin/assets/css/admin.css') ?>?v=<?= CORE_VERSION ?>">
</head>
<body>
<div class="login-wrap"><div class="login-box">
  <h1>التحقق بخطوتين</h1>
  <p class="form-hint">أدخل الرمز المكوّن من 6 أرقام من تطبيق المصادقة، أو أحد رموز الاسترداد.</p>
  <?php if (!empty($error)): ?><div class="alert alert-error"><?= View::e($error) ?></div><?php endif; ?>
  <form method="post" action="<?= Url::admin('login/two-factor') ?>">
    <?= Csrf::field() ?>
    <div class="form-group"><label>رمز التحقق</label><input class="form-control" name="code" inputmode="numeric" autocomplete="one-time-code" required autofocus></div>
    <button class="btn btn-primary" type="submit" style="width:100%">تحقق ودخول</button>
  </form>
</div></div>
</body></html>
