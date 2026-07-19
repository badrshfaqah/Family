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

/* شريط الأرقام */
.stats-strip{
  display:grid;grid-template-columns:repeat(2,1fr);gap:14px;
  background:#fff;border:1px solid var(--border);border-radius:var(--radius-lg);
  padding:22px;box-shadow:var(--shadow-card);
}
@media(min-width:760px){.stats-strip{grid-template-columns:repeat(4,1fr)}}
.stat{text-align:center}
.stat b{
  display:block;font-size:1.9rem;font-weight:900;line-height:1.2;
  background:var(--gradient-1);-webkit-background-clip:text;background-clip:text;-webkit-text-fill-color:transparent;
}
.stat span{color:var(--text-muted);font-size:.82rem;font-weight:700}

/* معرض شاشات البرنامج */
.shots{display:grid;grid-template-columns:repeat(2,1fr);gap:16px}
@media(min-width:960px){.shots{grid-template-columns:repeat(4,1fr)}}
.shot{text-align:center}
.shot .frame{
  border-radius:22px;overflow:hidden;background:#fff;
  border:1px solid var(--border);box-shadow:var(--shadow-lg);
  aspect-ratio:9/16.6;display:flex;flex-direction:column;
  transition:transform .25s;
}
.shot:hover .frame{transform:translateY(-6px)}
.shot .cap{color:var(--text-muted);font-size:.8rem;font-weight:800;margin-top:12px}
/* شاشة القفل مع تنبيه */
.scr-lock{background:linear-gradient(180deg,#1a2440,#0F172A);color:#fff;padding:22px 10px;flex:1;display:flex;flex-direction:column}
.scr-lock .clock{font-size:2.1rem;font-weight:300;text-align:center;letter-spacing:2px}
.scr-lock .date{text-align:center;font-size:.6rem;opacity:.75;margin-bottom:16px}
.notif{
  background:rgba(255,255,255,.95);color:#1E293B;border-radius:14px;padding:9px 11px;
  text-align:right;box-shadow:0 8px 22px rgba(0,0,0,.35);
}
.notif .nh{display:flex;align-items:center;gap:6px;font-size:.55rem;color:#64748B;margin-bottom:4px}
.notif .nh .ic{width:15px;height:15px;border-radius:4px;background:linear-gradient(135deg,#5b4327,#3c2c18);display:inline-flex;align-items:center;justify-content:center;font-size:.5rem}
.notif .nh .now{margin-inline-start:auto}
.notif b{display:block;font-size:.66rem;margin-bottom:2px}
.notif p{font-size:.6rem;color:#475569;line-height:1.6}
.notif+.notif{margin-top:8px;opacity:.92}
/* شاشات بألوان البرنامج التراثية */
.scr{flex:1;background:#f5efe2;padding:10px;display:flex;flex-direction:column;gap:7px;overflow:hidden}
.scr .bar{background:#5b4327;color:#fff;border-radius:9px;padding:6px 9px;font-size:.6rem;font-weight:800;text-align:right;display:flex;justify-content:space-between;align-items:center}
.mini-cal{display:flex;align-items:center;gap:6px;background:#fffdf8;border:1px solid #e7dcc6;border-radius:10px;padding:6px}
.mini-cal.hot{border-color:#c9a24b;background:linear-gradient(135deg,#fffdf8,#f8f0dd)}
.mini-cal .d{flex:none;width:30px;text-align:center;background:#5b4327;color:#fff;border-radius:7px;padding:3px 0;font-size:.52rem;line-height:1.3}
.mini-cal .d b{display:block;font-size:.72rem}
.mini-cal .t{flex:1;text-align:right;min-width:0}
.mini-cal .t b{display:block;font-size:.58rem;color:#2a2214;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
.mini-cal .t i{font-style:normal;font-size:.48rem;color:#83725a}
.mini-cal .left{flex:none;background:#c9a24b;color:#2a220a;border-radius:999px;padding:2px 6px;font-size:.45rem;font-weight:800;white-space:nowrap}
.mini-obit{background:#f7f5f0;border:1px solid #ddd6c8;border-inline-start:3px solid #8a8478;border-radius:10px;padding:7px 9px;text-align:right;display:flex;gap:6px;align-items:center}
.mini-obit .t{flex:1;min-width:0}
.mini-obit b{display:block;font-size:.58rem;color:#3c382f}
.mini-obit i{font-style:normal;font-size:.48rem;color:#7a7466}
.mini-obit .city{flex:none;background:#eceae3;border-radius:999px;padding:2px 6px;font-size:.45rem;font-weight:800;color:#5c574a}
.mini-form{background:#fffdf8;border:1px solid #e7dcc6;border-radius:12px;padding:9px;display:flex;flex-direction:column;gap:6px;text-align:right}
.mini-form .fld{background:#fff;border:1px solid #e7dcc6;border-radius:8px;padding:6px 8px;font-size:.52rem;color:#83725a}
.mini-form .chips{display:flex;gap:4px;flex-wrap:wrap}
.mini-form .chip{border:1px solid #e7dcc6;border-radius:999px;padding:3px 8px;font-size:.48rem;font-weight:800;color:#5b4327;background:#fff}
.mini-form .chip.on{background:#c9a24b;border-color:#c9a24b;color:#2a220a}
.mini-form .go{background:#c9a24b;color:#2a220a;border-radius:999px;padding:6px;font-size:.56rem;font-weight:900;text-align:center;margin-top:2px}
.scr .hint{font-size:.5rem;color:#83725a;text-align:center;margin-top:auto}

/* خطوات الانطلاق */
.steps{display:grid;grid-template-columns:1fr;gap:16px;counter-reset:st}
@media(min-width:760px){.steps{grid-template-columns:repeat(3,1fr)}}
.step{
  background:#fff;border:1px solid var(--border);border-radius:var(--radius-lg);
  padding:26px 20px;text-align:center;box-shadow:var(--shadow-card);position:relative;
}
.step .num{
  width:44px;height:44px;border-radius:50%;margin:0 auto 12px;
  background:var(--gradient-1);color:#fff;font-weight:900;font-size:1.1rem;
  display:flex;align-items:center;justify-content:center;box-shadow:var(--shadow-md);
}
.step b{display:block;color:var(--dark);font-size:1rem;margin-bottom:6px;font-weight:800}
.step p{color:var(--text-muted);font-size:.84rem}

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
  <p class="tagline">وداعًا لضياع أخبار العائلة بين قروبات الواتساب.. موقع رسمي يليق باسم عائلتك، يتثبّت كتطبيق على جوالات الجميع، ويوصّل كل مناسبة وخبر وعزاء <b>بتنبيه فوري</b> لكل فرد.</p>
  <span class="version">الإصدار <?= View::e($coreVersion) ?> — يتطور باستمرار</span>
  <div class="ctas">
    <a class="btn btn-primary" href="https://almgrat.com" target="_blank" rel="noopener">اطلب البرنامج لعائلتك</a>
    <a class="btn btn-outline" href="<?= View::e($siteBase) ?>">شاهد مثالًا حيًا ←</a>
  </div>
</header>

<section class="wrap" style="padding:6px 20px 14px">
  <div class="stats-strip">
    <div class="stat"><b>12</b><span>إضافة متكاملة مرفقة</span></div>
    <div class="stat"><b>100%</b><span>عربي — واجهة ولوحة تحكم</span></div>
    <div class="stat"><b>0</b><span>خبرة تقنية مطلوبة للإدارة</span></div>
    <div class="stat"><b>24/7</b><span>تنبيهات تصل حتى والموقع مغلق</span></div>
  </div>
</section>

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

<section class="section wrap">
  <span class="section-badge">من داخل البرنامج</span>
  <h2 class="section-title">لقطات تتكلم عن نفسها</h2>
  <p class="section-sub">هذي تجربة أفراد عائلتك اليومية مع البرنامج — من التنبيه على شاشة القفل إلى التسجيل بضغطة</p>
  <div class="shots">

    <div class="shot">
      <div class="frame">
        <div class="scr-lock">
          <div class="clock">9:41</div>
          <div class="date">الجمعة، 24 يوليو</div>
          <div class="notif">
            <div class="nh"><span class="ic">🕊</span> موقع العائلة <span class="now">الآن</span></div>
            <b>وفاة الوالد فلان بن فلان</b>
            <p>العزاء بمقر العائلة — من بعد صلاة المغرب. اضغط للتفاصيل والموقع</p>
          </div>
          <div class="notif">
            <div class="nh"><span class="ic">📅</span> موقع العائلة <span class="now">قبل ساعة</span></div>
            <b>تذكير: زواج ابن العم غدًا</b>
            <p>قاعة الاحتفالات الكبرى — الساعة 7:00 مساءً</p>
          </div>
        </div>
      </div>
      <div class="cap">🔔 التنبيه يوصل قبل ما يسألون "متى العزاء؟"</div>
    </div>

    <div class="shot">
      <div class="frame">
        <div class="scr">
          <div class="bar">📅 رزنامة المناسبات <span>›</span></div>
          <div class="mini-cal hot"><div class="d"><span>الجمعة</span><b>25</b></div><div class="t"><b>زواج ابن العم</b><i>🕗 7:00 م · قاعة الاحتفالات</i></div><span class="left">غدًا</span></div>
          <div class="mini-cal"><div class="d"><span>الأحد</span><b>27</b></div><div class="t"><b>حفل تخرج أبناء العائلة</b><i>🕗 8:00 م · الصالة الكبرى</i></div><span class="left">باقي 3 أيام</span></div>
          <div class="mini-cal"><div class="d"><span>الخميس</span><b>31</b></div><div class="t"><b>اجتماع العائلة السنوي</b><i>🕗 9:00 م · الاستراحة</i></div><span class="left">باقي 7 أيام</span></div>
          <div class="mini-cal"><div class="d"><span>الجمعة</span><b>8</b></div><div class="t"><b>وليمة عشاء الفريج</b><i>🕗 8:30 م · منزل العم</i></div><span class="left">باقي 15 يوم</span></div>
          <div class="hint">عدّاد تنازلي واضح لكل مناسبة</div>
        </div>
      </div>
      <div class="cap">📅 محد يفوته شي بعد اليوم</div>
    </div>

    <div class="shot">
      <div class="frame">
        <div class="scr">
          <div class="bar">🕊 الوفيات والعزاء <span>›</span></div>
          <div class="mini-obit"><span>🕊</span><div class="t"><b>وفاة الوالد فلان بن فلان</b><i>📍 العزاء: مقر العائلة — حي الروضة</i></div><span class="city">الرياض</span></div>
          <div class="mini-obit"><span>🕊</span><div class="t"><b>وفاة الوالدة أم فلان</b><i>📍 العزاء: منزل العائلة القديم</i></div><span class="city">بريدة</span></div>
          <div class="mini-form" style="text-align:center">
            <div class="fld" style="text-align:center">🗓 تاريخ الوفاة · 🕰 أوقات العزاء</div>
            <div class="fld" style="text-align:center">🗺 موقع العزاء على الخريطة ←</div>
          </div>
          <div class="hint">إعلان وقور بكل التفاصيل التي يحتاجها المعزّون</div>
        </div>
      </div>
      <div class="cap">🕊 في اللحظات الصعبة، الخبر يوصل بكرامة</div>
    </div>

    <div class="shot">
      <div class="frame">
        <div class="scr">
          <div class="bar">📱 التسجيل في جوال العائلة <span>›</span></div>
          <div class="mini-form">
            <div class="fld">الاسم: محمد بن فلان</div>
            <div class="fld">الجوال: 05xxxxxxxx</div>
            <div class="chips"><span class="chip on">الرياض</span><span class="chip on">جدة</span><span class="chip">بريدة</span></div>
            <div class="go">سجّلني ✨</div>
          </div>
          <div class="mini-form" style="text-align:center">
            <div class="fld" style="text-align:center">🔒 الأرقام للإدارة فقط — لا تُعرض لأحد</div>
          </div>
          <div class="hint">تسجيل ذاتي بثوانٍ — والقاعدة تكبر وحدها</div>
        </div>
      </div>
      <div class="cap">📱 قاعدة تواصل العائلة تبني نفسها</div>
    </div>

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
  <span class="section-badge">كيف تنطلق؟</span>
  <h2 class="section-title">موقع عائلتك جاهز بثلاث خطوات</h2>
  <p class="section-sub">ما تحتاج مبرمج ولا خبرة — المجرات تجهز لك كل شيء</p>
  <div class="steps">
    <div class="step"><span class="num">1</span><b>اطلبه من المجرات</b><p>تواصل معنا ونجهّز البرنامج بهوية عائلتك: الاسم والشعار والألوان — على نطاقكم الخاص.</p></div>
    <div class="step"><span class="num">2</span><b>أضف محتواك</b><p>من لوحة تحكم عربية بسيطة: مناسبات، أخبار، صور، شجرة النسب — أي فرد من العائلة يقدر يديرها.</p></div>
    <div class="step"><span class="num">3</span><b>شارك الرابط</b><p>أرسل الرابط بقروب العائلة مرة واحدة — يثبّتونه كتطبيق، يفعّلون التنبيهات، وكل جديد يوصلهم تلقائيًا.</p></div>
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
    <h2>عائلتك تستاهل أكثر من قروب واتساب</h2>
    <p>موقع رسمي باسمها، تطبيق على جوالات أفرادها، وتنبيه يجمعهم في كل مناسبة — كل هذا بطلب واحد من المجرات</p>
    <a class="btn" href="https://almgrat.com" target="_blank" rel="noopener">اطلب برنامج العائلة الآن 🚀</a>
  </div>
</section>

<footer class="g-footer wrap">
  برنامج العائلة — صفحة تعريفية مرفقة مع كل نسخة من النظام · تطوير <a href="https://almgrat.com" target="_blank" rel="noopener">المجرات</a>
</footer>

</body>
</html>
