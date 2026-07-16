<?php
/** @var array $roles */
use Core\Support\Url;
use Core\View;
?>
<div class="table-wrap">
  <table>
    <thead><tr><th>الدور</th><th>نوع</th><th></th></tr></thead>
    <tbody>
    <?php foreach ($roles as $r): ?>
      <tr>
        <td><?= View::e($r['name']) ?></td>
        <td><?= $r['is_system'] ? 'دور أساسي' : 'مخصص' ?></td>
        <td>
          <?php if ($r['slug'] === 'system_admin'): ?>
            <span class="form-hint">يملك جميع الصلاحيات دائمًا</span>
          <?php else: ?>
            <a class="btn btn-secondary btn-sm" href="<?= Url::admin('roles/' . $r['id'] . '/edit') ?>">تعديل الصلاحيات</a>
          <?php endif; ?>
        </td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
</div>
