<?php
use Core\Support\Csrf;
use Core\Support\Security;

$db = $state['db'] ?? [];
?>
<p>أدخل بيانات الاتصال بقاعدة بيانات MySQL التي أنشأتها مسبقًا من لوحة تحكم الاستضافة.</p>
<form method="post">
  <?= Csrf::field() ?>
  <div class="form-row cols-2">
    <div class="form-group"><label>اسم السيرفر</label><input class="form-control" name="db_host" value="<?= Security::e($db['dbHost'] ?? 'localhost') ?>" required></div>
    <div class="form-group"><label>المنفذ (اختياري)</label><input class="form-control" name="db_port" value="<?= Security::e($db['dbPort'] ?? '3306') ?>"></div>
  </div>
  <div class="form-group"><label>اسم قاعدة البيانات</label><input class="form-control" name="db_name" value="<?= Security::e($db['dbName'] ?? '') ?>" required></div>
  <div class="form-row cols-2">
    <div class="form-group"><label>اسم المستخدم</label><input class="form-control" name="db_user" value="<?= Security::e($db['dbUser'] ?? '') ?>" required></div>
    <div class="form-group"><label>كلمة المرور</label><input class="form-control" type="password" name="db_pass" value=""></div>
  </div>
  <div class="form-group"><label>بادئة الجداول</label><input class="form-control" name="db_prefix" value="<?= Security::e($db['dbPrefix'] ?? 'fam_') ?>"></div>
  <button class="btn btn-primary" type="submit" style="width:100%">اختبار الاتصال وإنشاء الجداول</button>
</form>
