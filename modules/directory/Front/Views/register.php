<?php
/** @var array $captcha
 *  @var string $consentText
 *  @var bool $cityEnabled
 *  @var bool $branchesEnabled
 *  @var array $cities
 *  @var array $branches
 *  @var string|null $success
 *  @var string|null $error
 */
use Core\Support\Csrf;
use Core\Support\Url;
use Core\Terms;
use Core\View;
?>
<div class="container section">
  <div class="breadcrumbs"><a href="<?= Url::to('') ?>">الرئيسية</a> / <?= View::e(Terms::phrase('family_directory_register')) ?></div>

  <div class="reg-hero">
    <span class="reg-icon">📱</span>
    <h1><?= View::e(Terms::phrase('family_directory_register')) ?></h1>
    <p>خلّك قريب من أهلك.. سجّل رقمك ويوصلك كل جديد أولًا بأول</p>
    <div class="reg-perks">
      <span>📅 المناسبات</span>
      <span>☕ الجمعات</span>
      <span>📰 الأخبار</span>
    </div>
  </div>

  <div class="reg-wrap">
    <?php if ($success): ?><div class="alert alert-success"><?= View::e($success) ?></div><?php endif; ?>
    <?php if ($error): ?><div class="alert alert-error"><?= View::e($error) ?></div><?php endif; ?>
  </div>

  <form method="post" action="<?= Url::to('directory/register') ?>" class="reg-card">
    <?= Csrf::field() ?>

    <div class="form-group">
      <label>الاسم</label>
      <input class="form-control" type="text" name="name" required>
    </div>

    <div class="form-group">
      <label>رقم الجوال</label>
      <input class="form-control" dir="ltr" type="tel" name="phone" placeholder="05xxxxxxxx" required>
    </div>

    <div class="form-group">
      <label>تأكيد رقم الجوال</label>
      <input class="form-control" dir="ltr" type="tel" name="phone_confirm" placeholder="05xxxxxxxx" required>
    </div>

    <?php if ($cityEnabled && !empty($cities)): ?>
    <div class="form-group">
      <label>المدينة (اختياري)</label>
      <select class="form-control" name="city_id">
        <option value="">— اختر —</option>
        <?php foreach ($cities as $c): ?>
          <option value="<?= (int) $c['id'] ?>"><?= View::e($c['name']) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <?php endif; ?>

    <?php if ($branchesEnabled && !empty($branches)): ?>
    <div class="form-group">
      <label>الفرع (اختياري)</label>
      <select class="form-control" name="branch_id">
        <option value="">— اختر —</option>
        <?php foreach ($branches as $b): ?>
          <option value="<?= (int) $b['id'] ?>"><?= View::e($b['name']) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <?php endif; ?>

    <div class="form-group">
      <label>البريد الإلكتروني (اختياري)</label>
      <input class="form-control" dir="ltr" type="email" name="email">
    </div>

    <div class="form-group">
      <label style="display:flex;gap:8px;align-items:flex-start;font-weight:400">
        <input type="checkbox" name="consent" value="1" required style="margin-top:4px">
        <span><?= View::e($consentText) ?></span>
      </label>
    </div>

    <div class="form-group">
      <label>كم يساوي <?= (int) $captcha['a'] ?> + <?= (int) $captcha['b'] ?>؟</label>
      <input class="form-control" type="text" name="captcha_answer" inputmode="numeric" autocomplete="off" required>
    </div>

    <button class="btn btn-primary btn-block join-band-btn" type="submit">سجّلني ✨</button>
    <p class="reg-privacy">🔒 بياناتك بأمان — تستخدمها إدارة الموقع فقط لإرسال الأخبار والمناسبات، ولا تُعرض لأي زائر.</p>
  </form>

  <p class="form-hint reg-manage">
    لإلغاء الاشتراك أو تحديث حالة رقمك لاحقًا:
    <a href="<?= Url::to('directory/manage-subscription') ?>">إدارة الاشتراك</a>
  </p>
</div>
