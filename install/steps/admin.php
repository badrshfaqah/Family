<?php
use Core\Support\Csrf;
use Core\Support\Security;

$admin = $state['admin'] ?? [];
?>
<p>أنشئ أول حساب لمدير النظام. سيملك هذا الحساب جميع الصلاحيات.</p>
<form method="post">
  <?= Csrf::field() ?>
  <div class="form-group"><label>الاسم الكامل</label><input class="form-control" name="admin_name" value="<?= Security::e($admin['name'] ?? '') ?>" required></div>
  <div class="form-row cols-2">
    <div class="form-group"><label>اسم المستخدم</label><input class="form-control" name="admin_username" value="<?= Security::e($admin['username'] ?? '') ?>" required></div>
    <div class="form-group"><label>البريد الإلكتروني</label><input class="form-control" type="email" name="admin_email" value="<?= Security::e($admin['email'] ?? '') ?>" required></div>
  </div>
  <div class="form-row cols-2">
    <div class="form-group"><label>كلمة المرور</label><input class="form-control" type="password" name="admin_password" required minlength="8"></div>
    <div class="form-group"><label>تأكيد كلمة المرور</label><input class="form-control" type="password" name="admin_password_confirm" required minlength="8"></div>
  </div>
  <button class="btn btn-primary" type="submit" style="width:100%">التالي</button>
</form>
