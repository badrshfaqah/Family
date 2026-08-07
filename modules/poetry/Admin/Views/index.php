<?php
/** @var array $rows */
use Core\Support\Csrf;
use Core\Support\Url;
use Core\View;
?>
<div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:14px">
  <p class="form-hint" style="margin:0">شعراء العائلة يظهرون للزوار كبطاقات باسمهم وصورتهم، وداخل كل شاعر قصائده.</p>
  <div style="display:flex;gap:8px">
    <a class="btn btn-secondary" href="<?= Url::admin('poems/create') ?>">+ إضافة قصيدة</a>
    <a class="btn btn-primary" href="<?= Url::admin('poetry/create') ?>">+ إضافة شاعر</a>
  </div>
</div>

<div class="table-wrap">
  <table>
    <thead><tr><th>الشاعر</th><th>نبذة</th><th>القصائد</th><th>الترتيب</th><th>الحالة</th><th></th></tr></thead>
    <tbody>
    <?php foreach ($rows as $r): ?>
      <tr>
        <td><b><?= View::e($r['name']) ?></b></td>
        <td style="max-width:280px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap"><?= View::e($r['bio'] ?? '—') ?></td>
        <td><?= (int) $r['poems_count'] ?></td>
        <td><?= (int) $r['sort_order'] ?></td>
        <td><span class="badge <?= $r['status'] === 'active' ? 'badge-green' : 'badge-gray' ?>"><?= $r['status'] === 'active' ? 'ظاهر' : 'مخفي' ?></span></td>
        <td style="display:flex;gap:6px">
          <a class="btn btn-secondary btn-sm" href="<?= Url::admin('poetry/' . $r['id'] . '/edit') ?>">إدارة</a>
          <form method="post" action="<?= Url::admin('poetry/' . $r['id'] . '/delete') ?>"><?= Csrf::field() ?><button class="btn btn-danger btn-sm" type="submit" data-confirm="حذف الشاعر وجميع قصائده نهائيًا؟">حذف</button></form>
        </td>
      </tr>
    <?php endforeach; ?>
    <?php if (empty($rows)): ?><tr><td colspan="6" style="text-align:center;color:#888">لا يوجد شعراء بعد — ابدأ بإضافة أول شاعر.</td></tr><?php endif; ?>
    </tbody>
  </table>
</div>
