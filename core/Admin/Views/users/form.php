<?php
/** @var array $roles
 *  @var array|null $errors
 */
use Core\Support\Csrf;
use Core\Support\Url;
use Core\View;
?>
<form method="post" action="<?= Url::admin('users/store') ?>" class="card-box" style="max-width:520px">
  <?= Csrf::field() ?>
  <?php if (!empty($errors)): foreach ($errors as $e): ?><div class="alert alert-error"><?= View::e($e) ?></div><?php endforeach; endif; ?>
  <div class="form-group"><label>الاسم</label><input class="form-control" name="name" required></div>
  <div class="form-group"><label>اسم المستخدم</label><input class="form-control" name="username" required></div>
  <div class="form-group"><label>البريد الإلكتروني</label><input class="form-control" type="email" name="email" required></div>
  <div class="form-group"><label>كلمة المرور</label><input class="form-control" type="password" name="password" required minlength="8"></div>
  <div class="form-group">
    <label>الدور</label>
    <select class="form-control" name="role_id" required>
      <?php foreach ($roles as $r): ?><option value="<?= $r['id'] ?>"><?= View::e($r['name']) ?></option><?php endforeach; ?>
    </select>
  </div>
  <button class="btn btn-primary" type="submit">إضافة</button>
</form>
