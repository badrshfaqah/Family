<?php
/** @var array|null $item */
use Core\Support\Csrf;
use Core\Support\Url;
use Core\View;

$isEdit = $item !== null;
$action = $isEdit ? Url::admin('newsletter/' . $item['id'] . '/update') : Url::admin('newsletter/store');
$status = $item['status'] ?? 'subscribed';
?>
<form method="post" action="<?= $action ?>" class="card-box" style="max-width:640px">
  <?= Csrf::field() ?>

  <div class="form-row cols-2">
    <div class="form-group">
      <label>الاسم (اختياري)</label>
      <input class="form-control" name="name" value="<?= View::e($item['name'] ?? '') ?>" maxlength="150">
    </div>
    <div class="form-group">
      <label>البريد الإلكتروني</label>
      <input class="form-control" dir="ltr" type="email" name="email" value="<?= View::e($item['email'] ?? '') ?>" required maxlength="191">
    </div>
  </div>

  <div class="form-row cols-2">
    <div class="form-group">
      <label>التصنيف (اختياري)</label>
      <input class="form-control" name="category" value="<?= View::e($item['category'] ?? '') ?>" placeholder="مثال: أخبار عامة / مناسبات فقط">
    </div>
    <div class="form-group">
      <label>الحالة</label>
      <select class="form-control" name="status">
        <option value="subscribed" <?= $status === 'subscribed' ? 'selected' : '' ?>>مشترك</option>
        <option value="unsubscribed" <?= $status === 'unsubscribed' ? 'selected' : '' ?>>ملغى</option>
      </select>
    </div>
  </div>

  <?php if ($isEdit): ?>
    <p class="form-hint">
      المصدر: <?= View::e(['website_form' => 'نموذج الموقع', 'admin_manual' => 'إضافة يدوية', 'import_csv' => 'استيراد CSV'][$item['source']] ?? $item['source']) ?>
      <?php if (!empty($item['subscribed_at'])): ?> — تاريخ الاشتراك: <?= View::e($item['subscribed_at']) ?><?php endif; ?>
    </p>
  <?php endif; ?>

  <button class="btn btn-primary" type="submit"><?= $isEdit ? 'حفظ التعديلات' : 'إضافة' ?></button>
</form>
