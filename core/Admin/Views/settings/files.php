<?php
/** @var array $settings */
use Core\Support\Csrf;
use Core\Support\Url;
use Core\View;
include __DIR__ . '/_tabs.php';
?>
<form method="post" action="<?= Url::admin('settings/files/save') ?>" class="card-box">
  <?= Csrf::field() ?>
  <div class="form-row cols-2">
    <div class="form-group"><label>الحد الأقصى لحجم الملف (ميجابايت)</label><input class="form-control" type="number" min="1" max="100" name="media_max_size_mb" value="<?= (int) ($settings['media_max_size_mb'] ?? 8) ?>"></div>
    <div class="form-group"><label>امتدادات إضافية مسموحة (افصل بفاصلة)</label><input class="form-control" name="media_allowed_extensions" value="<?= View::e($settings['media_allowed_extensions'] ?? '') ?>" placeholder="مثال: zip,rar"></div>
  </div>
  <p class="form-hint">الامتدادات الأساسية المسموحة دائمًا: jpg, jpeg, png, webp, gif, pdf, doc, docx, xls, xlsx, mp3, mp4.</p>

  <div class="form-row cols-2">
    <div class="form-group">
      <label>جودة ضغط الصور المرفوعة (%)</label>
      <input class="form-control" type="number" min="10" max="100" name="media_image_quality" value="<?= (int) ($settings['media_image_quality'] ?? 85) ?>">
      <div class="form-hint">جودة أقل = حجم ملف أصغر وتحميل أسرع. 85 قيمة متوازنة موصى بها.</div>
    </div>
    <div class="form-group">
      <label>أقصى بُعد للصورة (بكسل)</label>
      <input class="form-control" type="number" min="500" max="6000" name="media_max_dimension" value="<?= (int) ($settings['media_max_dimension'] ?? 2000) ?>">
      <div class="form-hint">تُصغَّر أي صورة يتجاوز عرضها أو ارتفاعها هذا الحد تلقائيًا عند الرفع.</div>
    </div>
  </div>

  <button class="btn btn-primary" type="submit">حفظ</button>
</form>
