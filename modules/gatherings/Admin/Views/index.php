<?php
/** @var array $rows
 *  @var array $pending */
use Core\Support\Csrf;
use Core\Support\Url;
use Core\View;

$statusLabels = ['active' => 'نشطة', 'paused' => 'متوقفة مؤقتًا', 'ended' => 'منتهية', 'pending' => 'بانتظار الموافقة'];
$statusBadge = ['active' => 'badge-green', 'paused' => 'badge-gray', 'ended' => 'badge-gray'];
?>
<?php if (!empty($pending)): ?>
<div style="background:#fff8ec;border:1.5px solid #e0b95a;border-radius:12px;padding:14px 16px;margin-bottom:18px">
  <p style="margin:0 0 10px;font-weight:800">⏳ مقترحات الزوار بانتظار الموافقة (<?= count($pending) ?>)</p>
  <?php foreach ($pending as $pd): ?>
  <div style="display:flex;gap:12px;align-items:center;flex-wrap:wrap;background:#fff;border:1px solid #eadfc5;border-radius:10px;padding:10px 12px;margin-bottom:8px">
    <div style="flex:1;min-width:220px">
      <b><?= View::e($pd['title']) ?></b>
      <div style="color:#8b8574;font-size:.8rem;margin-top:2px">
        🗓 <?= View::e($pd['recurrence_label'] ?? '') ?><?php $pdPeriod = \Modules\Gatherings\Support\RecurrenceLabel::periodLabel($pd['time_period'] ?? null); ?><?php if ($pdPeriod !== ''): ?> · 🕗 <?= View::e($pdPeriod) ?><?php elseif (!empty($pd['start_time'])): ?> · 🕗 <?= View::e(substr($pd['start_time'], 0, 5)) ?><?php endif; ?>
        <?php if (!empty($pd['city_name'])): ?> · 🏙 <?= View::e($pd['city_name']) ?><?php endif; ?>
        <?php if (!empty($pd['venue'])): ?> · 📍 <?= View::e($pd['venue']) ?><?php endif; ?>
      </div>
      <div style="color:#5d5745;font-size:.78rem;margin-top:4px">
        👤 المرسل: <b><?= View::e($pd['submitted_name'] ?? '؟') ?></b>
        · 📱 <span dir="ltr"><?= View::e($pd['submitted_phone'] ?? '؟') ?></span>
      </div>
      <?php if (!empty($pd['description'])): ?><div style="color:#777;font-size:.78rem;margin-top:3px">📝 <?= View::e($pd['description']) ?></div><?php endif; ?>
    </div>
    <div style="display:flex;gap:6px;flex:none">
      <form method="post" action="<?= Url::admin('gatherings/' . $pd['id'] . '/approve') ?>"><?= Csrf::field() ?><button class="btn btn-primary btn-sm" type="submit">✅ اعتماد ونشر</button></form>
      <form method="post" action="<?= Url::admin('gatherings/' . $pd['id'] . '/reject') ?>"><?= Csrf::field() ?><button class="btn btn-danger btn-sm" type="submit" data-confirm="رفض هذا الاقتراح وحذفه نهائيًا؟">رفض</button></form>
    </div>
  </div>
  <?php endforeach; ?>
</div>
<?php endif; ?>

<div style="display:flex;justify-content:flex-end;gap:8px;margin-bottom:14px">
  <a class="btn btn-primary" href="<?= Url::admin('gatherings/create') ?>">+ إضافة جمعة</a>
</div>
<div class="table-wrap">
  <table>
    <thead><tr><th style="width:70px">الترتيب</th><th>اسم الجمعة</th><th>المدينة</th><th>التكرار</th><th>الحالة</th><th>تاريخ البداية</th><th></th></tr></thead>
    <tbody>
    <?php $rowCount = count($rows); foreach ($rows as $i => $r): ?>
      <tr>
        <td>
          <div style="display:flex;gap:4px">
            <form method="post" action="<?= Url::admin('gatherings/' . $r['id'] . '/move') ?>"><?= Csrf::field() ?><input type="hidden" name="dir" value="up"><button class="btn btn-secondary btn-sm" type="submit" title="رفع للأعلى" <?= $i === 0 ? 'disabled style="opacity:.35"' : '' ?>>▲</button></form>
            <form method="post" action="<?= Url::admin('gatherings/' . $r['id'] . '/move') ?>"><?= Csrf::field() ?><input type="hidden" name="dir" value="down"><button class="btn btn-secondary btn-sm" type="submit" title="إنزال للأسفل" <?= $i === $rowCount - 1 ? 'disabled style="opacity:.35"' : '' ?>>▼</button></form>
          </div>
        </td>
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
    <?php if (empty($rows)): ?><tr><td colspan="7" class="form-hint">لا توجد جمعات بعد.</td></tr><?php endif; ?>
    </tbody>
  </table>
</div>
