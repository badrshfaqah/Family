<?php
/** @var array $captcha
 *  @var string $consentText
 *  @var array $old
 *  @var string|null $error
 *  @var string|null $success
 */
use Core\Support\Captcha;
use Core\Support\Csrf;
use Core\Support\Url;
use Core\View;
?>
<div class="container section" style="max-width:560px">
  <div class="breadcrumbs"><a href="<?= Url::to('') ?>">الرئيسية</a> / القائمة البريدية</div>
  <h1>الاشتراك في القائمة البريدية</h1>
  <p class="form-hint">اشترك لتصلك آخر الأخبار والمناسبات عبر البريد الإلكتروني.</p>

  <?php if (!empty($success)): ?><div class="alert alert-success"><?= View::e($success) ?></div><?php endif; ?>
  <?php if (!empty($error)): ?><div class="alert alert-error"><?= View::e($error) ?></div><?php endif; ?>

  <form method="post" action="<?= Url::to('newsletter/subscribe') ?>" class="card-box">
    <?= Csrf::field() ?>

    <div class="form-group">
      <label>الاسم (اختياري)</label>
      <input class="form-control" type="text" name="name" value="<?= View::e($old['name'] ?? '') ?>" maxlength="150">
    </div>

    <div class="form-group">
      <label>البريد الإلكتروني</label>
      <input class="form-control" type="email" name="email" value="<?= View::e($old['email'] ?? '') ?>" required maxlength="191">
    </div>

    <div class="form-group">
      <label style="display:flex;gap:8px;align-items:flex-start;font-weight:400">
        <input type="checkbox" name="consent" value="1" required style="margin-top:4px">
        <span><?= View::e($consentText) ?></span>
      </label>
    </div>

    <div class="form-group">
      <label>رمز التحقق</label>
      <?php if (Captcha::isImageSupported()): ?>
        <div style="margin-bottom:8px">
          <img src="<?= View::e(Url::to('captcha.png') . '?a=' . (int) $captcha['a'] . '&b=' . (int) $captcha['b']) ?>" alt="رمز التحقق" width="150" height="50">
        </div>
      <?php else: ?>
        <div class="form-hint" style="margin-bottom:8px"><?= (int) $captcha['a'] ?> + <?= (int) $captcha['b'] ?> = ؟</div>
      <?php endif; ?>
      <input class="form-control" type="text" name="captcha_answer" inputmode="numeric" required placeholder="ناتج الجمع">
    </div>

    <button class="btn btn-primary" type="submit">اشتراك</button>
  </form>

  <p class="form-hint" style="margin-top:16px">
    اشتركت سابقًا وتريد إلغاء الاشتراك؟ <a href="<?= Url::to('newsletter/unsubscribe') ?>">اضغط هنا</a>.
  </p>
</div>
