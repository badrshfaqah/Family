<?php
/** @var array $categories */
use Core\Support\Csrf;
use Core\Support\Url;
use Core\View;
?>
<div class="card-box">
  <form method="post" action="<?= Url::admin('archive-categories/store') ?>" style="display:flex;gap:10px">
    <?= Csrf::field() ?>
    <input class="form-control" name="name" placeholder="اسم تصنيف جديد" required>
    <button class="btn btn-primary" type="submit">إضافة</button>
  </form>
</div>
<div class="table-wrap">
  <table>
    <thead><tr><th>الاسم</th><th>الرابط</th><th></th></tr></thead>
    <tbody>
    <?php foreach ($categories as $c): ?>
      <tr>
        <td><?= View::e($c['name']) ?></td>
        <td dir="ltr" style="text-align:right"><?= View::e($c['slug']) ?></td>
        <td><form method="post" action="<?= Url::admin('archive-categories/' . $c['id'] . '/delete') ?>" data-confirm="حذف هذا التصنيف؟"><?= Csrf::field() ?><button class="btn btn-danger btn-sm" type="submit">حذف</button></form></td>
      </tr>
    <?php endforeach; ?>
    <?php if (empty($categories)): ?><tr><td colspan="3" class="form-hint">لا توجد تصنيفات بعد.</td></tr><?php endif; ?>
    </tbody>
  </table>
</div>
