<?php
/** @var array $rows
 *  @var int $total
 *  @var int $page
 *  @var int $perPage
 *  @var array $summary
 *  @var array $cities
 *  @var array $branches
 *  @var bool $branchesEnabled
 *  @var array $filters
 */
use Core\Auth;
use Core\Support\Csrf;
use Core\Support\Url;
use Core\View;

$statusLabels = ['active' => 'نشط', 'excluded' => 'مستبعد من التواصل'];
$statusBadge = ['active' => 'badge-green', 'excluded' => 'badge-gray'];
$sourceLabels = ['website_form' => 'نموذج الموقع', 'admin_manual' => 'إدخال يدوي', 'import_csv' => 'استيراد CSV'];
$canManage = Auth::can('directory.manage');
$canExport = Auth::can('directory.export');

$totalPages = max(1, (int) ceil($total / $perPage));

function fam_dir_qs(array $filters, int $page): string
{
    return http_build_query(array_merge($filters, ['page' => $page]));
}
?>
<div class="grid-cards">
  <div class="stat-box"><b><?= (int) $summary['total'] ?></b><span>إجمالي السجلات</span></div>
  <div class="stat-box"><b><?= (int) $summary['active'] ?></b><span>نشط (قابل للتواصل)</span></div>
  <div class="stat-box"><b><?= (int) $summary['excluded'] ?></b><span>مستبعد من التواصل</span></div>
</div>

<div style="display:flex;justify-content:flex-end;gap:8px;margin-bottom:14px;flex-wrap:wrap">
  <a class="btn btn-secondary" href="<?= Url::admin('directory/duplicates') ?>">التكرارات</a>
  <a class="btn btn-secondary" href="<?= Url::admin('directory/removal-requests') ?>">طلبات إلغاء الاشتراك</a>
  <?php if ($canManage): ?>
    <a class="btn btn-secondary" href="<?= Url::admin('directory/import') ?>">استيراد Excel (CSV)</a>
  <?php endif; ?>
  <?php if ($canExport): ?>
    <a class="btn btn-secondary" href="<?= Url::admin('directory/export.csv?' . fam_dir_qs($filters, 1)) ?>">تصدير Excel (CSV)</a>
  <?php endif; ?>
  <?php if ($canManage): ?>
    <a class="btn btn-primary" href="<?= Url::admin('directory/create') ?>">+ إضافة جهة اتصال</a>
  <?php endif; ?>
</div>

<form method="get" action="<?= Url::admin('directory') ?>" class="card-box" style="display:flex;gap:10px;flex-wrap:wrap;align-items:end">
  <div class="form-group" style="margin-bottom:0;flex:2;min-width:180px">
    <label>بحث (الاسم أو الجوال)</label>
    <input class="form-control" type="text" name="q" value="<?= View::e($filters['q']) ?>">
  </div>
  <div class="form-group" style="margin-bottom:0;min-width:150px">
    <label>المدينة</label>
    <select class="form-control" name="city_id">
      <option value="">الكل</option>
      <?php foreach ($cities as $c): ?>
        <option value="<?= (int) $c['id'] ?>" <?= (string) $filters['city_id'] === (string) $c['id'] ? 'selected' : '' ?>><?= View::e($c['name']) ?></option>
      <?php endforeach; ?>
    </select>
  </div>
  <?php if ($branchesEnabled): ?>
  <div class="form-group" style="margin-bottom:0;min-width:150px">
    <label>الفرع</label>
    <select class="form-control" name="branch_id">
      <option value="">الكل</option>
      <?php foreach ($branches as $b): ?>
        <option value="<?= (int) $b['id'] ?>" <?= (string) $filters['branch_id'] === (string) $b['id'] ? 'selected' : '' ?>><?= View::e($b['name']) ?></option>
      <?php endforeach; ?>
    </select>
  </div>
  <?php endif; ?>
  <div class="form-group" style="margin-bottom:0;min-width:150px">
    <label>الحالة</label>
    <select class="form-control" name="status">
      <option value="">الكل</option>
      <option value="active" <?= $filters['status'] === 'active' ? 'selected' : '' ?>>نشط</option>
      <option value="excluded" <?= $filters['status'] === 'excluded' ? 'selected' : '' ?>>مستبعد من التواصل</option>
    </select>
  </div>
  <div class="form-group" style="margin-bottom:0"><button class="btn btn-primary" type="submit">تصفية</button></div>
