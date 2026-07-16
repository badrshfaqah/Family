<?php
/** @var array $rows */
use Core\Support\Csrf;
use Core\Support\Url;
use Core\View;

$statusLabels = ['pending' => 'بانتظار المراجعة', 'approved' => 'معتمد', 'rejected' => 'مرفوض'];
$statusBadge = ['pending' => 'badge-gray', 'approved' => 'badge-green', 'rejected' => 'badge-red'];
?>
<p class="form-hint">
  طلبات إلغاء اشتراك واردة من الموقع العام دون تسجيل دخول للزائر. لا يُنفَّذ أي حذف أو استبعاد تلقائيًا؛
  يجب اعتماد الطلب من هنا أولًا، ويُسجَّل كل إجراء في سجل العمليات.
</p>

<div class="table-wrap">
  <table>
    <thead><tr><th>الجوال</th><th>تاريخ الطلب</th><th>الحالة</th><th>تمت المراجعة في</th><th></th></tr></thead>
    <tbody>
    <?php foreach ($rows as $r): ?>
      <tr>
        <td dir="ltr"><?= View::e($r['phone']) ?></td>
        <td><?= View::e($r['requested_at']) ?></td>
        <td><span class="badge <?= $statusBadge[$r['status']] ?>"><?= $statusLabels[$r['status']] ?></span></td>
        <td><?= View::e($r['reviewed_at'] ?? '—') ?></td>
        <td>
          <?php if ($r['status'] === 'pending'): ?>
            <form method="post" action="<?= Url::admin('directory/removal-requests/' . $r['id'] . '/approve') ?>" style="display:flex;gap:8px;align-items:center;flex-wrap:wrap" data-confirm="اعتماد طلب إلغاء الاشتراك لهذا الرقم؟">
              <?= Csrf::field() ?>
              <label style="font-weight:400;display:flex;gap:4px;align-items:center"><input type="checkbox" name="hard_delete" value="1"> حذف نهائي (بدلًا من الاستبعاد فقط)</label>
              <button class="btn btn-primary btn-sm" type="submit">اعتماد</button>
            </form>
            <form method="post" action="<?= Url::admin('directory/removal-requests/' . $r['id'] . '/reject') ?>" style="margin-top:6px" data-confirm="رفض هذا الطلب؟">
              <?= Csrf::field() ?>
              <button class="btn btn-secondary btn-sm" type="submit">رفض</button>
            </form>
          <?php endif; ?>
        </td>
      </tr>
    <?php endforeach; ?>
    <?php if (empty($rows)): ?><tr><td colspan="5" class="form-hint">لا توجد طلبات حتى الآن.</td></tr><?php endif; ?>
    </tbody>
  </table>
</div>
