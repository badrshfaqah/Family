<?php
/** @var array $ordered  قائمة العقد مرتبة هرميًا (DFS) مع مفتاح depth لكل عقدة
 *  @var array $branches
 */
use Core\Support\Csrf;
use Core\Support\Url;
use Core\View;

$nameById = [];
foreach ($ordered as $n) {
    $nameById[(int) $n['id']] = $n['name'];
}
$branchById = [];
foreach ($branches as $b) {
    $branchById[(int) $b['id']] = $b['name'];
}
?>
<div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:14px">
  <p class="form-hint" style="margin:0">لا يوجد حد أقصى لعدد المستويات. رتّب العقد عبر "الترتيب" واختر "الأب" لبناء التسلسل.</p>
  <a class="btn btn-primary" href="<?= Url::admin('tree-nodes/create') ?>">+ إضافة عقدة</a>
</div>

<div class="table-wrap">
  <table>
    <thead><tr><th>الاسم</th><th>الأب</th><th>الترتيب</th><th>الفرع المرتبط</th><th>الظهور</th><th></th></tr></thead>
    <tbody>
    <?php foreach ($ordered as $n): ?>
      <tr>
        <td style="padding-inline-start: <?= 14 + ((int) $n['depth'] * 22) ?>px">
          <?= $n['depth'] > 0 ? '↳ ' : '' ?><?= View::e($n['name']) ?>
        </td>
        <td><?= $n['parent_id'] ? View::e($nameById[(int) $n['parent_id']] ?? '—') : '—' ?></td>
        <td><?= (int) $n['sort_order'] ?></td>
        <td><?= $n['branch_id'] ? View::e($branchById[(int) $n['branch_id']] ?? '—') : '—' ?></td>
        <td><span class="badge <?= $n['is_visible'] ? 'badge-green' : 'badge-gray' ?>"><?= $n['is_visible'] ? 'ظاهر' : 'مخفي' ?></span></td>
        <td style="display:flex;gap:6px">
          <a class="btn btn-secondary btn-sm" href="<?= Url::admin('tree-nodes/' . $n['id'] . '/edit') ?>">تعديل</a>
          <form method="post" action="<?= Url::admin('tree-nodes/' . $n['id'] . '/delete') ?>" data-confirm="حذف هذه العقدة؟ سيصبح أبناؤها بلا أب مباشر."><?= Csrf::field() ?><button class="btn btn-danger btn-sm" type="submit">حذف</button></form>
        </td>
      </tr>
    <?php endforeach; ?>
    <?php if (empty($ordered)): ?><tr><td colspan="6" class="form-hint">لا توجد عقد بعد. ابدأ بإضافة الجد الأول في التسلسل.</td></tr><?php endif; ?>
    </tbody>
  </table>
</div>
