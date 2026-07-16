<?php
/** @var string|null $error
 *  @var string $identifier
 */
use Core\Settings;
use Core\Support\Csrf;
use Core\Support\Url;
use Core\View;
?><!doctype html>
<html lang="ar" dir="rtl">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>تسجيل الدخول — لوحة التحكم</title>
<meta name="robots" content="noindex, nofollow">
<link rel="stylesheet" href="<?= Url::to('admin/assets/css/admin.css') ?>">
</head>
<body>
<div class="login-wrap">
  <div class="login-box">
    <h1>لوحة تحكم <?= View::e(Settings::get('identity_short_name', '')) ?></h1>
    <?php if (!empty($error)): ?><div class="alert alert-error"><?= View::e($error) ?></div><?php endif; ?>
    <form method="post" action="<?= Url::admin('login') ?>">
      <?= Csrf::field() ?>
      <div class="form-group">
        <label>اسم المستخدم أو البريد الإلكتروني</label>
        <input class="form-control" type="text" name="identifier" value="<?= View::e($identifier ?? '') ?>" required autofocus>
      </div>
      <div class="form-group">
        <label>كلمة المرور</label>
        <input class="form-control" type="password" name="password" required>
      </div>
      <button class="btn btn-primary" type="submit" style="width:100%">دخول</button>
    </form>
  </div>
</div>
</body>
</html>
