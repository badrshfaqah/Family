<?php
/** @var array $rows */
use Core\Support\Csrf;
use Core\Support\Url;
use Core\View;

$statusLabels = ['draft' => 'مسودة', 'published' => 'منشور'];
$statusBadge = ['draft' => 'badge-gray', 'published' => 'badge-green'];
?>
<div style="display:flex;justify-content:flex-end;gap:8px;margin-bottom:14px">
  <a class="btn btn-secondary" href="<?= Url::admin('archive-categories') ?>">التصنيفات</a>
  <a class="btn btn-primary" href="<?= Url::admin('archive/create') ?>">+ إضافة عنصر</a>
</div>
<div class="table-wrap">
  <table>
    <thead><tr><th>العنوان</th><th>التصنيف</th><th>الفترة الزمنية</th><th>الحالة</th><th></th></tr></thead>
    <tbody>
    <?php foreach ($rows as $r): ?>
      <tr>
        <td><?= View::e($r['title']) ?></td>
        <td><?= View::e($r['category_name'] ?? '—') ?></td>
        <td><?= View::e($r['period_label'] ?? '—') ?></td>
        <td><span class="badge <?= $statusBadge[$r['status']] ?>"><?= $statusLabels[$r['status']] ?></span></td>
        <td style="display:flex;gap:6px">
          <a class="btn btn-secondary btn-sm" href="<?= Url::admin('archive/' . $r['id'] . '/edit') ?>">تعديل</a>
          <form method="post" action="<?= Url::admin('archive/' . $r['id'] . '/delete') ?>" data-confirm="حذف هذا العنصر؟"><?= Csrf::field() ?><button class="btn btn-danger btn-sm" type="submit">حذف</button></form>
        </td>
      </tr>
    <?php endforeach; ?>
    <?php if (empty($rows)): ?><tr><td colspan="5" class="form-hint">لا توجد عناصر أرشيف بعد.</td></tr><?php endif; ?>
    </tbody>
  </table>
</div>
