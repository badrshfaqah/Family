<?php
/** @var array $roles */
use Core\Support\Csrf;
use Core\Support\Url;
use Core\View;
?>
<div style="display:flex;justify-content:flex-end;margin-bottom:14px">
  <a class="btn btn-primary" href="<?= Url::admin('roles/create') ?>">+ إضافة دور جديد</a>
</div>
<div class="table-wrap">
  <table>
    <thead><tr><th>الدور</th><th>نوع</th><th></th></tr></thead>
    <tbody>
    <?php foreach ($roles as $r): ?>
      <tr>
        <td><?= View::e($r['name']) ?></td>
        <td><?= $r['is_system'] ? 'دور أساسي' : 'مخصص' ?></td>
        <td style="display:flex;gap:6px">
          <?php if ($r['slug'] === 'system_admin'): ?>
            <span class="form-hint">يملك جميع الصلاحيات دائمًا</span>
          <?php else: ?>
            <a class="btn btn-secondary btn-sm" href="<?= Url::admin('roles/' . $r['id'] . '/edit') ?>">تعديل الصلاحيات</a>
            <?php if (!$r['is_system']): ?>
              <form method="post" action="<?= Url::admin('roles/' . $r['id'] . '/delete') ?>" data-confirm="حذف هذا الدور؟ يجب ألا يكون هناك مستخدمون مرتبطون به.">
                <?= Csrf::field() ?>
                <button class="btn btn-danger btn-sm" type="submit">حذف</button>
              </form>
            <?php endif; ?>
          <?php endif; ?>
        </td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
</div>
