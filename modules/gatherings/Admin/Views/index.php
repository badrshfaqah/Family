<?php
/** @var array $rows */
use Core\Support\Csrf;
use Core\Support\Url;
use Core\View;

$statusLabels = ['active' => 'نشطة', 'paused' => 'متوقفة مؤقتًا', 'ended' => 'منتهية'];
$statusBadge = ['active' => 'badge-green', 'paused' => 'badge-gray', 'ended' => 'badge-gray'];
?>
<div style="display:flex;justify-content:flex-end;gap:8px;margin-bottom:14px">
  <a class="btn btn-primary" href="<?= Url::admin('gatherings/create') ?>">+ إضافة جمعة</a>
</div>
<div class="table-wrap">
  <table>
    <thead><tr><th>اسم الجمعة</th><th>المدينة</th><th>التكرار</th><th>الحالة</th><th>تاريخ البداية</th><th></th></tr></thead>
    <tbody>
    <?php foreach ($rows as $r): ?>
      <tr>
        <td><?= View::e($r['title']) ?></td>
        <td><?= View::e($r['city_name'] ?? '—') ?></td>
        <td><?= View::e($r['recurrence_label'] ?? '') ?></td>
        <td><span class="badge <?= $statusBadge[$r['status']] ?? 'badge-gray' ?>"><?= View::e($statusLabels[$r['status']] ?? $r['status']) ?></span></td>
        <td><?= View::e($r['starts_on'] ?? '—') ?></td>
        <td style="display:flex;gap:6px">
          <a class="btn btn-secondary btn-sm" href="<?= Url::admin('gatherings/' . $r['id'] . '/edit') ?>">تعديل</a>
          <form method="post" action="<?= Url::admin('gatherings/' . $r['id'] . '/delete') ?>" data-confirm="حذف هذه الجمعة؟"><?= Csrf::field() ?><button class="btn btn-danger btn-sm" type="submit">حذف</button></form>
        </td>
      </tr>
    <?php endforeach; ?>
    <?php if (empty($rows)): ?><tr><td colspan="6" class="form-hint">لا توجد جمعات بعد.</td></tr><?php endif; ?>
    </tbody>
  </table>
</div>
