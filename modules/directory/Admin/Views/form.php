<?php
/** @var array|null $item
 *  @var array $cities
 *  @var array $branches
 *  @var bool $branchesEnabled
 */
use Core\Support\Csrf;
use Core\Support\Url;
use Core\View;

$isEdit = $item !== null;
$action = $isEdit ? Url::admin('directory/' . $item['id'] . '/update') : Url::admin('directory/store');
$sourceLabels = ['website_form' => 'نموذج الموقع', 'admin_manual' => 'إدخال يدوي', 'import_csv' => 'استيراد CSV'];
?>
<form method="post" action="<?= $action ?>" class="card-box" style="max-width:640px">
  <?= Csrf::field() ?>

  <div class="form-row cols-2">
    <div class="form-group"><label>الاسم</label><input class="form-control" name="name" value="<?= View::e($item['name'] ?? '') ?>" required></div>
    <div class="form-group"><label>رقم الجوال</label><input class="form-control" dir="ltr" name="phone" value="<?= View::e($item['phone'] ?? '') ?>" required></div>
  </div>

  <div class="form-row cols-2">
    <div class="form-group">
      <label>المدينة</label>
      <select class="form-control" name="city_id">
        <option value="">بدون</option>
        <?php foreach ($cities as $c): ?>
          <option value="<?= (int) $c['id'] ?>" <?= ($item['city_id'] ?? null) == $c['id'] ? 'selected' : '' ?>><?= View::e($c['name']) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <?php if ($branchesEnabled): ?>
    <div class="form-group">
      <label>الفرع</label>
      <select class="form-control" name="branch_id">
        <option value="">بدون</option>
        <?php foreach ($branches as $b): ?>
          <option value="<?= (int) $b['id'] ?>" <?= ($item['branch_id'] ?? null) == $b['id'] ? 'selected' : '' ?>><?= View::e($b['name']) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <?php endif; ?>
  </div>

  <div class="form-row cols-2">
    <div class="form-group"><label>البريد الإلكتروني</label><input class="form-control" dir="ltr" type="email" name="email" value="<?= View::e($item['email'] ?? '') ?>"></div>
    <div class="form-group">
      <label>الحالة</label>
      <select class="form-control" name="status">
        <option value="active" <?= ($item['status'] ?? 'active') === 'active' ? 'selected' : '' ?>>نشط (قابل للتواصل)</option>
        <option value="excluded" <?= ($item['status'] ?? '') === 'excluded' ? 'selected' : '' ?>>مستبعد من التواصل (بدون حذف)</option>
      </select>
    </div>
  </div>

  <?php if ($isEdit): ?>
    <p class="form-hint">
      المصدر: <?= View::e($sourceLabels[$item['source']] ?? $item['source']) ?> —
      تاريخ التسجيل: <?= View::e($item['registered_at']) ?>
    </p>
  <?php endif; ?>

  <button class="btn btn-primary" type="submit"><?= $isEdit ? 'حفظ التعديلات' : 'إضافة' ?></button>
</form>
