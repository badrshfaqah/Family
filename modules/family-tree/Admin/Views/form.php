<?php
/** @var array|null $item
 *  @var array $parentOptions  عقد مرتبة هرميًا (مع depth) متاحة للاختيار كأب
 *  @var array $branches
 */
use Core\Support\Csrf;
use Core\Support\Url;
use Core\View;

$isEdit = $item !== null;
$action = $isEdit ? Url::admin('tree-nodes/' . $item['id'] . '/update') : Url::admin('tree-nodes/store');
?>
<form method="post" action="<?= $action ?>" class="card-box">
  <?= Csrf::field() ?>
  <div class="form-row cols-2">
    <div class="form-group"><label>الاسم</label><input class="form-control" name="name" value="<?= View::e($item['name'] ?? '') ?>" required placeholder="مثال: وائل بن ربيعة"></div>
    <div class="form-group">
      <label>الأب (اختياري)</label>
      <select class="form-control" name="parent_id">
        <option value="">— بلا أب (جذر التسلسل) —</option>
        <?php foreach ($parentOptions as $p): ?>
          <option value="<?= (int) $p['id'] ?>" <?= (int) ($item['parent_id'] ?? 0) === (int) $p['id'] ? 'selected' : '' ?>>
            <?= str_repeat('— ', (int) $p['depth']) ?><?= View::e($p['name']) ?>
          </option>
        <?php endforeach; ?>
      </select>
    </div>
  </div>

  <div class="form-row cols-3">
    <div class="form-group"><label>الترتيب</label><input class="form-control" type="number" name="sort_order" value="<?= (int) ($item['sort_order'] ?? 0) ?>"></div>
    <div class="form-group">
      <label>ربط بفرع (اختياري)</label>
      <select class="form-control" name="branch_id">
        <option value="">— بدون —</option>
        <?php foreach ($branches as $b): ?>
          <option value="<?= (int) $b['id'] ?>" <?= (int) ($item['branch_id'] ?? 0) === (int) $b['id'] ? 'selected' : '' ?>><?= View::e($b['name']) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="form-group" style="display:flex;align-items:end">
      <label style="display:flex;gap:6px;align-items:center"><input type="checkbox" name="is_visible" <?= ($item === null || !empty($item['is_visible'])) ? 'checked' : '' ?>> ظاهر في الصفحة العامة</label>
    </div>
  </div>

  <div class="form-group"><label>نبذة / ملاحظة اختيارية</label><textarea class="form-control" name="note" rows="3"><?= View::e($item['note'] ?? '') ?></textarea></div>

  <button class="btn btn-primary" type="submit"><?= $isEdit ? 'حفظ التعديلات' : 'إضافة' ?></button>
</form>