</form>

<div class="table-wrap">
  <table>
    <thead>
      <tr>
        <th>الاسم</th><th>الجوال</th><th>المدينة</th><?php if ($branchesEnabled): ?><th>الفرع</th><?php endif; ?>
        <th>الحالة</th><th>المصدر</th><th>تاريخ التسجيل</th><th></th>
      </tr>
    </thead>
    <tbody>
    <?php foreach ($rows as $r): ?>
      <tr>
        <td><?= View::e($r['name']) ?></td>
        <td dir="ltr"><?= View::e($r['phone']) ?></td>
        <td><?= View::e($r['city_name'] ?? '—') ?></td>
        <?php if ($branchesEnabled): ?><td><?= View::e($r['branch_name'] ?? '—') ?></td><?php endif; ?>
        <td><span class="badge <?= $statusBadge[$r['status']] ?>"><?= $statusLabels[$r['status']] ?></span></td>
        <td><?= View::e($sourceLabels[$r['source']] ?? $r['source']) ?></td>
        <td><?= View::e($r['registered_at']) ?></td>
        <td style="display:flex;gap:6px;flex-wrap:wrap">
          <?php if ($canManage): ?>
            <a class="btn btn-secondary btn-sm" href="<?= Url::admin('directory/' . $r['id'] . '/edit') ?>">تعديل</a>
            <form method="post" action="<?= Url::admin('directory/' . $r['id'] . '/toggle-status') ?>"><?= Csrf::field() ?>
              <button class="btn btn-secondary btn-sm" type="submit"><?= $r['status'] === 'active' ? 'إيقاف التواصل' : 'إعادة التفعيل' ?></button>
            </form>
            <form method="post" action="<?= Url::admin('directory/' . $r['id'] . '/delete') ?>" data-confirm="حذف جهة الاتصال هذه؟"><?= Csrf::field() ?>
              <button class="btn btn-danger btn-sm" type="submit">حذف</button>
            </form>
          <?php endif; ?>
        </td>
      </tr>
    <?php endforeach; ?>
    <?php if (empty($rows)): ?><tr><td colspan="8" class="form-hint">لا توجد سجلات مطابقة.</td></tr><?php endif; ?>
    </tbody>
  </table>
</div>

<?php if ($totalPages > 1): ?>
<div style="display:flex;gap:6px;margin-top:16px;flex-wrap:wrap">
  <?php for ($p = 1; $p <= $totalPages; $p++): ?>
    <a class="btn btn-sm <?= $p === $page ? 'btn-primary' : 'btn-secondary' ?>" href="<?= Url::admin('directory?' . fam_dir_qs($filters, $p)) ?>"><?= $p ?></a>
  <?php endfor; ?>
</div>
<?php endif; ?>

<div class="card-box" style="margin-top:18px">
  <h2 style="margin-top:0;font-size:1rem">آخر 10 تسجيلات</h2>
  <?php if (empty($summary['recent'])): ?>
    <p class="form-hint">لا توجد تسجيلات بعد.</p>
  <?php else: ?>
    <div class="table-wrap">
      <table>
        <thead><tr><th>الاسم</th><th>الجوال</th><th>المصدر</th><th>التاريخ</th></tr></thead>
        <tbody>
        <?php foreach ($summary['recent'] as $rec): ?>
          <tr>
            <td><?= View::e($rec['name']) ?></td>
            <td dir="ltr"><?= View::e($rec['phone']) ?></td>
            <td><?= View::e($sourceLabels[$rec['source']] ?? $rec['source']) ?></td>
            <td><?= View::e($rec['registered_at']) ?></td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  <?php endif; ?>
</div>
