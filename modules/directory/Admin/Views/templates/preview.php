<?php
/** @var array $item
 *  @var array $placeholders
 *  @var array $values
 *  @var string $rendered
 */
use Core\Support\Url;
use Core\View;

$placeholderLabels = [
    'event_name' => 'اسم المناسبة',
    'date' => 'التاريخ',
    'time' => 'الوقت',
    'city' => 'المدينة',
    'link' => 'رابط (اختياري)',
];
?>
<form method="get" action="<?= Url::admin('directory-templates/' . $item['id'] . '/preview') ?>" class="card-box" style="max-width:640px">
  <?php if (empty($placeholders)): ?>
    <p class="form-hint">لا توجد متغيرات في هذا القالب.</p>
  <?php else: ?>
    <div class="form-row cols-2">
      <?php foreach ($placeholders as $key): ?>
        <div class="form-group">
          <label><?= View::e($placeholderLabels[$key] ?? $key) ?> <code>{<?= View::e($key) ?>}</code></label>
          <input class="form-control" name="<?= View::e($key) ?>" value="<?= View::e($values[$key] ?? '') ?>">
        </div>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
  <button class="btn btn-primary" type="submit">تحديث المعاينة</button>
</form>

<div class="card-box" style="max-width:640px">
  <h3 style="margin-top:0;font-size:1rem">النص النهائي</h3>
  <textarea id="dir-tpl-rendered" class="form-control" rows="8" readonly><?= View::e($rendered) ?></textarea>
  <button type="button" class="btn btn-secondary" style="margin-top:10px" onclick="fam_copy_dir_tpl()">نسخ النص</button>
  <span id="dir-tpl-copied" class="form-hint" style="display:none;color:#1c6e3c">تم نسخ النص.</span>
</div>

<script>
function fam_copy_dir_tpl() {
  var el = document.getElementById('dir-tpl-rendered');
  el.select();
  navigator.clipboard && navigator.clipboard.writeText ? navigator.clipboard.writeText(el.value) : document.execCommand('copy');
  var msg = document.getElementById('dir-tpl-copied');
  msg.style.display = 'inline';
  setTimeout(function () { msg.style.display = 'none'; }, 2000);
}
</script>
