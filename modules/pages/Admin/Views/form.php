<?php
/** @var array|null $page */
use Core\Support\Csrf;
use Core\Support\Url;
use Core\View;

$isEdit = $page !== null;
$action = $isEdit ? Url::admin('pages/' . $page['id'] . '/update') : Url::admin('pages/store');
?>
<form method="post" action="<?= $action ?>" class="card-box">
  <?= Csrf::field() ?>
  <div class="form-row cols-2">
    <div class="form-group"><label>العنوان</label><input class="form-control" name="title" value="<?= View::e($page['title'] ?? '') ?>" required></div>
    <div class="form-group"><label>الرابط المختصر (Slug)</label><input class="form-control" dir="ltr" name="slug" value="<?= View::e($page['slug'] ?? '') ?>" placeholder="سيُنشأ تلقائيًا إن تُرك فارغًا"></div>
  </div>
  <div class="form-group">
    <label>المحتوى</label>
    <textarea class="form-control" name="content" rows="12"><?= View::e($page['content'] ?? '') ?></textarea>
    <div class="form-hint">يدعم وسوم HTML الأساسية (عناوين، فقرات، روابط، صور، قوائم).</div>
  </div>
  <div class="form-row cols-2">
    <div class="form-group">
      <label>الحالة</label>
      <select class="form-control" name="status">
        <option value="draft" <?= ($page['status'] ?? 'draft') === 'draft' ? 'selected' : '' ?>>مسودة</option>
        <option value="published" <?= ($page['status'] ?? '') === 'published' ? 'selected' : '' ?>>منشورة</option>
      </select>
    </div>
    <div class="form-group"><label>ترتيب العرض</label><input class="form-control" type="number" name="sort_order" value="<?= (int) ($page['sort_order'] ?? 0) ?>"></div>
  </div>
  <label class="switch-row"><span>إضافة الصفحة تلقائيًا لقائمة التذييل</span><input type="checkbox" name="show_in_menu" <?= !empty($page['show_in_menu']) ? 'checked' : '' ?>></label>

  <h3 style="font-size:.95rem;margin-top:20px">إعدادات SEO</h3>
  <div class="form-group"><label>عنوان SEO</label><input class="form-control" name="seo_title" value="<?= View::e($page['seo_title'] ?? '') ?>"></div>
  <div class="form-group"><label>وصف SEO</label><textarea class="form-control" name="seo_description" rows="2"><?= View::e($page['seo_description'] ?? '') ?></textarea></div>

  <button class="btn btn-primary" type="submit"><?= $isEdit ? 'حفظ التعديلات' : 'إضافة' ?></button>
</form>
