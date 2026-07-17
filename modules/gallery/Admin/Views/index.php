<?php
/** @var array $rows */
use Core\Support\Csrf;
use Core\Support\Url;
use Core\View;

$statusLabels = ['draft' => 'مسودة', 'published' => 'منشور'];
$statusBadge = ['draft' => 'badge-gray', 'published' => 'badge-green'];
$typeLabels = ['photo' => 'صور', 'video' => 'فيديو'];
?>
<div style="display:flex;justify-content:flex-end;gap:8px;margin-bottom:14px">
  <a class="btn btn-primary" href="<?= Url::admin('gallery/create') ?>">+ إضافة ألبوم</a>
</div>
<div class="table-wrap">
  <table>
    <thead><tr><th>العنوان</th><th>النوع</th><th>السنة</th><th>المدينة</th><th>عدد الصور</th><th>الحالة</th><th></th></tr></thead>
    <tbody>
    <?php foreach ($rows as $r): ?>
      <tr>
        <td><?= View::e($r['title']) ?></td>
        <td><?= View::e($typeLabels[$r['album_type']] ?? $r['album_type']) ?></td>
        <td><?= View::e((string) ($r['year'] ?? '—')) ?></td>
        <td><?= View::e($r['city_name'] ?? '—') ?></td>
        <td><?= (int) $r['photos_count'] ?></td>
        <td><span class="badge <?= $statusBadge[$r['status']] ?>"><?= $statusLabels[$r['status']] ?></span></td>
        <td style="display:flex;gap:6px">
          <a class="btn btn-secondary btn-sm" href="<?= Url::admin('gallery/' . $r['id'] . '/edit') ?>">تعديل</a>
          <form method="post" action="<?= Url::admin('gallery/' . $r['id'] . '/delete') ?>" data-confirm="حذف هذا الألبوم وكل صوره؟"><?= Csrf::field() ?><button class="btn btn-danger btn-sm" type="submit">حذف</button></form>
        </td>
      </tr>
    <?php endforeach; ?>
    <?php if (empty($rows)): ?><tr><td colspan="7" class="form-hint">لا توجد ألبومات بعد.</td></tr><?php endif; ?>
    </tbody>
  </table>
</div>
