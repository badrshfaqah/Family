<?php
/** @var array $rows */
use Core\Support\Csrf;
use Core\Support\Url;
use Core\View;

$statusLabels = ['draft' => 'مسودة', 'published' => 'منشورة', 'archived' => 'مؤرشفة'];
$statusBadge = ['draft' => 'badge-gray', 'published' => 'badge-green', 'archived' => 'badge-gray'];
?>
<div style="display:flex;justify-content:flex-end;gap:8px;margin-bottom:14px">
  <a class="btn btn-primary" href="<?= Url::admin('events/create') ?>">+ إضافة مناسبة</a>
</div>
<div class="table-wrap">
  <table>
    <thead><tr><th>العنوان</th><th>النوع</th><th>المدينة</th><th>البداية</th><th>الحالة</th><th>مميزة</th><th>مثبتة</th><th></th></tr></thead>
    <tbody>
    <?php foreach ($rows as $r): ?>
      <tr>
        <td><?= View::e($r['title']) ?></td>
        <td><?= View::e($r['event_type'] ?? '—') ?></td>
        <td><?= View::e($r['city_name'] ?? '—') ?></td>
        <td><?= View::e($r['starts_at'] ? date('Y/m/d H:i', strtotime($r['starts_at'])) : '—') ?></td>
        <td><span class="badge <?= $statusBadge[$r['status']] ?>"><?= $statusLabels[$r['status']] ?></span></td>
        <td><?= $r['is_featured'] ? '✓' : '—' ?></td>
        <td><?= $r['is_pinned'] ? '✓' : '—' ?></td>
        <td style="display:flex;gap:6px">
          <a class="btn btn-secondary btn-sm" href="<?= Url::admin('events/' . $r['id'] . '/edit') ?>">تعديل</a>
          <form method="post" action="<?= Url::admin('events/' . $r['id'] . '/delete') ?>" data-confirm="حذف هذه المناسبة؟"><?= Csrf::field() ?><button class="btn btn-danger btn-sm" type="submit">حذف</button></form>
        </td>
      </tr>
    <?php endforeach; ?>
    <?php if (empty($rows)): ?><tr><td colspan="8" class="form-hint">لا توجد مناسبات بعد.</td></tr><?php endif; ?>
    </tbody>
  </table>
</div>
