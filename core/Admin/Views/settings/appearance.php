<?php
/** @var array $settings */
use Core\Support\Csrf;
use Core\Support\Url;
use Core\View;
include __DIR__ . '/_tabs.php';

$presets = [
    ['name' => 'أخضر تقليدي', 'primary' => '#0f6e5e', 'secondary' => '#c9a24b'],
    ['name' => 'أزرق هادئ', 'primary' => '#1e4d6b', 'secondary' => '#d9a441'],
    ['name' => 'بني ذهبي', 'primary' => '#5c4326', 'secondary' => '#b08d57'],
    ['name' => 'كحلي رسمي', 'primary' => '#1a2b4c', 'secondary' => '#9aa5b1'],
    ['name' => 'زيتوني', 'primary' => '#4b5320', 'secondary' => '#c2b280'],
    ['name' => 'عنابي', 'primary' => '#5e1f2b', 'secondary' => '#c9a24b'],
];

$currentPrimary = $settings['theme_color_primary'] ?? '#0f6e5e';
$currentSecondary = $settings['theme_color_secondary'] ?? '#c9a24b';
$currentFont = $settings['theme_font'] ?? 'default';
?>
<form method="post" action="<?= Url::admin('settings/appearance/save') ?>" class="card-box">
  <?= Csrf::field() ?>

  <div class="form-group">
    <label>نمط ألوان جاهز</label>
    <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(140px,1fr));gap:10px;margin-top:6px">
      <?php foreach ($presets as $p): ?>
        <button type="button" class="btn-preset" data-primary="<?= $p['primary'] ?>" data-secondary="<?= $p['secondary'] ?>"
          style="border:1px solid var(--a-border);border-radius:10px;padding:10px;background:#fff;cursor:pointer;text-align:right">
          <div style="display:flex;gap:4px;margin-bottom:6px">
            <span style="width:22px;height:22px;border-radius:6px;background:<?= $p['primary'] ?>;display:inline-block"></span>
            <span style="width:22px;height:22px;border-radius:6px;background:<?= $p['secondary'] ?>;display:inline-block"></span>
          </div>
          <span style="font-size:.82rem"><?= View::e($p['name']) ?></span>
        </button>
      <?php endforeach; ?>
    </div>
  </div>

  <div class="form-row cols-2" style="margin-top:16px">
    <div class="form-group">
      <label>اللون الأساسي</label>
      <input class="form-control" type="color" id="theme_color_primary" name="theme_color_primary" value="<?= View::e($currentPrimary) ?>" style="height:44px">
    </div>
    <div class="form-group">
      <label>اللون الثانوي</label>
      <input class="form-control" type="color" id="theme_color_secondary" name="theme_color_secondary" value="<?= View::e($currentSecondary) ?>" style="height:44px">
    </div>
  </div>

  <div class="form-group" style="max-width:320px">
    <label>الخط</label>
    <select class="form-control" name="theme_font">
      <option value="default" <?= $currentFont === 'default' ? 'selected' : '' ?>>افتراضي (Tajawal)</option>
      <option value="traditional" <?= $currentFont === 'traditional' ? 'selected' : '' ?>>تقليدي</option>
      <option value="modern" <?= $currentFont === 'modern' ? 'selected' : '' ?>>عصري (Segoe)</option>
    </select>
  </div>

  <button class="btn btn-primary" type="submit">حفظ</button>
</form>

<script nonce="<?= \Core\Support\Security::cspNonce() ?>">
document.querySelectorAll('.btn-preset').forEach(function (btn) {
  btn.addEventListener('click', function () {
    document.getElementById('theme_color_primary').value = btn.getAttribute('data-primary');
    document.getElementById('theme_color_secondary').value = btn.getAttribute('data-secondary');
  });
});
</script>
