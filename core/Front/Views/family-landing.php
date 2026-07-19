<?php
/**
 * صفحة برنامج العائلة التعريفية — مادة تسويقية مستقلة بهوية المجرات.
 * ترافق كل تركيبة من البرنامج على مسار /family وتتحدث تلقائيًا مع كل إصدار:
 * الإضافات من module.json، السجل من CHANGELOG.md، والمعاينة الحية من موقع التركيب نفسه.
 *
 * @var string $coreVersion
 * @var array  $modules
 * @var array  $installedMap
 * @var string $changelogHtml
 * @var string $siteBase
 */
use Core\View;
?><!doctype html>
<html lang="ar" dir="rtl">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<title>برنامج العائلة — نظام مواقع العوائل والقبائل | تطوير المجرات</title>
<meta name="description" content="برنامج العائلة: نظام متكامل لإنشاء موقع رسمي لأي عائلة أو قبيلة — تطبيق جوال، تنبيهات فورية، رزنامة مناسبات، جوال العائلة، شجرة نسب، وأمان مشدد. تطوير المجرات.">
<meta name="robots" content="index, follow">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Tajawal:wght@400;500;700;800;900&display=swap">
<style>
:root{
  --g-bg:#0b0f26;
  --g-bg2:#131a3d;
  --g-surface:rgba(255,255,255,.05);
  --g-border:rgba(255,255,255,.1);
  --g-text:#eef0fa;
  --g-muted:#9aa3c7;
  --g-gold:#e8b64c;
  --g-violet:#7c6cff;
  --g-radius:18px;
}
*{box-sizing:border-box}
body{
  margin:0;font-family:'Tajawal',Tahoma,Arial,sans-serif;
  background:var(--g-bg);color:var(--g-text);line-height:1.8;font-size:16px;
  background-image:
    radial-gradient(700px 350px at 85% -50px,rgba(124,108,255,.22),transparent 70%),
    radial-gradient(600px 300px at 10% 20%,rgba(232,182,76,.1),transparent 70%);
  background-attachment:fixed;
}
a{color:inherit;text-decoration:none}
.wrap{max-width:1060px;margin:0 auto;padding:0 18px}

