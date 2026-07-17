<?php
/** @var array $rows */
use Core\Support\Csrf;
use Core\Support\Url;
use Core\View;
use Modules\Announcements\Admin\Controllers\AnnouncementsAdminController;

$statusLabels = ['active' => 'مفعّل', 'inactive' => 'غير مفعّل'];

function fam_announcement_is_live(array $a): bool
{
    if ($a['status'] !== 'active') {
        return false;
    }
    $now = time();
    if (!empty($a['starts_at']) && strtotime($a['starts_at']) > $now) {
        return false;
    }
    if (!empty($a['ends_at']) && strtotime($a['ends_at']) < $now) {
        return false;
    }
    return true;
}
?>
<div style="display:flex;justify-content:flex-end;gap:8px;margin-bottom:14px">
  <a class="btn btn-primary" href="<?= Url::admin('announcements/create') ?>">+ إضافة إعلان</a>
</div>
<div class="table-wrap">
  <table>
    <thead><tr><th>العنوان</th><th>النوع</th><th>مكان العرض</th><th>الحالة</th><th>الفترة</th><th>مباشر الآن؟</th><th></th></tr></thead>
    <tbody>
    <?php foreach ($rows as $r): ?>
      <tr>
        <td><?= View::e($r['title']) ?></td>
        <td><?= View::e($r['announcement_type'] ?? '—') ?></td>
        <td><?= View::e(AnnouncementsAdminController::PLACEMENT_LABELS[$r['placement']] ?? $r['placement']) ?></td>
        <td><span class="badge <?= $r['status'] === 'active' ? 'badge-green' : 'badge-gray' ?>"><?= $statusLabels[$r['status']] ?></span></td>
        <td>
          <?= $r['starts_at'] ? View::e(date('Y/m/d H:i', strtotime($r['starts_at']))) : '—' ?>
          →
          <?= $r['ends_at'] ? View::e(date('Y/m/d H:i', strtotime($r['ends_at']))) : '—' ?>
        </td>
        <td><span class="badge <?= fam_announcement_is_live($r) ? 'badge-green' : 'badge-gray' ?>"><?= fam_announcement_is_live($r) ? '● مباشر' : '—' ?></span></td>
        <td style="display:flex;gap:6px">
          <a class="btn btn-secondary btn-sm" href="<?= Url::admin('announcements/' . $r['id'] . '/edit') ?>">تعديل</a>
          <form method="post" action="<?= Url::admin('announcements/' . $r['id'] . '/delete') ?>" data-confirm="حذف هذا الإعلان؟"><?= Csrf::field() ?><button class="btn btn-danger btn-sm" type="submit">حذف</button></form>
        </td>
      </tr>
    <?php endforeach; ?>
    <?php if (empty($rows)): ?><tr><td colspan="7" class="form-hint">لا توجد إعلانات بعد.</td></tr><?php endif; ?>
    </tbody>
  </table>
</div>
