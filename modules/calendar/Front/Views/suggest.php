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
  <div class="breadcrumbs"><a href="<?= Url::to('') ?>">الرئيسية</a> / <a href="<?= Url::to('calendar') ?>">رزنامة المناسبات</a> / أضف مناسبة</div>

  <div class="reg-hero">
    <span class="reg-icon">📅</span>
    <h1>أضف مناسبة للرزنامة</h1>
    <p>عندك زواج أو تخرج أو مناسبة تخص العائلة؟ اقترحها هنا وستظهر في الرزنامة فور اعتماد الإدارة</p>
  </div>

  <div class="reg-wrap">
    <?php if ($success): ?><div class="alert alert-success"><?= View::e($success) ?></div><?php endif; ?>
    <?php if ($error): ?><div class="alert alert-error"><?= View::e($error) ?></div><?php endif; ?>
  </div>

  <form method="post" action="<?= Url::to('calendar/suggest') ?>" class="reg-card">
    <?= Csrf::field() ?>
    <?= \Core\Support\SpamGuard::fields('calendar_suggest') ?>

    <div class="form-group">
      <label>عنوان المناسبة</label>
      <input class="form-control" name="title" maxlength="150" placeholder="مثال: زواج محمد بن فلان" required>
    </div>

    <div class="form-row cols-2" style="display:grid;grid-template-columns:1fr 1fr;gap:12px">
      <div class="form-group">
        <label>نوع المناسبة</label>
        <select class="form-control" name="entry_type" required>
          <option value="">— اختر —</option>
          <?php foreach (['زواج', 'تخرج', 'اجتماع', 'وليمة', 'تكريم', 'أخرى'] as $t): ?>
            <option value="<?= $t ?>"><?= $t ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="form-group">
        <label>التاريخ</label>
        <input class="form-control" type="date" name="entry_datetime" required>
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
      <label>مكان المناسبة (اختياري)</label>
      <input class="form-control" name="venue_name" maxlength="150" placeholder="مثال: قاعة الفهد للاحتفالات">
    </div>

    <div class="form-group">
      <label>تفاصيل إضافية (اختياري)</label>
      <textarea class="form-control" name="notes" rows="3" placeholder="أي تفاصيل تحب توضيحها للإدارة والحضور"></textarea>
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
      <label><?= View::e(\Core\Support\Captcha::question($captcha)) ?></label>
      <input class="form-control" type="text" name="captcha_answer" inputmode="numeric" autocomplete="off" required>
    </div>

    <button class="btn btn-primary btn-block join-band-btn" type="submit">إرسال الاقتراح للإدارة 📨</button>
    <p class="reg-privacy">🔒 اسمك وجوالك للإدارة فقط للتحقق والتواصل — لا يظهران للزوار، والمناسبة لا تُنشر إلا بعد الاعتماد.</p>
  </form>
</div>
