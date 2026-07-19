<?php
/**
 * صفحة برنامج العائلة التعريفية — مادة تسويقية مستقلة بهوية المجرات (ar.almgrat.com).
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
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700;800;900&family=Tajawal:wght@400;500;700;800&display=swap">
<style>
/* هوية المجرات — تصميم أبيض فاتح (ar.almgrat.com) */
:root{
  --primary:#2563EB;
  --primary-light:#3B82F6;
  --accent:#7C3AED;
  --green:#059669;
  --dark:#0F172A;
  --bg:#FFFFFF;
  --bg-2:#F8FAFF;
  --bg-3:#F1F5FF;
  --border:#E2E8F0;
  --border-hover:#93C5FD;
  --text:#1E293B;
  --text-muted:#64748B;
  --gradient-1:linear-gradient(135deg,#2563EB 0%,#7C3AED 100%);
  --shadow-md:0 4px 16px rgba(37,99,235,.10);
  --shadow-lg:0 12px 40px rgba(37,99,235,.12);
  --shadow-card:0 2px 12px rgba(0,0,0,.06);
  --radius-lg:16px;
  --radius-xl:24px;
  --font-main:'Cairo','Tajawal',sans-serif;
}
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
html{scroll-behavior:smooth}
body{
  font-family:var(--font-main);background:var(--bg);color:var(--text);
  direction:rtl;text-align:right;line-height:1.7;overflow-x:hidden;
  -webkit-font-smoothing:antialiased;
}
/* شبكة خلفية خفيفة — بصمة هوية المجرات */
body::before{
  content:'';position:fixed;inset:0;z-index:0;pointer-events:none;
  background-image:
    linear-gradient(rgba(37,99,235,.03) 1px,transparent 1px),
    linear-gradient(90deg,rgba(37,99,235,.03) 1px,transparent 1px);
  background-size:48px 48px;
}
a{color:var(--primary);text-decoration:none;transition:all .25s cubic-bezier(.4,0,.2,1)}
a:hover{color:var(--accent)}
.wrap{max-width:1120px;margin:0 auto;padding:0 20px;position:relative;z-index:1}

/* شريط علوي بشعار المجرات — مطابق لترويسة ar.almgrat.com */
.g-top{
  position:sticky;top:0;z-index:100;
  background:rgba(255,255,255,.85);-webkit-backdrop-filter:blur(12px);backdrop-filter:blur(12px);
  border-bottom:1px solid var(--border);
}
.g-top-in{max-width:1120px;margin:0 auto;padding:10px 20px;display:flex;align-items:center;justify-content:space-between}
.site-logo{display:flex;align-items:center;gap:10px;text-decoration:none;flex-shrink:0}
.logo-img{
  width:46px;height:46px;object-fit:contain;
  filter:drop-shadow(0 2px 10px rgba(37,99,235,.35));
  transition:transform .3s ease;
}
.site-logo:hover .logo-img{transform:scale(1.06)}
.logo-text{display:flex;flex-direction:column;line-height:1.1}
.logo-name-ar{
  font-size:17px;font-weight:800;
  background:var(--gradient-1);
  -webkit-background-clip:text;-webkit-text-fill-color:transparent;background-clip:text;
}
.logo-name-en{
  font-size:9px;font-weight:600;color:#94A3B8;
  letter-spacing:3px;text-transform:uppercase;font-family:'Courier New',monospace;
}
.g-top .btn{padding:9px 20px;font-size:.85rem;border-radius:10px}

/* الترويسة */
.hero{text-align:center;padding:56px 0 48px;position:relative}
.hero::after{
  content:'';position:absolute;top:-80px;left:50%;transform:translateX(-50%);
  width:640px;height:420px;border-radius:50%;z-index:-1;
  background:radial-gradient(closest-side,rgba(124,58,237,.08),rgba(37,99,235,.05),transparent);
}
.maker{
  display:inline-flex;align-items:center;gap:8px;
  background:var(--bg-3);border:1px solid var(--border);border-radius:999px;
  padding:8px 18px;font-size:.85rem;font-weight:700;color:var(--text-muted);
}
.maker b{background:var(--gradient-1);-webkit-background-clip:text;background-clip:text;-webkit-text-fill-color:transparent}
.hero h1{
  margin:22px 0 6px;font-weight:900;line-height:1.2;color:var(--dark);
  font-size:clamp(38px,5.5vw,64px);
}
.hero h1 .gradient-text{
  background:var(--gradient-1);
  -webkit-background-clip:text;background-clip:text;-webkit-text-fill-color:transparent;
}
.tagline{margin:0 auto;max-width:620px;color:var(--text-muted);font-size:1.08rem}
.version{
  display:inline-block;margin-top:16px;background:var(--bg-3);
  border:1px solid var(--border);color:var(--primary);
  border-radius:999px;padding:5px 16px;font-size:.8rem;font-weight:800;
}
.ctas{display:flex;justify-content:center;gap:12px;flex-wrap:wrap;margin-top:28px}
.btn{
  display:inline-flex;align-items:center;justify-content:center;gap:8px;
  border-radius:12px;padding:14px 30px;font-weight:800;font-size:1rem;
  border:none;cursor:pointer;font-family:var(--font-main);
  transition:all .25s cubic-bezier(.4,0,.2,1);
}
.btn-primary{background:var(--primary);color:#fff;box-shadow:0 4px 14px rgba(37,99,235,.3)}
.btn-primary:hover{background:var(--primary-light);color:#fff;transform:translateY(-2px)}
.btn-outline{background:transparent;color:var(--primary);border:1.5px solid var(--primary)}
.btn-outline:hover{background:var(--bg-3);color:var(--primary)}

/* الأقسام */
.section{padding:46px 0}
.section-alt{background:var(--bg-2);border-top:1px solid var(--border);border-bottom:1px solid var(--border)}
.section-badge{
  display:table;margin:0 auto 12px;background:var(--bg-3);color:var(--primary);
  border:1px solid var(--border);border-radius:999px;padding:5px 16px;
  font-size:.78rem;font-weight:800;
}
.section-title{text-align:center;font-size:clamp(26px,3.5vw,40px);font-weight:800;color:var(--dark);margin-bottom:10px}
.section-sub{text-align:center;color:var(--text-muted);margin:0 auto 32px;max-width:560px;font-size:.95rem}

/* المميزات */
.features{display:grid;grid-template-columns:1fr;gap:16px}
@media(min-width:640px){.features{grid-template-columns:repeat(2,1fr)}}
@media(min-width:960px){.features{grid-template-columns:repeat(4,1fr)}}
.feature{
  background:#fff;border:1px solid var(--border);border-radius:var(--radius-lg);
  padding:24px 20px;box-shadow:var(--shadow-card);
  transition:all .25s cubic-bezier(.4,0,.2,1);
}
.feature:hover{transform:translateY(-5px);border-color:var(--border-hover);box-shadow:var(--shadow-lg)}
.feature .fi{
  width:52px;height:52px;border-radius:14px;display:flex;align-items:center;justify-content:center;
  font-size:1.5rem;margin-bottom:14px;background:var(--bg-3);border:1px solid var(--border);
}
.feature b{display:block;font-size:1.02rem;margin-bottom:6px;color:var(--dark);font-weight:800}
.feature p{color:var(--text-muted);font-size:.85rem;line-height:1.75}

/* المعاينة */
.previews{display:flex;gap:40px;flex-wrap:wrap;justify-content:center;align-items:flex-start}
.device{text-align:center}
.device .label{color:var(--text-muted);font-size:.88rem;font-weight:700;margin-top:16px}
.phone{
  width:270px;height:552px;border-radius:38px;padding:10px;position:relative;
  background:var(--dark);
  box-shadow:var(--shadow-lg),0 0 0 1px var(--border);
}
.phone::before{
  content:"";position:absolute;top:16px;left:50%;transform:translateX(-50%);
  width:78px;height:8px;border-radius:999px;background:#000;z-index:2;
}
.phone .screen{width:100%;height:100%;border-radius:29px;overflow:hidden;background:#fff;position:relative}
.phone iframe{
  width:375px;height:767px;border:0;
  transform:scale(.6667);transform-origin:top right;
  pointer-events:none;
}
/* مجسم لوحة التحكم */
.admin-mock{
  width:min(470px,92vw);border-radius:var(--radius-lg);overflow:hidden;
  background:#f6f4ef;border:1px solid var(--border);
  box-shadow:var(--shadow-lg);
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
.modules{display:grid;grid-template-columns:1fr;gap:12px}
@media(min-width:640px){.modules{grid-template-columns:repeat(2,1fr)}}
@media(min-width:960px){.modules{grid-template-columns:repeat(3,1fr)}}
.module{
  background:#fff;border:1px solid var(--border);border-radius:12px;
  padding:16px 18px;display:flex;justify-content:space-between;gap:10px;align-items:flex-start;
  box-shadow:var(--shadow-card);transition:all .25s;
}
.module:hover{border-color:var(--border-hover)}
.module b{display:block;font-size:.95rem;margin-bottom:3px;color:var(--dark);font-weight:800}
.module p{color:var(--text-muted);font-size:.8rem;line-height:1.7}
.module .mv{
  flex:none;color:var(--accent);font-size:.7rem;font-weight:800;
  background:#F5F3FF;border:1px solid #ddd6fe;border-radius:999px;padding:2px 10px;
}

/* سجل التحديثات */
.changelog{
  background:#fff;border:1px solid var(--border);border-radius:var(--radius-lg);
  padding:8px 22px;box-shadow:var(--shadow-card);
}
.changelog summary{
  cursor:pointer;font-weight:800;padding:12px 0;font-size:1rem;list-style:none;
  display:flex;align-items:center;justify-content:space-between;color:var(--dark);
}
.changelog summary::after{content:"⌄";color:var(--primary);font-size:1.2rem}
.changelog[open] summary::after{content:"⌃"}
.changelog-body{border-top:1px solid var(--border);padding:14px 0;max-height:420px;overflow-y:auto;font-size:.85rem;color:var(--text)}
.changelog-body h2,.changelog-body h3{color:var(--dark);font-size:1rem;margin:16px 0 6px}
.changelog-body h4,.changelog-body h5{color:var(--primary);font-size:.9rem;margin:12px 0 5px}
.changelog-body ul{padding-inline-start:20px;margin:6px 0;list-style:disc}
.changelog-body li{margin:3px 0}
.changelog-body hr{border:none;border-top:1px solid var(--border);margin:14px 0}
.changelog-body code{background:var(--bg-3);border-radius:5px;padding:1px 6px;font-size:.8em}

/* دعوة أخيرة وفوتر */
.final-cta{
  text-align:center;background:var(--gradient-1);color:#fff;
  border-radius:var(--radius-xl);padding:46px 22px;box-shadow:var(--shadow-lg);
}
.final-cta h2{margin:0 0 8px;font-size:clamp(22px,3vw,32px);font-weight:900}
.final-cta p{margin:0 0 22px;color:rgba(255,255,255,.85);font-size:.95rem}
.final-cta .btn{background:#fff;color:var(--primary)}
.final-cta .btn:hover{transform:translateY(-2px)}
.g-footer{text-align:center;padding:32px 0 28px;color:var(--text-muted);font-size:.82rem;border-top:1px solid var(--border);margin-top:46px}
.g-footer a{font-weight:800}
@media(max-width:639px){
  .hero{padding:50px 0 36px}
  .phone{width:240px;height:492px}
  .phone iframe{transform:scale(.5893)}
}
</style>
</head>
<body>

<div class="g-top">
  <div class="g-top-in">
    <a href="https://ar.almgrat.com" target="_blank" rel="noopener" class="site-logo">
      <img src="<?= \Core\Support\Url::to('admin/assets/img/almgrat-logo.png') ?>" class="logo-img" alt="المجرات — ALmgrat">
      <span class="logo-text">
        <span class="logo-name-ar">المجرات</span>
        <span class="logo-name-en">ALMGRAT</span>
      </span>
    </a>
    <a class="btn btn-primary" href="https://almgrat.com" target="_blank" rel="noopener">اطلب البرنامج</a>
  </div>
</div>

<header class="hero wrap">
  <span class="maker">🚀 من تطوير <b>المجرات</b></span>
  <h1>برنامج <span class="gradient-text">العائلة</span></h1>
  <p class="tagline">نظام متكامل لإنشاء الموقع الرسمي لأي عائلة أو قبيلة — يجمع أهلك في مكان واحد: مناسبات، جمعات، أخبار، وتنبيهات تصل لجوالاتهم فورًا.</p>
  <span class="version">الإصدار <?= View::e($coreVersion) ?></span>
  <div class="ctas">
    <a class="btn btn-primary" href="https://almgrat.com" target="_blank" rel="noopener">اطلب البرنامج لعائلتك</a>
    <a class="btn btn-outline" href="<?= View::e($siteBase) ?>">شاهد مثالًا حيًا ←</a>
  </div>
</header>

<section class="section wrap">
  <span class="section-badge">المميزات</span>
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

<section class="section section-alt">
  <div class="wrap">
    <span class="section-badge">معاينة حية</span>
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
  </div>
</section>

<section class="section wrap">
  <span class="section-badge">الإضافات</span>
  <h2 class="section-title">كل ما تحتاجه في مكان واحد</h2>
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
    <a class="btn" href="https://almgrat.com" target="_blank" rel="noopener">تواصل مع المجرات الآن 🚀</a>
  </div>
</section>

<footer class="g-footer wrap">
  برنامج العائلة — صفحة تعريفية مرفقة مع كل نسخة من النظام · تطوير <a href="https://almgrat.com" target="_blank" rel="noopener">المجرات</a>
</footer>

</body>
</html>
