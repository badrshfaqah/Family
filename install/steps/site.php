<?php
use Core\Support\Csrf;
use Core\Support\Security;

$site = $state['site'] ?? [];
?>
<p>أدخل اسم <?= $site['termMode'] ?? '' ?> واختر المصطلح الأساسي المستخدم في الموقع (يمكن تغييره لاحقًا من الإعدادات).</p>
<form method="post">
  <?= Csrf::field() ?>
  <div class="form-group"><label>الاسم الرسمي</label><input class="form-control" name="official_name" value="<?= Security::e($site['officialName'] ?? '') ?>" required></div>
  <div class="form-group"><label>الاسم المختصر (اختياري)</label><input class="form-control" name="short_name" value="<?= Security::e($site['shortName'] ?? '') ?>"></div>

  <div class="form-group">
    <label>المصطلح الأساسي</label>
    <?php $presets = ['family' => 'عائلة', 'tribe' => 'قبيلة', 'league' => 'رابطة', 'diwaniyah' => 'ديوانية', 'group' => 'جماعة']; ?>
    <?php foreach ($presets as $key => $label): ?>
      <label style="display:flex;align-items:center;gap:8px;font-weight:400;padding:6px 0">
        <input type="radio" name="term_mode" value="<?= $key ?>" <?= ($site['termMode'] ?? 'family') === $key ? 'checked' : '' ?>>
        <?= $label ?>
      </label>
    <?php endforeach; ?>
    <label style="display:flex;align-items:center;gap:8px;font-weight:400;padding:6px 0">
      <input type="radio" name="term_mode" value="custom" <?= ($site['termMode'] ?? '') === 'custom' ? 'checked' : '' ?>>
      مسمى مخصص
    </label>
  </div>
  <div class="form-group"><label>المسمى المخصص (عند اختياره أعلاه)</label><input class="form-control" name="term_custom" value="<?= Security::e($site['termCustom'] ?? '') ?>"></div>

  <p class="form-hint">يمكن رفع الشعار وصورة الغلاف لاحقًا من الإعدادات بعد الدخول للوحة التحكم.</p>
  <button class="btn btn-primary" type="submit" style="width:100%">التالي</button>
</form>
