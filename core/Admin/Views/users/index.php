<?php
/** @var array $users */
use Core\Support\Csrf;
use Core\Support\Url;
use Core\View;
?>
<div style="display:flex;justify-content:flex-end;margin-bottom:14px">
  <a class="btn btn-primary" href="<?= Url::admin('users/create') ?>">+ إضافة مستخدم</a>
</div>
<div class="table-wrap">
  <table>
    <thead><tr><th>الاسم</th><th>اسم المستخدم</th><th>البريد</th><th>الدور</th><th>الحالة</th><th>آخر دخول</th><th></th></tr></thead>
    <tbody>
    <?php foreach ($users as $u): ?>
      <tr>
        <td><?= View::e($u['name']) ?></td>
        <td><?= View::e($u['username']) ?></td>
        <td><?= View::e($u['email']) ?></td>
        <td><?= View::e($u['role_name']) ?></td>
        <td><span class="badge <?= $u['status'] === 'active' ? 'badge-green' : 'badge-gray' ?>"><?= $u['status'] === 'active' ? 'مفعل' : 'معطل' ?></span></td>
        <td><?= View::e($u['last_login_at'] ?? '—') ?></td>
        <td style="display:flex;gap:6px">
          <form method="post" action="<?= Url::admin('users/' . $u['id'] . '/toggle') ?>"><?= Csrf::field() ?><button class="btn btn-secondary btn-sm" type="submit"><?= $u['status'] === 'active' ? 'تعطيل' : 'تفعيل' ?></button></form>
          <form method="post" action="<?= Url::admin('users/' . $u['id'] . '/delete') ?>" data-confirm="هل تريد حذف هذا المستخدم؟"><?= Csrf::field() ?><button class="btn btn-danger btn-sm" type="submit">حذف</button></form>
        </td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
</div>
