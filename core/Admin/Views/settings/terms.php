<?php
/** @var array $settings */
use Core\Support\Csrf;
use Core\Support\Url;
use Core\Terms;
use Core\View;
include __DIR__ . '/_tabs.php';
$mode = $settings['term_mode'] ?? 'family';
?>
<form method="post" action="<?= Url::admin('settings/terms/save') ?>" class="card-box">
  <?= Csrf::field() ?>
  <p class="form-hint">اختر المصطلح الأساسي الذي سيظهر تلقائيًا في كل نصوص الموقع (جوال العائلة، أخبار العائلة، شجرة العائلة...).</p>
  <div class="form-group">
    <?php foreach (Terms::PRESETS as $key => $label): ?>
      <label style="display:flex;align-items:center;gap:8px;font-weight:400;padding:8px 0">
        <input type="radio" name="term_mode" value="<?= $key ?>" <?= $mode === $key ? 'checked' : '' ?>>
        <?= View::e($label) ?>
      </label>
    <?php endforeach; ?>
    <label style="display:flex;align-items:center;gap:8px;font-weight:400;padding:8px 0">
      <input type="radio" name="term_mode" value="custom" <?= $mode === 'custom' ? 'checked' : '' ?>>
      مسمى مخصص
    </label>
  </div>
  <div class="form-group">
    <label>المسمى المخصص (يُستخدم فقط عند اختيار "مسمى مخصص")</label>
    <input class="form-control" name="term_custom" value="<?= View::e($settings['term_custom'] ?? '') ?>" placeholder="مثال: قبيلة بني فلان">
  </div>
  <button class="btn btn-primary" type="submit">حفظ</button>
</form>
