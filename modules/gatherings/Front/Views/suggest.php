<?php
/** @var array $cities
 *  @var array $captcha
 *  @var string|null $success
 *  @var string|null $error
 */
use Core\Support\Csrf;
use Core\Support\Url;
use Core\View;
?>
<div class="container section">
  <div class="breadcrumbs"><a href="<?= Url::to('') ?>">الرئيسية</a> / <a href="<?= Url::to('gatherings') ?>">الجمعات</a> / أضف جمعة</div>

  <div class="reg-hero">
    <span class="reg-icon">☕</span>
    <h1>أضف جمعة</h1>
    <p>عندكم جمعة دورية أو ديوانية؟ اقترحها هنا وستظهر في صفحة الجمعات فور اعتماد الإدارة</p>
  </div>

  <div class="reg-wrap">
    <?php if ($success): ?><div class="alert alert-success"><?= View::e($success) ?></div><?php endif; ?>
    <?php if ($error): ?><div class="alert alert-error"><?= View::e($error) ?></div><?php endif; ?>
  </div>

  <form method="post" action="<?= Url::to('gatherings/suggest') ?>" class="reg-card">
    <?= Csrf::field() ?>

    <div class="form-group">
      <label>اسم الجمعة</label>
      <input class="form-control" name="title" maxlength="150" placeholder="مثال: جمعة الخميس الأسبوعية" required>
    </div>

    <div class="form-row cols-2" style="display:grid;grid-template-columns:1fr 1fr;gap:12px">
      <div class="form-group">
        <label>موعدها الدوري</label>
        <input class="form-control" name="recurrence_label" maxlength="150" placeholder="مثال: كل خميس بعد المغرب" required>
      </div>
      <div class="form-group">
        <label>وقت البداية (اختياري)</label>
        <input class="form-control" type="time" name="start_time">
      </div>
    </div>

    <?php if (!empty($cities)): ?>
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

    <div class="form-group">
      <label>مكان الجمعة (اختياري)</label>
      <input class="form-control" name="venue" maxlength="150" placeholder="مثال: مجلس آل فلان — حي النخيل">
    </div>

    <div class="form-group">
      <label>وصف مختصر (اختياري)</label>
      <textarea class="form-control" name="description" rows="2" placeholder="من يحضرها؟ وش برنامجها؟"></textarea>
    </div>

    <div class="form-row cols-2" style="display:grid;grid-template-columns:1fr 1fr;gap:12px">
      <div class="form-group">
        <label>اسمك</label>
        <input class="form-control" name="submitted_name" maxlength="100" required>
      </div>
      <div class="form-group">
        <label>رقم جوالك</label>
        <input class="form-control" dir="ltr" type="tel" name="submitted_phone" placeholder="05xxxxxxxx" required>
      </div>
    </div>

    <div class="form-group">
      <label>كم يساوي <?= (int) $captcha['a'] ?> + <?= (int) $captcha['b'] ?>؟</label>
      <input class="form-control" type="text" name="captcha_answer" inputmode="numeric" autocomplete="off" required>
    </div>

    <button class="btn btn-primary btn-block join-band-btn" type="submit">إرسال الاقتراح للإدارة 📨</button>
    <p class="reg-privacy">🔒 اسمك وجوالك للإدارة فقط للتحقق والتواصل — لا يظهران للزوار، والجمعة لا تُنشر إلا بعد الاعتماد.</p>
  </form>
</div>
