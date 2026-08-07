<?php
/** @var array|null $item
 *  @var array $poems
 */
use Core\Support\Csrf;
use Core\Support\Url;
use Core\View;

$isEdit = $item !== null;
$action = $isEdit ? Url::admin('poetry/' . $item['id'] . '/update') : Url::admin('poetry/store');
?>
<form method="post" action="<?= $action ?>" class="card-box" enctype="multipart/form-data">
  <?= Csrf::field() ?>

  <div class="form-row cols-2">
    <div class="form-group"><label>اسم الشاعر</label><input class="form-control" name="name" value="<?= View::e($item['name'] ?? '') ?>" required></div>
    <div class="form-group">
      <label>صورة الشاعر <?= $isEdit && $item['photo_media_id'] ? '(مرفوعة — اختر ملفًا لاستبدالها)' : '' ?></label>
      <input class="form-control" type="file" name="photo" accept="image/*">
    </div>
  </div>

  <div class="form-group"><label>نبذة مختصرة (اختياري)</label><textarea class="form-control" name="bio" rows="2" placeholder="مثال: شاعر العائلة الكبير، من مواليد..."><?= View::e($item['bio'] ?? '') ?></textarea></div>

  <div class="form-row cols-2">
    <div class="form-group"><label>ترتيب العرض</label><input class="form-control" type="number" name="sort_order" value="<?= View::e((string) ($item['sort_order'] ?? 0)) ?>"></div>
    <div class="form-group">
      <label>الحالة</label>
      <select class="form-control" name="status">
        <option value="active" <?= ($item['status'] ?? 'active') === 'active' ? 'selected' : '' ?>>ظاهر للزوار</option>
        <option value="hidden" <?= ($item['status'] ?? '') === 'hidden' ? 'selected' : '' ?>>مخفي</option>
      </select>
    </div>
  </div>

  <button class="btn btn-primary" type="submit"><?= $isEdit ? 'تحديث بيانات الشاعر' : 'حفظ الشاعر' ?></button>
</form>

<?php if ($isEdit): ?>
<div style="display:flex;justify-content:space-between;align-items:center;margin:22px 0 12px">
  <h3 style="margin:0">قصائد <?= View::e($item['name']) ?> (<?= count($poems) ?>)</h3>
  <a class="btn btn-primary" href="<?= Url::admin('poems/create?poet_id=' . $item['id']) ?>">+ إضافة قصيدة</a>
</div>

<div class="table-wrap">
  <table>
    <thead><tr><th>العنوان</th><th>المناسبة</th><th>الترتيب</th><th>الحالة</th><th></th></tr></thead>
    <tbody>
    <?php foreach ($poems as $p): ?>
      <tr>
        <td><b><?= View::e($p['title']) ?></b></td>
        <td><?= View::e($p['occasion'] ?? '—') ?></td>
        <td><?= (int) $p['sort_order'] ?></td>
        <td><span class="badge <?= $p['status'] === 'published' ? 'badge-green' : 'badge-gray' ?>"><?= $p['status'] === 'published' ? 'منشورة' : 'مسودة' ?></span></td>
        <td style="display:flex;gap:6px">
          <a class="btn btn-secondary btn-sm" href="<?= Url::admin('poems/' . $p['id'] . '/edit') ?>">تعديل</a>
          <form method="post" action="<?= Url::admin('poems/' . $p['id'] . '/delete') ?>"><?= Csrf::field() ?><button class="btn btn-danger btn-sm" type="submit" data-confirm="حذف هذه القصيدة؟">حذف</button></form>
        </td>
      </tr>
    <?php endforeach; ?>
    <?php if (empty($poems)): ?><tr><td colspan="5" style="text-align:center;color:#888">لا توجد قصائد بعد.</td></tr><?php endif; ?>
    </tbody>
  </table>
</div>
<?php endif; ?>
