<?php
/** @var array $catalog
 *  @var array $extraPermissions
 */
use Core\Support\Csrf;
use Core\Support\Url;
use Core\View;
?>
<form method="post" action="<?= Url::admin('roles/store') ?>" class="card-box">
  <?= Csrf::field() ?>
  <div class="form-group" style="max-width:360px">
    <label>اسم الدور</label>
    <input class="form-control" name="name" required placeholder="مثال: مدير الفعاليات">
  </div>

  <?php foreach ($catalog as $group): ?>
    <h3 style="font-size:.95rem"><?= View::e($group['label']) ?></h3>
    <?php foreach ($group['items'] as $key => $label): ?>
      <label class="switch-row" style="font-weight:400">
        <span><?= View::e(str_replace('{term}', \Core\Terms::name(), $label)) ?></span>
        <input type="checkbox" name="permissions[]" value="<?= $key ?>">
      </label>
    <?php endforeach; ?>
  <?php endforeach; ?>

  <?php if (!empty($extraPermissions)): ?>
    <h3 style="font-size:.95rem">صلاحيات الإضافات</h3>
    <?php foreach ($extraPermissions as $p): ?>
      <label class="switch-row" style="font-weight:400">
        <span><?= View::e($p['label']) ?> <small class="form-hint">(<?= View::e($p['module_slug']) ?>)</small></span>
        <input type="checkbox" name="permissions[]" value="<?= View::e($p['permission_key']) ?>">
      </label>
    <?php endforeach; ?>
  <?php endif; ?>

  <div style="margin-top:16px"><button class="btn btn-primary" type="submit">إضافة الدور</button></div>
</form>
