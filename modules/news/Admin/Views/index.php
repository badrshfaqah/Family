<?php
/** @var array $rows */
use Core\Support\Csrf;
use Core\Support\Url;
use Core\View;

$statusLabels = ['draft' => 'مسودة', 'published' => 'منشور', 'scheduled' => 'مجدول', 'archived' => 'مؤرشف'];
$statusBadge = ['draft' => 'badge-gray', 'published' => 'badge-green', 'scheduled' => 'badge-gray', 'archived' => 'badge-gray'];
?>
<div style="display:flex;justify-content:flex-end;gap:8px;margin-bottom:14px">
  <a class="btn btn-secondary" href="<?= Url::admin('news-categories') ?>">التصنيفات</a>
  <a class="btn btn-primary" href="<?= Url::admin('news/create') ?>">+ إضافة خبر</a>
</div>
<div class="table-wrap">
  <table>
    <thead><tr><th>العنوان</th><th>التصنيف</th><th>الحالة</th><th>مثبت</th><th>مميز</th><th>تاريخ النشر</th><th></th></tr></thead>
    <tbody>
    <?php foreach ($rows as $r): ?>
      <tr>
        <td><?= View::e($r['title']) ?></td>
        <td><?= View::e($r['category_name'] ?? '—') ?></td>
        <td><span class="badge <?= $statusBadge[$r['status']] ?>"><?= $statusLabels[$r['status']] ?></span></td>
        <td><?= $r['is_pinned'] ? '✓' : '—' ?></td>
        <td><?= $r['is_featured'] ? '✓' : '—' ?></td>
        <td><?= View::e($r['published_at'] ?? '—') ?></td>
        <td style="display:flex;gap:6px">
          <a class="btn btn-secondary btn-sm" href="<?= Url::admin('news/' . $r['id'] . '/edit') ?>">تعديل</a>
          <form method="post" action="<?= Url::admin('news/' . $r['id'] . '/delete') ?>" data-confirm="حذف هذا الخبر؟"><?= Csrf::field() ?><button class="btn btn-danger btn-sm" type="submit">حذف</button></form>
        </td>
      </tr>
    <?php endforeach; ?>
    <?php if (empty($rows)): ?><tr><td colspan="7" class="form-hint">لا توجد أخبار بعد.</td></tr><?php endif; ?>
    </tbody>
  </table>
</div>
