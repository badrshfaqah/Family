<?php
/** @var array $rows
 *  @var array $categories
 *  @var array $summary
 *  @var array $filters
 *  @var int $page
 *  @var int $perPage
 *  @var int $total
 */
use Core\Support\Csrf;
use Core\Support\Url;
use Core\View;

$statusLabels = ['subscribed' => 'مشترك', 'unsubscribed' => 'ملغى'];
$statusBadge = ['subscribed' => 'badge-green', 'unsubscribed' => 'badge-gray'];
$sourceLabels = ['website_form' => 'نموذج الموقع', 'admin_manual' => 'إضافة يدوية', 'import_csv' => 'استيراد CSV'];
$totalPages = max(1, (int) ceil($total / $perPage));

$exportQuery = http_build_query(array_filter($filters));
?>
<div class="grid-cards" style="margin-bottom:18px">
  <div class="stat-box"><b><?= (int) $summary['total'] ?></b><span>إجمالي المشتركين</span></div>
  <div class="stat-box"><b><?= (int) $summary['subscribed'] ?></b><span>مشترك حاليًا</span></div>
  <div class="stat-box"><b><?= (int) $summary['unsubscribed'] ?></b><span>ألغى الاشتراك</span></div>
  <div class="stat-box"><b><?= (int) $summary['recent'] ?></b><span>اشتراكات آخر 7 أيام</span></div>
</div>

<div style="display:flex;justify-content:flex-end;gap:8px;margin-bottom:14px;flex-wrap:wrap">
  <a class="btn btn-secondary" href="<?= Url::admin('newsletter/import') ?>">استيراد CSV</a>
  <a class="btn btn-secondary" href="<?= Url::admin('newsletter/export') . ($exportQuery ? '?' . $exportQuery : '') ?>">تصدير CSV</a>
  <a class="btn btn-primary" href="<?= Url::admin('newsletter/create') ?>">+ إضافة مشترك</a>
</div>

<form method="get" action="<?= Url::admin('newsletter') ?>" class="card-box" style="margin-bottom:14px">
  <div class="form-row cols-3">
    <div class="form-group">
      <label>بحث (الاسم أو البريد)</label>
      <input class="form-control" type="text" name="q" value="<?= View::e($filters['q']) ?>">
    </div>
    <div class="form-group">
      <label>الحالة</label>
      <select class="form-control" name="status">
        <option value="">الكل</option>
        <option value="subscribed" <?= $filters['status'] === 'subscribed' ? 'selected' : '' ?>>مشترك</option>
        <option value="unsubscribed" <?= $filters['status'] === 'unsubscribed' ? 'selected' : '' ?>>ملغى</option>
      </select>
    </div>
    <div class="form-group">
      <label>التصنيف</label>
      <select class="form-control" name="category">
        <option value="">الكل</option>
        <?php foreach ($categories as $c): ?>
          <option value="<?= View::e($c) ?>" <?= $filters['category'] === $c ? 'selected' : '' ?>><?= View::e($c) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
  </div>
  <button class="btn btn-primary btn-sm" type="submit">تصفية</button>
  <a class="btn btn-secondary btn-sm" href="<?= Url::admin('newsletter') ?>">إعادة تعيين</a>
</form>

<!-- نموذج التصنيف الجماعي: مستقل عن الجدول، وتُربط صناديق الاختيار به عبر السمة form= لتفادي تداخل نماذج HTML -->
<form method="post" action="<?= Url::admin('newsletter/bulk-category') ?>" id="bulk-form">
  <?= Csrf::field() ?>
  <input type="hidden" name="q" value="<?= View::e($filters['q']) ?>">
  <input type="hidden" name="status" value="<?= View::e($filters['status']) ?>">
  <input type="hidden" name="category" value="<?= View::e($filters['category']) ?>">

  <div style="display:flex;gap:8px;align-items:center;margin-bottom:10px;flex-wrap:wrap">
    <span class="form-hint">تصنيف جماعي للمحدد:</span>
    <input class="form-control" style="max-width:220px" type="text" name="bulk_category" placeholder="مثال: أخبار عامة" form="bulk-form">
    <button class="btn btn-secondary btn-sm" type="submit" form="bulk-form">تطبيق على المحدد</button>
  </div>
</form>

<div class="table-wrap">
  <table>
    <thead>
      <tr>
        <th><input type="checkbox" data-select-all></th>
        <th>الاسم</th><th>البريد الإلكتروني</th><th>الحالة</th><th>التصنيف</th><th>المصدر</th><th>تاريخ الاشتراك</th><th></th>
      </tr>
    </thead>
    <tbody>
    <?php foreach ($rows as $r): ?>
      <tr>
        <td><input type="checkbox" name="ids[]" value="<?= (int) $r['id'] ?>" form="bulk-form"></td>
        <td><?= View::e($r['name'] ?? '—') ?></td>
        <td dir="ltr"><?= View::e($r['email']) ?></td>
        <td><span class="badge <?= $statusBadge[$r['status']] ?>"><?= $statusLabels[$r['status']] ?></span></td>
        <td><?= View::e($r['category'] ?? '—') ?></td>
        <td><?= View::e($sourceLabels[$r['source']] ?? $r['source']) ?></td>
        <td><?= View::e($r['subscribed_at'] ?? '—') ?></td>
        <td style="display:flex;gap:6px">
          <a class="btn btn-secondary btn-sm" href="<?= Url::admin('newsletter/' . $r['id'] . '/edit') ?>">تعديل</a>
          <form method="post" action="<?= Url::admin('newsletter/' . $r['id'] . '/delete') ?>" data-confirm="حذف هذا المشترك؟"><?= Csrf::field() ?><button class="btn btn-danger btn-sm" type="submit">حذف</button></form>
        </td>
      </tr>
    <?php endforeach; ?>
    <?php if (empty($rows)): ?><tr><td colspan="8" class="form-hint">لا يوجد مشتركون مطابقون.</td></tr><?php endif; ?>
    </tbody>
  </table>
</div>

<?php if ($totalPages > 1): ?>
<div style="display:flex;gap:6px;margin-top:16px;flex-wrap:wrap">
  <?php for ($p = 1; $p <= $totalPages; $p++): ?>
    <?php $q = array_merge($filters, ['page' => $p]); ?>
    <a class="btn btn-sm <?= $p === $page ? 'btn-primary' : 'btn-secondary' ?>" href="<?= Url::admin('newsletter') . '?' . http_build_query(array_filter($q)) ?>"><?= $p ?></a>
  <?php endfor; ?>
</div>
<?php endif; ?>

<script nonce="<?= \Core\Support\Security::cspNonce() ?>">
document.querySelector('[data-select-all]')?.addEventListener('change', function () {
  document.querySelectorAll('input[name="ids[]"]').forEach(function (cb) { cb.checked = this.checked; }.bind(this));
});
</script>
