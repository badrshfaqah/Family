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
$action = $isEdit ? Url::admin('news/' . $item['id'] . '/update') : Url::admin('news/store');
?>
<form method="post" action="<?= $action ?>" class="card-box" enctype="multipart/form-data">
  <?= Csrf::field() ?>
  <div class="form-row cols-2">
    <div class="form-group"><label>العنوان</label><input class="form-control" name="title" value="<?= View::e($item['title'] ?? '') ?>" required></div>
    <div class="form-group"><label>الرابط المختصر</label><input class="form-control" dir="ltr" name="slug" value="<?= View::e($item['slug'] ?? '') ?>"></div>
  </div>
  <div class="form-group"><label>الملخص</label><textarea class="form-control" name="excerpt" rows="2"><?= View::e($item['excerpt'] ?? '') ?></textarea></div>
  <div class="form-group"><label>المحتوى</label><textarea class="form-control" name="content" rows="10"><?= View::e($item['content'] ?? '') ?></textarea></div>

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
    <div class="form-group"><label>الوسوم (افصل بفاصلة)</label><input class="form-control" name="tags" value="<?= View::e($item['tags'] ?? '') ?>"></div>
    <div class="form-group"><label>اسم الكاتب / الجهة الناشرة</label><input class="form-control" name="author_name" value="<?= View::e($item['author_name'] ?? '') ?>"></div>
  </div>

  <div class="form-row cols-2">
    <div class="form-group">
      <label>الصورة الرئيسية</label>
      <?php if (!empty($item['cover_media_id'])):
        $cover = Database::fetchOne('SELECT stored_path FROM ' . Database::table('media') . ' WHERE id = ?', [$item['cover_media_id']]);
        if ($cover): ?><img src="<?= View::e(Media::url($cover['stored_path'])) ?>" style="height:70px;border-radius:8px;margin-bottom:8px"><?php endif;
      endif; ?>
      <input class="form-control" type="file" name="cover" accept="image/*">
    </div>
    <div class="form-group"><label>رابط فيديو خارجي</label><input class="form-control" dir="ltr" name="video_url" value="<?= View::e($item['video_url'] ?? '') ?>" placeholder="رابط يوتيوب مثلًا"></div>
  </div>

  <div class="form-group"><label>روابط خارجية</label><textarea class="form-control" name="external_links" rows="2"><?= View::e($item['external_links'] ?? '') ?></textarea></div>

  <div class="form-row cols-3">
    <div class="form-group">
      <label>الحالة</label>
      <?php $status = $item['status'] ?? 'draft'; ?>
      <select class="form-control" name="status" id="status-select">
        <option value="draft" <?= $status === 'draft' ? 'selected' : '' ?>>مسودة</option>
        <option value="published" <?= $status === 'published' ? 'selected' : '' ?>>منشور</option>
        <option value="scheduled" <?= $status === 'scheduled' ? 'selected' : '' ?>>مجدول</option>
        <option value="archived" <?= $status === 'archived' ? 'selected' : '' ?>>مؤرشف</option>
      </select>
    </div>
    <div class="form-group"><label>تاريخ ووقت الجدولة (عند اختيار "مجدول")</label><input class="form-control" type="datetime-local" name="scheduled_at"></div>
    <div class="form-group" style="display:flex;gap:16px;align-items:end">
      <label style="display:flex;gap:6px;align-items:center"><input type="checkbox" name="is_pinned" <?= !empty($item['is_pinned']) ? 'checked' : '' ?>> مثبت</label>
      <label style="display:flex;gap:6px;align-items:center"><input type="checkbox" name="is_featured" <?= !empty($item['is_featured']) ? 'checked' : '' ?>> مميز</label>
    </div>
  </div>

  <h3 style="font-size:.95rem;margin-top:20px">إعدادات SEO</h3>
  <div class="form-group"><label>عنوان SEO</label><input class="form-control" name="seo_title" value="<?= View::e($item['seo_title'] ?? '') ?>"></div>
  <div class="form-group"><label>وصف SEO</label><textarea class="form-control" name="seo_description" rows="2"><?= View::e($item['seo_description'] ?? '') ?></textarea></div>

  <button class="btn btn-primary" type="submit"><?= $isEdit ? 'حفظ التعديلات' : 'إضافة' ?></button>
</form>
