<?php
/** @var array $pages */
use Core\Support\Csrf;
use Core\Support\Url;
use Core\View;
?>
<div style="display:flex;justify-content:flex-end;margin-bottom:14px">
  <a class="btn btn-primary" href="<?= Url::admin('pages/create') ?>">+ إضافة صفحة</a>
</div>
<div class="table-wrap">
  <table>
    <thead><tr><th>العنوان</th><th>الرابط</th><th>الحالة</th><th>في القائمة</th><th></th></tr></thead>
    <tbody>
    <?php foreach ($pages as $p): ?>
      <tr>
        <td><?= View::e($p['title']) ?></td>
        <td dir="ltr" style="text-align:right"><?= View::e($p['slug']) ?></td>
        <td><span class="badge <?= $p['status'] === 'published' ? 'badge-green' : 'badge-gray' ?>"><?= $p['status'] === 'published' ? 'منشورة' : 'مسودة' ?></span></td>
        <td><?= $p['show_in_menu'] ? '✓' : '—' ?></td>
        <td style="display:flex;gap:6px">
          <a class="btn btn-secondary btn-sm" href="<?= Url::admin('pages/' . $p['id'] . '/edit') ?>">تعديل</a>
          <form method="post" action="<?= Url::admin('pages/' . $p['id'] . '/delete') ?>" data-confirm="حذف هذه الصفحة؟"><?= Csrf::field() ?><button class="btn btn-danger btn-sm" type="submit">حذف</button></form>
        </td>
      </tr>
    <?php endforeach; ?>
    <?php if (empty($pages)): ?><tr><td colspan="5" class="form-hint">لا توجد صفحات بعد.</td></tr><?php endif; ?>
    </tbody>
  </table>
</div>
