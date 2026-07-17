<?php
/** @var array $captcha
 *  @var string|null $success
 *  @var string|null $error
 */
use Core\Support\Csrf;
use Core\Support\Url;
use Core\Terms;
use Core\View;
?>
<div class="container section">
  <div class="breadcrumbs"><a href="<?= Url::to('') ?>">الرئيسية</a> / إدارة الاشتراك</div>
  <h1>إدارة الاشتراك في <?= View::e(Terms::phrase('family_directory')) ?></h1>
  <p class="form-hint">
    إذا رغبت في إلغاء اشتراك رقم جوالك من قائمة التواصل، أدخل الرقم أدناه. سيصل الطلب إلى إدارة الموقع
    لمراجعته واعتماده، ولن يُحذف أي شيء تلقائيًا.
  </p>

  <?php if ($success): ?><div class="alert alert-success"><?= View::e($success) ?></div><?php endif; ?>
  <?php if ($error): ?><div class="alert alert-error"><?= View::e($error) ?></div><?php endif; ?>

  <form method="post" action="<?= Url::to('directory/manage-subscription') ?>" class="card-box" style="max-width:480px">
    <?= Csrf::field() ?>

    <div class="form-group">
      <label>رقم الجوال المراد إلغاء اشتراكه</label>
      <input class="form-control" dir="ltr" type="tel" name="phone" placeholder="05xxxxxxxx" required>
    </div>

    <div class="form-group">
      <label>كم يساوي <?= (int) $captcha['a'] ?> + <?= (int) $captcha['b'] ?>؟</label>
      <input class="form-control" type="text" name="captcha_answer" inputmode="numeric" autocomplete="off" required>
    </div>

    <button class="btn btn-primary btn-block" type="submit">إرسال طلب إلغاء الاشتراك</button>
  </form>
</div>
