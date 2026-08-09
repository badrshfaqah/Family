<?php
/** @var array $rows
 *  @var array $pending */
use Core\Support\Csrf;
use Core\Support\Url;
use Core\View;

$statusLabels = ['draft' => 'مسودة', 'published' => 'منشور', 'cancelled' => 'ملغى', 'pending' => 'بانتظار الموافقة'];
$statusBadge = ['draft' => 'badge-gray', 'published' => 'badge-green', 'cancelled' => 'badge-gray'];
?>
<?php if (!empty($pending)): ?>
<div style="background:#fff8ec;border:1.5px solid #e0b95a;border-radius:12px;padding:14px 16px;margin-bottom:18px">
  <p style="margin:0 0 10px;font-weight:800">⏳ مقترحات الزوار بانتظار الموافقة (<?= count($pending) ?>)</p>
  <?php foreach ($pending as $pd): ?>
  <div style="display:flex;gap:12px;align-items:center;flex-wrap:wrap;background:#fff;border:1px solid #eadfc5;border-radius:10px;padding:10px 12px;margin-bottom:8px">
    <div style="flex:1;min-width:220px">
      <b><?= View::e($pd['title']) ?></b>
      <div style="color:#8b8574;font-size:.8rem;margin-top:2px">
        🏷 <?= View::e($pd['entry_type']) ?> · 🗓 <?= View::e(date('Y/m/d', strtotime($pd['entry_datetime']))) ?>
        <?php if (!empty($pd['city_name'])): ?> · 🏙 <?= View::e($pd['city_name']) ?><?php endif; ?>
        <?php if (!empty($pd['venue_name'])): ?> · 📍 <?= View::e($pd['venue_name']) ?><?php endif; ?>
      </div>
      <div style="color:#5d5745;font-size:.78rem;margin-top:4px">
        👤 المرسل: <b><?= View::e($pd['submitted_name'] ?? '؟') ?></b>
        · 📱 <span dir="ltr"><?= View::e($pd['submitted_phone'] ?? '؟') ?></span>
      </div>
      <?php if (!empty($pd['notes'])): ?><div style="color:#777;font-size:.78rem;margin-top:3px">📝 <?= View::e($pd['notes']) ?></div><?php endif; ?>
    </div>
    <div style="display:flex;gap:6px;flex:none">
      <form method="post" action="<?= Url::admin('calendar/' . $pd['id'] . '/approve') ?>"><?= Csrf::field() ?><button class="btn btn-primary btn-sm" type="submit">✅ اعتماد ونشر</button></form>
      <form method="post" action="<?= Url::admin('calendar/' . $pd['id'] . '/reject') ?>"><?= Csrf::field() ?><button class="btn btn-danger btn-sm" type="submit" data-confirm="رفض هذا الاقتراح وحذفه نهائيًا؟">رفض</button></form>
    </div>
  </div>
  <?php endforeach; ?>
</div>
<?php endif; ?>

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
        <td><?= View::e(date('Y/m/d', strtotime($r['entry_datetime']))) ?></td>
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
