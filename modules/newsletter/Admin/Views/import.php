<?php
/** @var array|null $preview
 *  @var int|null $validCount
 *  @var int|null $totalCount
 */
use Core\Support\Csrf;
use Core\Support\Url;
use Core\View;

$hasPreview = isset($preview);
?>

<?php if (!$hasPreview): ?>

  <div class="card-box" style="max-width:640px">
    <h2 style="margin-top:0;font-size:1rem">استيراد مشتركين من ملف CSV</h2>
    <p class="form-hint">
      الصف الأول من الملف يجب أن يحتوي على أسماء الأعمدة: <code>name</code>، <code>email</code>، <code>category</code>
      (أو ما يعادلها بالعربية: الاسم، البريد الإلكتروني، التصنيف). عمود البريد الإلكتروني مطلوب.
    </p>
    <form method="post" action="<?= Url::admin('newsletter/import/preview') ?>" enctype="multipart/form-data">
      <?= Csrf::field() ?>
      <div class="form-group">
        <label>ملف CSV</label>
        <input class="form-control" type="file" name="csv_file" accept=".csv,text/csv" required>
      </div>
      <button class="btn btn-primary" type="submit">معاينة الملف</button>
    </form>
  </div>

<?php else: ?>

  <div class="card-box">
    <h2 style="margin-top:0;font-size:1rem">معاينة الاستيراد</h2>
    <p class="form-hint">
      إجمالي الصفوف: <?= (int) $totalCount ?> —
      صالحة للإضافة: <b><?= (int) $validCount ?></b> —
      الصفوف الأخرى (مكررة داخل الملف، موجودة مسبقًا، أو غير صالحة) لن تُستورد.
    </p>

    <div class="table-wrap" style="max-height:420px;overflow-y:auto">
      <table>
        <thead><tr><th>#</th><th>الاسم</th><th>البريد الإلكتروني</th><th>التصنيف</th><th>الحالة</th><th>ملاحظة</th></tr></thead>
        <tbody>
        <?php foreach ($preview as $p): ?>
          <?php
            $rowBadge = ['صالح' => 'badge-green', 'موجود' => 'badge-gray', 'مكرر' => 'badge-gray', 'خطأ' => 'badge-red'][$p['status']] ?? 'badge-gray';
          ?>
          <tr>
            <td><?= (int) $p['row'] ?></td>
            <td><?= View::e($p['name'] ?: '—') ?></td>
            <td dir="ltr"><?= View::e($p['email'] ?: '—') ?></td>
            <td><?= View::e($p['category'] ?: '—') ?></td>
            <td><span class="badge <?= $rowBadge ?>"><?= View::e($p['status']) ?></span></td>
            <td class="form-hint"><?= View::e($p['reason']) ?></td>
          </tr>
        <?php endforeach; ?>
        <?php if (empty($preview)): ?><tr><td colspan="6" class="form-hint">لا توجد صفوف بيانات في الملف.</td></tr><?php endif; ?>
        </tbody>
      </table>
    </div>

    <div style="display:flex;gap:8px;margin-top:16px">
      <form method="post" action="<?= Url::admin('newsletter/import/commit') ?>">
        <?= Csrf::field() ?>
        <button class="btn btn-primary" type="submit" <?= $validCount < 1 ? 'disabled' : '' ?>>تأكيد استيراد <?= (int) $validCount ?> مشترك</button>
      </form>
      <a class="btn btn-secondary" href="<?= Url::admin('newsletter/import') ?>">إلغاء / رفع ملف آخر</a>
    </div>
  </div>

<?php endif; ?>

<!-- خارج نطاق النسخة الحالية: قوالب نشرات بريدية مستقبلية وربط الاشتراك بخبر أو مناسبة (قريبًا). -->
