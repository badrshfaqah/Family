<?php
/** @var array|null $item
 *  @var array $categories
 */
use Core\Database;
use Core\Media;
use Core\Support\Csrf;
use Core\Support\Url;
use Core\View;

$isEdit = $item !== null;
$action = $isEdit ? Url::admin('archive/' . $item['id'] . '/update') : Url::admin('archive/store');

function fam_archive_media($mediaId)
{
    if (!$mediaId) return null;
    return \Core\Database::fetchOne('SELECT * FROM ' . \Core\Database::table('media') . ' WHERE id = ?', [$mediaId]);
}
$coverMedia = fam_archive_media($item['cover_media_id'] ?? null);
$fileMedia = fam_archive_media($item['file_media_id'] ?? null);
?>
<form method="post" action="<?= $action ?>" class="card-box" enctype="multipart/form-data">
  <?= Csrf::field() ?>
  <div class="form-row cols-2">
    <div class="form-group"><label>العنوان</label><input class="form-control" name="title" value="<?= View::e($item['title'] ?? '') ?>" required></div>
    <div class="form-group"><label>الرابط المختصر</label><input class="form-control" dir="ltr" name="slug" value="<?= View::e($item['slug'] ?? '') ?>"></div>
  </div>

  <div class="form-group"><label>الوصف</label><textarea class="form-control" name="description" rows="4"><?= View::e($item['description'] ?? '') ?></textarea></div>

  <div class="form-row cols-3">
    <div class="form-group">
      <label>التصنيف</label>
      <select class="form-control" name="category_id">
        <option value="">بدون تصنيف</option>
        <?php foreach ($categories as $c): ?>
          <option value="<?= $c['id'] ?>" <?= ($item['category_id'] ?? null) == $c['id'] ? 'selected' : '' ?>><?= View::e($c['name']) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="form-group"><label>السنة أو الفترة الزمنية</label><input class="form-control" name="period_label" placeholder="مثال: 1950-1960 أو 1400هـ" value="<?= View::e($item['period_label'] ?? '') ?>"></div>
    <div class="form-group"><label>المصدر (اختياري)</label><input class="form-control" name="source" value="<?= View::e($item['source'] ?? '') ?>"></div>
  </div>

  <div class="form-group"><label>الوسوم (افصل بفاصلة)</label><input class="form-control" name="tags" value="<?= View::e($item['tags'] ?? '') ?>"></div>
  <div class="form-group"><label>ملاحظات</label><textarea class="form-control" name="notes" rows="2"><?= View::e($item['notes'] ?? '') ?></textarea></div>

  <div class="form-row cols-2">
    <div class="form-group">
      <label>صورة مصغّرة / غلاف</label>
      <?php if ($coverMedia): ?><img src="<?= View::e(Media::url($coverMedia['stored_path'])) ?>" style="height:70px;border-radius:8px;margin-bottom:8px;display:block"><?php endif; ?>
      <input class="form-control" type="file" name="cover" accept="image/*">
    </div>
    <div class="form-group">
      <label>الملف (وثيقة / صورة / صوت / فيديو / PDF)</label>
      <?php if ($fileMedia): ?><div class="form-hint">الملف الحالي: <a href="<?= View::e(Media::url($fileMedia['stored_path'])) ?>" target="_blank" rel="noopener"><?= View::e($fileMedia['file_name']) ?></a></div><?php endif; ?>
      <input class="form-control" type="file" name="file">
    </div>
  </div>

  <div class="form-group">
    <label>حالة النشر</label>
    <?php $status = $item['status'] ?? 'draft'; ?>
    <select class="form-control" name="status" style="max-width:220px">
      <option value="draft" <?= $status === 'draft' ? 'selected' : '' ?>>مسودة</option>
      <option value="published" <?= $status === 'published' ? 'selected' : '' ?>>منشور</option>
    </select>
  </div>

  <h3 style="font-size:.95rem;margin-top:20px">إعدادات SEO</h3>
  <div class="form-group"><label>عنوان SEO</label><input class="form-control" name="seo_title" value="<?= View::e($item['seo_title'] ?? '') ?>"></div>
  <div class="form-group"><label>وصف SEO</label><textarea class="form-control" name="seo_description" rows="2"><?= View::e($item['seo_description'] ?? '') ?></textarea></div>

  <button class="btn btn-primary" type="submit"><?= $isEdit ? 'حفظ التعديلات' : 'إضافة' ?></button>
</form>
