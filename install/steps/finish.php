<?php
use Core\Support\Csrf;
use Core\Support\Security;
?>
<p>البيانات جاهزة. اضغط الزر أدناه لإتمام التثبيت: سيتم زراعة الإعدادات الافتراضية، إنشاء حساب المدير، وحفظ ملف الإعدادات بصورة آمنة.</p>

<div class="card-box">
  <table>
    <tr><td>الاسم الرسمي</td><td><?= Security::e($state['site']['officialName'] ?? '') ?></td></tr>
    <tr><td>قاعدة البيانات</td><td><?= Security::e($state['db']['dbName'] ?? '') ?></td></tr>
    <tr><td>اسم المستخدم الإداري</td><td><?= Security::e($state['admin']['username'] ?? '') ?></td></tr>
    <tr><td>البريد الإلكتروني</td><td><?= Security::e($state['admin']['email'] ?? '') ?></td></tr>
  </table>
</div>

<form method="post">
  <?= Csrf::field() ?>
  <button class="btn btn-primary" type="submit" style="width:100%">إتمام التثبيت</button>
</form>
