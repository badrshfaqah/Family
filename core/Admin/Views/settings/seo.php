<?php
/** @var array $settings */
use Core\Support\Csrf;
use Core\Support\Url;
use Core\View;
include __DIR__ . '/_tabs.php';
?>
<form method="post" action="<?= Url::admin('settings/seo/save') ?>" class="card-box">
  <?= Csrf::field() ?>
  <div class="form-group"><label>العنوان الافتراضي (Meta Title)</label><input class="form-control" name="seo_default_title" value="<?= View::e($settings['seo_default_title'] ?? '') ?>"></div>
  <div class="form-group"><label>الوصف الافتراضي (Meta Description)</label><textarea class="form-control" name="seo_default_description" rows="3"><?= View::e($settings['seo_default_description'] ?? '') ?></textarea></div>
  <p class="form-hint">خريطة الموقع متوفرة تلقائيًا على sitemap.xml وملف robots.txt يمنع فهرسة لوحة التحكم ومعالج التثبيت والبيانات الحساسة.</p>
  <button class="btn btn-primary" type="submit">حفظ</button>
</form>
