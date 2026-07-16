<?php
/** @var array $old
 *  @var string|null $error
 *  @var string|null $success
 */
use Core\Support\Csrf;
use Core\Support\Url;
use Core\View;
?>
<div class="container section" style="max-width:560px">
  <div class="breadcrumbs"><a href="<?= Url::to('') ?>">الرئيسية</a> / إلغاء الاشتراك</div>
  <h1>إلغاء الاشتراك في القائمة البريدية</h1>
  <p class="form-hint">أدخل بريدك الإلكتروني لإلغاء اشتراكك من القائمة البريدية.</p>

  <?php if (!empty($success)): ?><div class="alert alert-success"><?= View::e($success) ?></div><?php endif; ?>
  <?php if (!empty($error)): ?><div class="alert alert-error"><?= View::e($error) ?></div><?php endif; ?>

  <form method="post" action="<?= Url::to('newsletter/unsubscribe') ?>" class="card-box">
    <?= Csrf::field() ?>
    <div class="form-group">
      <label>البريد الإلكتروني</label>
      <input class="form-control" type="email" name="email" value="<?= View::e($old['email'] ?? '') ?>" required maxlength="191">
    </div>
    <button class="btn btn-primary" type="submit">إلغاء الاشتراك</button>
  </form>
</div>
