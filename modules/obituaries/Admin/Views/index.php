<?php
/** @var array $rows */
use Core\Support\Csrf;
use Core\Support\Url;
use Core\View;
?>
<div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:14px">
  <p class="form-hint" style="margin:0">الإعلانات النشطة تظهر كبطاقة مميزة أعلى الصفحة الرئيسية فوق بطاقة التسجيل. أرشِف الإعلان بعد انتهاء العزاء.</p>
  <a class="btn btn-primary" href="<?= Url::admin('obituaries/create') ?>">+ إضافة إعلان وفاة</a>
</div>

<div class="table-wrap">
  <table>
    <thead><tr><th>المتوفى</th><th>المدينة</th><th>تاريخ الوفاة</th><th>مكان العزاء</th><th>الحالة</th><th></th></tr></thead>
    <tbody>
    <?php foreach ($rows as $r): ?>
      <tr>
        <td><?= View::e($r['name']) ?></td>
        <td><?= View::e($r['city_name'] ?? '—') ?></td>
        <td><?= $r['deceased_on'] ? View::e(date('Y/m/d', strtotime($r['deceased_on']))) : '—' ?></td>
        <td><?= View::e($r['condolence_venue'] ?? '—') ?></td>
        <td><span class="badge <?= $r['status'] === 'active' ? 'badge-green' : 'badge-gray' ?>"><?= $r['status'] === 'active' ? 'نشط' : 'مؤرشف' ?></span></td>
        <td style="display:flex;gap:6px">
          <a class="btn btn-secondary btn-sm" href="<?= Url::admin('obituaries/' . $r['id'] . '/edit') ?>">تعديل</a>
          <form method="post" action="<?= Url::admin('obituaries/' . $r['id'] . '/delete') ?>" data-confirm="حذف هذا الإعلان نهائيًا؟"><?= Csrf::field() ?><button class="btn btn-danger btn-sm" type="submit">حذف</button></form>
        </td>
      </tr>
    <?php endforeach; ?>
    <?php if (empty($rows)): ?><tr><td colspan="6" style="text-align:center;color:#888">لا توجد إعلانات وفيات.</td></tr><?php endif; ?>
    </tbody>
  </table>
</div>
