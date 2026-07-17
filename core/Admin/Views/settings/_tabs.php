<?php
/** @var string $tab */
use Core\Support\Url;
use Core\View;

$labels = [
    'identity' => 'الهوية', 'appearance' => 'المظهر', 'terms' => 'المصطلحات', 'homepage' => 'الصفحة الرئيسية',
    'directory' => 'جوال العائلة', 'contact' => 'التواصل', 'mail' => 'البريد', 'files' => 'الملفات',
    'seo' => 'SEO', 'maintenance' => 'الصيانة',
];
?>
<div class="tabs">
  <?php foreach ($labels as $key => $label): ?>
    <a class="<?= $tab === $key ? 'active' : '' ?>" href="<?= Url::admin('settings/' . $key) ?>"><?= View::e($label) ?></a>
  <?php endforeach; ?>
</div>
