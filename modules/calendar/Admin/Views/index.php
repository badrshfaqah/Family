<?php
/** @var array $rows */
use Core\Support\Csrf;
use Core\Support\Url;
use Core\View;

$statusLabels = ['draft' => 'مسودة', 'published' => 'منشور', 'cancelled' => 'ملغى'];
$statusBadge = ['draft' => 'badge-gray', 'published' => 'badge-green', 'cancelled' => 'badge-gray'];
?>
<div style="display:flex;justify-content:flex-end;gap:8px;margin-bottom:14px">
  <a class="btn btn-primary" href="<?= Url::admin('calendar/create') ?>">+ إضافة موعد</a>
</div>
<div class="table-wrap">
  <table>
    <thead><tr><th>العنوان</th><th>النوع</th><th>التاريخ والوقت</th><th>المدينة</th><th>مرتبط بمناسبة</th><th>الحالة</th><th></th></tr></thead>
    <tbody>
    <?php foreach ($rows as $r): ?>
      <tr>
        <td><?= View::e($r['title']) ?></td>
        <td><?= View::e($r['entry_type']) ?></td>
        <td><?= View::e(date('Y/m/d H:i', strtotime($r['entry_datetime']))) ?></td>
        <td><?= View::e($r['city_name'] ?? '—') ?></td>
        <td><?= View::e($r['event_title'] ?? '—') ?></td>
        <td><span class="badge <?= $statusBadge[$r['status']] ?>"><?= $statusLabels[$r['status']] ?></span></td>
        <td style="display:flex;gap:6px">
          <a class="btn btn-secondary btn-sm" href="<?= Url::admin('calendar/' . $r['id'] . '/edit') ?>">تعديل</a>
          <form method="post" action="<?= Url::admin('calendar/' . $r['id'] . '/delete') ?>" data-confirm="حذف هذا الموعد؟"><?= Csrf::field() ?><button class="btn btn-danger btn-sm" type="submit">حذف</button></form>
        </td>
      </tr>
    <?php endforeach; ?>
    <?php if (empty($rows)): ?><tr><td colspan="7" class="form-hint">لا توجد مواعيد بعد.</td></tr><?php endif; ?>
    </tbody>
  </table>
</div>
