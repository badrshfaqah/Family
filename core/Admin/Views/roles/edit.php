<?php
/** @var array $role
 *  @var array $catalog
 *  @var array $extraPermissions
 *  @var array $assigned
 */
use Core\Support\Csrf;
use Core\Support\Url;
use Core\View;
?>
<form method="post" action="<?= Url::admin('roles/' . $role['id'] . '/update') ?>" class="card-box">
  <?= Csrf::field() ?>
  <?php foreach ($catalog as $group): ?>
    <h3 style="font-size:.95rem"><?= View::e($group['label']) ?></h3>
    <?php foreach ($group['items'] as $key => $label): ?>
      <label class="switch-row" style="font-weight:400">
        <span><?= View::e(str_replace('{term}', \Core\Terms::name(), $label)) ?></span>
        <input type="checkbox" name="permissions[]" value="<?= $key ?>" <?= in_array($key, $assigned, true) ? 'checked' : '' ?>>
      </label>
    <?php endforeach; ?>
  <?php endforeach; ?>

  <?php if (!empty($extraPermissions)): ?>
    <h3 style="font-size:.95rem">صلاحيات الإضافات</h3>
    <?php foreach ($extraPermissions as $p): ?>
      <label class="switch-row" style="font-weight:400">
        <span><?= View::e($p['label']) ?> <small class="form-hint">(<?= View::e($p['module_slug']) ?>)</small></span>
        <input type="checkbox" name="permissions[]" value="<?= View::e($p['permission_key']) ?>" <?= in_array($p['permission_key'], $assigned, true) ? 'checked' : '' ?>>
      </label>
    <?php endforeach; ?>
  <?php endif; ?>

  <div style="margin-top:16px"><button class="btn btn-primary" type="submit">حفظ الصلاحيات</button></div>
</form>
