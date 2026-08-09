<?php
/** @var array $captcha
 *  @var string|null $success
 *  @var string|null $error
 */
use Core\Support\Csrf;
use Core\Support\Url;
use Core\View;
?>
<div class="container section">
  <div class="breadcrumbs"><a href="<?= Url::to('') ?>">الرئيسية</a> / راسل الإدارة</div>

  <div class="reg-hero">
    <span class="reg-icon">✉️</span>
    <h1>راسل الإدارة</h1>
    <p>عندك ملاحظة أو استفسار أو تصحيح لمعلومة؟ اكتب لنا — رسالتك تصل الإدارة مباشرة</p>
  </div>

  <div class="reg-wrap">
    <?php if ($success): ?><div class="alert alert-success"><?= View::e($success) ?></div><?php endif; ?>
    <?php if ($error): ?><div class="alert alert-error"><?= View::e($error) ?></div><?php endif; ?>
  </div>

  <form method="post" action="<?= Url::to('contact') ?>" class="reg-card">
    <?= Csrf::field() ?>

    <div class="form-row cols-2" style="display:grid;grid-template-columns:1fr 1fr;gap:12px">
      <div class="form-group">
        <label>اسمك</label>
        <input class="form-control" name="name" maxlength="100" required>
      </div>
      <div class="form-group">
        <label>رقم جوالك (اختياري)</label>
        <input class="form-control" dir="ltr" type="tel" name="phone" placeholder="05xxxxxxxx">
      </div>
    </div>

    <div class="form-group">
      <label>الموضوع (اختياري)</label>
      <input class="form-control" name="subject" maxlength="150" placeholder="مثال: تصحيح معلومة في شجرة النسب">
    </div>

    <div class="form-group">
      <label>رسالتك</label>
      <textarea class="form-control" name="message" rows="6" maxlength="3000" required placeholder="اكتب رسالتك هنا..."></textarea>
    </div>

    <div class="form-group">
      <label>كم يساوي <?= (int) $captcha['a'] ?> + <?= (int) $captcha['b'] ?>؟</label>
      <input class="form-control" type="text" name="captcha_answer" inputmode="numeric" autocomplete="off" required>
    </div>

    <button class="btn btn-primary btn-block join-band-btn" type="submit">إرسال الرسالة 📨</button>
    <p class="reg-privacy">🔒 رسالتك تصل الإدارة فقط ولا تُعرض لأي زائر.</p>
  </form>
</div>