/* نجوم خافتة */
.stars{position:fixed;inset:0;pointer-events:none;opacity:.5;background-image:
  radial-gradient(1.2px 1.2px at 20% 30%,#fff,transparent),
  radial-gradient(1px 1px at 70% 12%,#fff,transparent),
  radial-gradient(1.4px 1.4px at 40% 70%,#ffffffcc,transparent),
  radial-gradient(1px 1px at 90% 55%,#fff,transparent),
  radial-gradient(1px 1px at 55% 40%,#ffffffaa,transparent),
  radial-gradient(1.3px 1.3px at 12% 80%,#fff,transparent)}

/* الترويسة */
.hero{position:relative;text-align:center;padding:60px 0 40px}
.hero .maker{
  display:inline-flex;align-items:center;gap:8px;
  background:var(--g-surface);border:1px solid var(--g-border);border-radius:999px;
  padding:7px 16px;font-size:.82rem;font-weight:700;color:var(--g-muted);
}
.hero .maker b{color:var(--g-gold)}
.hero h1{
  margin:20px 0 6px;font-size:2.3rem;font-weight:900;line-height:1.3;
  background:linear-gradient(90deg,#fff 30%,var(--g-gold));
  -webkit-background-clip:text;background-clip:text;-webkit-text-fill-color:transparent;
}
.hero .tagline{margin:0 auto;max-width:640px;color:var(--g-muted);font-size:1.05rem}
.hero .version{
  display:inline-block;margin-top:14px;background:rgba(124,108,255,.15);
  border:1px solid rgba(124,108,255,.4);color:#c9c2ff;
  border-radius:999px;padding:4px 14px;font-size:.78rem;font-weight:800;
}
.hero .ctas{display:flex;justify-content:center;gap:12px;flex-wrap:wrap;margin-top:26px}
.btn{
  display:inline-flex;align-items:center;justify-content:center;gap:8px;
  border-radius:999px;padding:13px 26px;font-weight:800;font-size:.95rem;
  border:none;cursor:pointer;font-family:inherit;
}
.btn-gold{background:linear-gradient(135deg,var(--g-gold),#d99b26);color:#231a05;box-shadow:0 8px 26px rgba(232,182,76,.3)}
.btn-ghost{background:var(--g-surface);border:1px solid var(--g-border);color:var(--g-text)}

/* الأقسام */
.section{padding:38px 0}
.section-title{text-align:center;font-size:1.5rem;font-weight:900;margin:0 0 8px}
.section-sub{text-align:center;color:var(--g-muted);margin:0 auto 28px;max-width:560px;font-size:.92rem}

/* المميزات */
.features{display:grid;grid-template-columns:1fr;gap:14px}
@media(min-width:640px){.features{grid-template-columns:repeat(2,1fr)}}
@media(min-width:960px){.features{grid-template-columns:repeat(4,1fr)}}
.feature{
  background:var(--g-surface);border:1px solid var(--g-border);border-radius:var(--g-radius);
  padding:22px 18px;transition:transform .2s,border-color .2s;
}
.feature:hover{transform:translateY(-4px);border-color:rgba(232,182,76,.4)}
.feature .fi{
  width:50px;height:50px;border-radius:14px;display:flex;align-items:center;justify-content:center;
  font-size:1.5rem;margin-bottom:12px;
  background:linear-gradient(135deg,rgba(124,108,255,.25),rgba(232,182,76,.2));
  border:1px solid var(--g-border);
}
.feature b{display:block;font-size:1rem;margin-bottom:5px}
.feature p{margin:0;color:var(--g-muted);font-size:.84rem;line-height:1.75}

/* المعاينة */
.previews{display:flex;gap:34px;flex-wrap:wrap;justify-content:center;align-items:flex-start}
.device{text-align:center}
.device .label{color:var(--g-muted);font-size:.85rem;font-weight:700;margin-top:14px}
.phone{
  width:270px;height:552px;border-radius:38px;padding:10px;position:relative;
  background:#1a1f3d;border:1px solid var(--g-border);
  box-shadow:0 24px 60px rgba(0,0,0,.5),0 0 0 2px rgba(124,108,255,.2);
}
.phone::before{
  content:"";position:absolute;top:16px;left:50%;transform:translateX(-50%);
  width:78px;height:8px;border-radius:999px;background:#0b0f26;z-index:2;
}
.phone .screen{
  width:100%;height:100%;border-radius:29px;overflow:hidden;background:#fff;position:relative;
}
.phone iframe{
  width:375px;height:767px;border:0;
  transform:scale(.6667);transform-origin:top right;
  pointer-events:none;
}
/* مجسم لوحة التحكم */
.admin-mock{
  width:min(470px,92vw);border-radius:16px;overflow:hidden;
  background:#f6f4ef;border:1px solid var(--g-border);
  box-shadow:0 24px 60px rgba(0,0,0,.5),0 0 0 2px rgba(232,182,76,.18);
  direction:rtl;text-align:right;
}
.am-top{background:#183f35;color:#fff;padding:9px 14px;display:flex;align-items:center;gap:8px;font-size:.66rem;font-weight:800}
.am-top .dot{width:7px;height:7px;border-radius:50%;background:#e8b64c}
.am-body{display:flex;min-height:295px}
.am-side{background:#10291f;color:#cfe0d6;width:112px;flex:none;padding:9px 0;font-size:.6rem;font-weight:700}
.am-side span{display:block;padding:6px 12px;opacity:.85}
.am-side span.on{background:rgba(232,182,76,.18);color:#f3d489;border-inline-start:3px solid #e8b64c}
.am-main{flex:1;padding:11px}
.am-stats{display:grid;grid-template-columns:repeat(3,1fr);gap:7px;margin-bottom:10px}
.am-stat{background:#fff;border:1px solid #e3ded2;border-radius:9px;padding:8px 6px;text-align:center}
.am-stat b{display:block;font-size:.85rem;color:#183f35}
.am-stat i{font-style:normal;font-size:.5rem;color:#8b8574}
.am-table{background:#fff;border:1px solid #e3ded2;border-radius:9px;overflow:hidden}
.am-th{background:#f0ece1;padding:6px 10px;font-size:.56rem;font-weight:800;color:#5d5745}
.am-tr{display:flex;justify-content:space-between;align-items:center;padding:6px 10px;border-top:1px solid #eee9dc;font-size:.56rem;color:#3d3a30}
.am-badge{background:#e7f6ee;color:#1c6e3c;border-radius:999px;padding:1px 7px;font-size:.5rem;font-weight:800}

/* الإضافات */
.modules{display:grid;grid-template-columns:1fr;gap:10px}
@media(min-width:640px){.modules{grid-template-columns:repeat(2,1fr)}}
@media(min-width:960px){.modules{grid-template-columns:repeat(3,1fr)}}
.module{
  background:var(--g-surface);border:1px solid var(--g-border);border-radius:14px;
  padding:14px 16px;display:flex;justify-content:space-between;gap:10px;align-items:flex-start;
}
.module b{display:block;font-size:.92rem;margin-bottom:3px}
.module p{margin:0;color:var(--g-muted);font-size:.78rem;line-height:1.7}
.module .mv{flex:none;color:#c9c2ff;font-size:.68rem;font-weight:800;background:rgba(124,108,255,.15);border-radius:999px;padding:2px 9px}

/* سجل التحديثات */
.changelog{
  background:var(--g-surface);border:1px solid var(--g-border);border-radius:var(--g-radius);
  padding:8px 22px;
}
.changelog summary{
  cursor:pointer;font-weight:800;padding:12px 0;font-size:1rem;list-style:none;
  display:flex;align-items:center;justify-content:space-between;
}
.changelog summary::after{content:"⌄";color:var(--g-gold);font-size:1.2rem}
.changelog[open] summary::after{content:"⌃"}
.changelog-body{border-top:1px solid var(--g-border);padding:14px 0;max-height:420px;overflow-y:auto;font-size:.85rem;color:#c6cbe4}
.changelog-body h2,.changelog-body h3{color:#fff;font-size:1rem;margin:16px 0 6px}
.changelog-body h4,.changelog-body h5{color:var(--g-gold);font-size:.9rem;margin:12px 0 5px}
.changelog-body ul{padding-inline-start:20px;margin:6px 0}
.changelog-body li{margin:3px 0}
.changelog-body hr{border:none;border-top:1px solid var(--g-border);margin:14px 0}
.changelog-body code{background:rgba(255,255,255,.1);border-radius:5px;padding:1px 6px;font-size:.8em}

/* دعوة أخيرة وفوتر */
.final-cta{
  text-align:center;background:linear-gradient(135deg,rgba(124,108,255,.16),rgba(232,182,76,.12));
  border:1px solid var(--g-border);border-radius:var(--g-radius);padding:38px 20px;
}
.final-cta h2{margin:0 0 8px;font-size:1.4rem;font-weight:900}
.final-cta p{margin:0 0 20px;color:var(--g-muted);font-size:.92rem}
.g-footer{text-align:center;padding:30px 0 26px;color:var(--g-muted);font-size:.8rem}
.g-footer a{color:var(--g-gold);font-weight:800}
@media(max-width:639px){
  .hero{padding:44px 0 30px}
  .hero h1{font-size:1.7rem}
  .phone{width:240px;height:492px}
  .phone iframe{transform:scale(.5893)}
}
</style>
</head>
<body>
<div class="stars"></div>

<header class="hero wrap">
  <span class="maker">🌌 من تطوير <b>المجرات</b></span>
  <h1>برنامج العائلة</h1>
  <p class="tagline">نظام متكامل لإنشاء الموقع الرسمي لأي عائلة أو قبيلة — يجمع أهلك في مكان واحد: مناسبات، جمعات، أخبار، وتنبيهات تصل لجوالاتهم فورًا.</p>
  <span class="version">الإصدار <?= View::e($coreVersion) ?></span>
  <div class="ctas">
    <a class="btn btn-gold" href="https://almgrat.com" target="_blank" rel="noopener">اطلب البرنامج لعائلتك ✨</a>
    <a class="btn btn-ghost" href="<?= View::e($siteBase) ?>">شاهد مثالًا حيًا ←</a>
  </div>
</header>

<section class="section wrap">
  <h2 class="section-title">ليش برنامج العائلة؟</h2>
  <p class="section-sub">مصمم خصيصًا لطبيعة العوائل والقبائل العربية — بدون تعقيد، وبخصوصية تامة لبيانات أفراد العائلة</p>
  <div class="features">
    <div class="feature"><span class="fi">📲</span><b>تطبيق جوال جاهز</b><p>يثبّت على شاشة الجوال كتطبيق حقيقي بأيقونة العائلة — بدون متاجر تطبيقات ولا تكاليف إضافية.</p></div>
    <div class="feature"><span class="fi">🔔</span><b>تنبيهات فورية</b><p>إشعارات تصل لجوالات المشتركين لحظة نشر مناسبة أو خبر أو وفاة — حتى والموقع مغلق.</p></div>
    <div class="feature"><span class="fi">📅</span><b>رزنامة المناسبات</b><p>مناسبات العائلة بعدّاد تنازلي واضح: زواجات، تخرج، اجتماعات — مع عرض شهري كامل.</p></div>
    <div class="feature"><span class="fi">📱</span><b>جوال العائلة</b><p>قاعدة أرقام تواصل خاصة بالإدارة فقط، بتسجيل ذاتي من الأفراد ودون عرض عام لأي رقم.</p></div>
    <div class="feature"><span class="fi">🕊</span><b>الوفيات والعزاء</b><p>إعلان وقور بمكان العزاء وأوقاته وموقعه على الخريطة، يتصدر الموقع ويصل تنبيهًا للجميع.</p></div>
    <div class="feature"><span class="fi">🌳</span><b>شجرة النسب</b><p>خريطة نسب تفاعلية متصلة الأجيال، أو صورة مخطوطة ترفعها الإدارة — بدون بيانات حساسة.</p></div>
    <div class="feature"><span class="fi">🛡️</span><b>أمان مشدد</b><p>مصادقة ثنائية، صلاحيات ثلاثية المستويات، سجل نشاط لكل مستخدم، وحماية مراجعة أمنيًا.</p></div>
    <div class="feature"><span class="fi">🧩</span><b>إضافات مرنة</b><p>كل ميزة إضافة مستقلة تفعّلها أو تعطلها بضغطة: معرض، أرشيف، أخبار، جمعات، قائمة بريدية.</p></div>
  </div>
</section>

<section class="section wrap">
  <h2 class="section-title">شوفه بعينك</h2>
  <p class="section-sub">معاينة حية مباشرة من هذا التركيب — مو صور معدلة</p>
  <div class="previews">
    <div class="device">
      <div class="phone"><div class="screen"><iframe src="<?= View::e($siteBase) ?>" title="معاينة حية للموقع" loading="lazy" tabindex="-1"></iframe></div></div>
      <div class="label">📱 الموقع كما يراه الزائر — معاينة حية</div>
    </div>
    <div class="device">
      <div class="admin-mock" aria-hidden="true">
        <div class="am-top"><span class="dot"></span> لوحة التحكم — برنامج العائلة</div>
        <div class="am-body">
          <div class="am-side">
            <span class="on">🏠 الرئيسية</span>
            <span>📰 الأخبار</span>
            <span>📅 الرزنامة</span>
            <span>🕊 الوفيات</span>
            <span>📱 جوال العائلة</span>
            <span>🔔 التنبيهات</span>
            <span>👁 نشاط المستخدمين</span>
            <span>⚙️ الإعدادات</span>
          </div>
          <div class="am-main">
            <div class="am-stats">
              <div class="am-stat"><b>124</b><i>رقم مسجّل</i></div>
              <div class="am-stat"><b>86</b><i>مشترك بالتنبيهات</i></div>
              <div class="am-stat"><b>17</b><i>مناسبة</i></div>
            </div>
            <div class="am-table">
              <div class="am-th">آخر التنبيهات المرسلة</div>
              <div class="am-tr"><span>دعوة اجتماع العائلة السنوي</span><span class="am-badge">وصل 86</span></div>
              <div class="am-tr"><span>تهنئة بنجاح أبناء العائلة</span><span class="am-badge">وصل 84</span></div>
              <div class="am-tr"><span>موعد جمعة الخميس</span><span class="am-badge">وصل 79</span></div>
            </div>
          </div>
        </div>
      </div>
      <div class="label">🖥 لوحة تحكم عربية بسيطة لغير المتخصصين</div>
    </div>
  </div>
</section>

<section class="section wrap">
  <h2 class="section-title">كل الإضافات المرفقة</h2>
  <p class="section-sub">تُقرأ هذه القائمة تلقائيًا من ملفات النظام وتتحدث مع كل إصدار</p>
  <div class="modules">
    <?php foreach ($modules as $slug => $m): ?>
    <div class="module">
      <div>
        <b><?= View::e($m['name'] ?? $slug) ?></b>
        <p><?= View::e($m['description'] ?? '') ?></p>
      </div>
      <span class="mv"><?= View::e($m['version'] ?? '1.0') ?></span>
    </div>
    <?php endforeach; ?>
  </div>
</section>

<section class="section wrap">
  <details class="changelog">
    <summary>📋 سجل التحديثات الكامل — آخر إصدار <?= View::e($coreVersion) ?></summary>
    <div class="changelog-body"><?= $changelogHtml ?></div>
  </details>
</section>

<section class="section wrap">
  <div class="final-cta">
    <h2>جاهز تجمع عائلتك في مكان واحد؟</h2>
    <p>يعمل على أي استضافة مشتركة عادية — تركيب من المتصفح بالكامل بدون خبرة تقنية</p>
    <a class="btn btn-gold" href="https://almgrat.com" target="_blank" rel="noopener">تواصل مع المجرات الآن 🚀</a>
  </div>
</section>

<footer class="g-footer wrap">
  برنامج العائلة — صفحة تعريفية مرفقة مع كل نسخة من النظام · تطوير <a href="https://almgrat.com" target="_blank" rel="noopener">المجرات</a>
</footer>

</body>
</html>
