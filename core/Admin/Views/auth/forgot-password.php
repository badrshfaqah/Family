<?php
/** @var bool $sent */
use Core\Settings;
use Core\Support\Csrf;
use Core\Support\Url;
use Core\View;
?><!doctype html>
<html lang="ar" dir="rtl">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>نسيت كلمة المرور — لوحة التحكم</title>
<meta name="robots" content="noindex, nofollow">
<link rel="stylesheet" href="<?= Url::to('admin/assets/css/admin.css') ?>">
</head>
<body>
<div class="login-wrap">
  <div class="login-box">
    <h1>استعادة كلمة المرور</h1>

    <?php if ($sent): ?>
      <div class="alert alert-success">إذا كان البريد الإلكتروني مسجلًا لحساب إداري نشط، فسيصلك رابط لإعادة تعيين كلمة المرور خلال دقائق.</div>
      <p style="text-align:center;margin-top:10px"><a class="btn-link" href="<?= Url::admin('login') ?>">العودة لتسجيل الدخول</a></p>
    <?php else: ?>
      <p class="form-hint" style="margin-bottom:14px">أدخل البريد الإلكتروني المسجل لحسابك الإداري، وسنرسل لك رابطًا لإعادة تعيين كلمة المرور.</p>
      <form method="post" action="<?= Url::admin('forgot-password') ?>">
        <?= Csrf::field() ?>
        <div class="form-group">
          <label>البريد الإلكتروني</label>
          <input class="form-control" type="email" name="email" required autofocus>
        </div>
        <button class="btn btn-primary" type="submit" style="width:100%">إرسال رابط الاستعادة</button>
      </form>
      <p style="text-align:center;margin-top:14px"><a class="btn-link" href="<?= Url::admin('login') ?>">العودة لتسجيل الدخول</a></p>
    <?php endif; ?>
  </div>
</div>
</body>
</html>
