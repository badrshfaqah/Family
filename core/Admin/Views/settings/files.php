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
  <button class="btn btn-primary" type="submit">حفظ</button>
</form>
