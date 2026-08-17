<?php
/** @var array $item */
use Core\Settings;
use Core\Support\Url;
use Core\View;

$siteName = Settings::get('identity_short_name', '') ?: Settings::get('identity_official_name', '');
$venueLabel = trim(implode(' — ', array_filter([$item['condolence_venue'] ?? '', $item['condolence_times'] ?? ''])));
?>
<div class="container section">
  <div class="breadcrumbs"><a href="<?= Url::to('') ?>">الرئيسية</a> / <a href="<?= Url::to('obituaries') ?>">الوفيات</a> / <?= View::e($item['name']) ?></div>

  <div class="obit-show">
    <div class="obit-show-head">
      <span class="obit-show-icon">🕊</span>
      <p class="obit-show-eulogy">انتقل إلى رحمة الله</p>
      <h1><?= View::e($item['name']) ?></h1>
      <p class="obit-verse">﴿يَا أَيَّتُهَا النَّفْسُ الْمُطْمَئِنَّةُ ارْجِعِي إِلَىٰ رَبِّكِ رَاضِيَةً مَّرْضِيَّةً﴾</p>
      <p class="form-hint" style="margin:0">إنا لله وإنا إليه راجعون</p>
      <div class="ornament" aria-hidden="true" style="color:#8a8478">❖ ✦ ❖</div>
    </div>

    <div class="obit-details">
      <?php if ($item['deceased_on']): ?>
        <div class="obit-row"><span class="obit-label">🗓 تاريخ الوفاة</span><span><?= View::e(date('Y/m/d', strtotime($item['deceased_on']))) ?></span></div>
      <?php endif; ?>
      <?php if (!empty($item['city_name'])): ?>
        <div class="obit-row"><span class="obit-label">🏙 المدينة</span><span><?= View::e($item['city_name']) ?></span></div>
      <?php endif; ?>
      <?php if (!empty($item['condolence_venue'])): ?>
        <div class="obit-row"><span class="obit-label">📍 مكان العزاء</span><span><?= View::e($item['condolence_venue']) ?></span></div>
      <?php endif; ?>
      <?php if (!empty($item['condolence_times'])): ?>
        <div class="obit-row"><span class="obit-label">🕰 أوقات العزاء</span><span><?= View::e($item['condolence_times']) ?></span></div>
      <?php endif; ?>
      <?php if (!empty($item['condolence_map_url'])): ?>
        <div class="obit-row"><span class="obit-label">🗺 الموقع</span><span><a href="<?= View::e($item['condolence_map_url']) ?>" target="_blank" rel="noopener" style="color:var(--c-primary);font-weight:700">فتح موقع العزاء على الخريطة ←</a></span></div>
      <?php endif; ?>
    </div>

    <?php if (!empty($item['details'])): ?>
      <div class="obit-extra"><?= nl2br(View::e($item['details'])) ?></div>
    <?php endif; ?>

    <div class="share-row">
      <button class="btn btn-sm btn-outline-dark" data-share-card="obituary"
              data-name="<?= View::e($item['name']) ?>"
              data-date="<?= $item['deceased_on'] ? View::e(date('Y/m/d', strtotime($item['deceased_on']))) : '' ?>"
              data-city="<?= View::e($item['city_name'] ?? '') ?>"
              data-venue="<?= View::e($venueLabel) ?>"
              data-site="<?= View::e($siteName) ?>"
              data-url="<?= View::e(Url::current()) ?>">تصدير كصورة 🖼</button>
    </div>
  </div>
</div>
