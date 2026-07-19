<?php
/** @var string $pushPublicKey
 *  @var string $pushCsrfToken
 */
use Core\Support\Url;
use Core\View;
?>
<div class="container section">
  <div class="breadcrumbs"><a href="<?= Url::to('') ?>">الرئيسية</a> / إضافة الموقع للشاشة الرئيسية</div>

  <div class="reg-hero">
    <span class="reg-icon">📲</span>
    <h1>أضف الموقع لشاشتك الرئيسية</h1>
    <p>ثبّت الموقع كتطبيق على جوالك: أيقونة على الشاشة، فتح بملء الشاشة، وتنبيهات بكل جديد</p>
  </div>

  <div class="install-cards">

    <div class="install-card" data-android-install hidden>
      <div class="install-card-head"><span>⚡</span><b>تثبيت مباشر</b></div>
      <p>متصفحك يدعم التثبيت بضغطة واحدة:</p>
      <button class="btn btn-primary btn-block" data-a2hs-install-page>📲 ثبّت التطبيق الآن</button>
    </div>

    <div class="install-card" data-ios-guide>
      <div class="install-card-head"><span>🍎</span><b>آيفون وآيباد (Safari)</b></div>
      <ol class="install-steps">
        <li>افتح الموقع في متصفح <b>Safari</b></li>
        <li>اضغط زر المشاركة <span class="install-key">⬆️</span> أسفل الشاشة</li>
        <li>مرّر للأسفل واختر <span class="install-key">إضافة إلى الشاشة الرئيسية ➕</span></li>
        <li>اضغط <b>إضافة</b> — وستجد أيقونة الموقع على شاشتك</li>
      </ol>
    </div>

    <div class="install-card" data-android-guide>
      <div class="install-card-head"><span>🤖</span><b>أندرويد (Chrome)</b></div>
      <ol class="install-steps">
        <li>افتح الموقع في متصفح <b>Chrome</b></li>
        <li>اضغط قائمة النقاط <span class="install-key">⋮</span> أعلى الشاشة</li>
        <li>اختر <span class="install-key">إضافة إلى الشاشة الرئيسية</span> أو <span class="install-key">تثبيت التطبيق</span></li>
        <li>أكّد بالضغط على <b>تثبيت</b></li>
      </ol>
    </div>

    <div class="install-card">
      <div class="install-card-head"><span>🔔</span><b>تفعيل التنبيهات</b></div>
      <p>فعّل التنبيهات ليصلك إشعار فوري بالمناسبات والوفيات وكل جديد — حتى والموقع مغلق.</p>
      <?php if ($pushPublicKey !== ''): ?>
        <button class="btn btn-primary btn-block" data-push-enable data-vapid="<?= View::e($pushPublicKey) ?>" data-subscribe-url="<?= Url::to('push/subscribe') ?>" data-csrf-token="<?= View::e($pushCsrfToken) ?>">🔔 تفعيل التنبيهات</button>
        <p class="form-hint" data-push-status style="text-align:center;margin-top:10px"></p>
        <p class="form-hint" style="margin-top:6px">📌 على الآيفون: ثبّت الموقع على الشاشة الرئيسية أولًا (بالخطوات أعلاه)، ثم افتح التطبيق من الأيقونة وفعّل التنبيهات من داخله — هذا شرط من نظام iOS (الإصدار 16.4 فأحدث).</p>
      <?php else: ?>
        <p class="form-hint">التنبيهات غير متاحة حاليًا على هذا الخادم.</p>
      <?php endif; ?>
    </div>

  </div>
</div>
