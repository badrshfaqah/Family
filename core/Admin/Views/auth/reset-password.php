<?php
/** @var string $token
 *  @var bool $valid
 *  @var string|null $error
 */
use Core\Support\Csrf;
use Core\Support\Url;
use Core\View;
?><!doctype html>
<html lang="ar" dir="rtl">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>تعيين كلمة مرور جديدة — لوحة التحكم</title>
<meta name="robots" content="noindex, nofollow">
<link rel="stylesheet" href="<?= Url::to('admin/assets/css/admin.css') ?>">
</head>
<body>
<div class="login-wrap">
  <div class="login-box">
    <h1>تعيين كلمة مرور جديدة</h1>
    <?php if (!empty($error)): ?><div class="alert alert-error"><?= View::e($error) ?></div><?php endif; ?>

    <?php if (!$valid): ?>
      <?php if (empty($error)): ?><div class="alert alert-error">رابط إعادة التعيين غير صالح أو منتهي الصلاحية.</div><?php endif; ?>
      <p style="text-align:center;margin-top:10px"><a class="btn-link" href="<?= Url::admin('forgot-password') ?>">طلب رابط جديد</a></p>
    <?php else: ?>
      <form method="post" action="<?= Url::admin('reset-password/' . $token) ?>">
        <?= Csrf::field() ?>
        <div class="form-group">
          <label>كلمة المرور الجديدة</label>
          <input class="form-control" type="password" name="password" required minlength="8" autofocus>
        </div>
        <div class="form-group">
          <label>تأكيد كلمة المرور</label>
          <input class="form-control" type="password" name="password_confirm" required minlength="8">
        </div>
        <button class="btn btn-primary" type="submit" style="width:100%">تعيين كلمة المرور</button>
      </form>
    <?php endif; ?>
  </div>
</div>
</body>
</html>
