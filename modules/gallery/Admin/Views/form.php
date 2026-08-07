<?php
/** @var array|null $item
 *  @var array $photos
 *  @var array $cities
 *  @var array $branches
 *  @var array $calendarEntries
 *  @var bool $branchesEnabled
 */
use Core\Database;
use Core\Media;
use Core\Support\Csrf;
use Core\Support\Url;
use Core\View;

$isEdit = $item !== null;
$action = $isEdit ? Url::admin('gallery/' . $item['id'] . '/update') : Url::admin('gallery/store');

function fam_gallery_cover_thumb($mediaId)
{
    if (!$mediaId) return null;
    $row = \Core\Database::fetchOne('SELECT stored_path FROM ' . \Core\Database::table('media') . ' WHERE id = ?', [$mediaId]);
    return $row ? Media::url($row['stored_path']) : null;
}
?>
<form method="post" action="<?= $action ?>" class="card-box" enctype="multipart/form-data">
  <?= Csrf::field() ?>
  <div class="form-row cols-2">
    <div class="form-group"><label>عنوان الألبوم</label><input class="form-control" name="title" value="<?= View::e($item['title'] ?? '') ?>" required></div>
    <div class="form-group"><label>الرابط المختصر</label><input class="form-control" dir="ltr" name="slug" value="<?= View::e($item['slug'] ?? '') ?>"></div>
  </div>

  <div class="form-group"><label>وصف الألبوم</label><textarea class="form-control" name="description" rows="2"><?= View::e($item['description'] ?? '') ?></textarea></div>

  <div class="form-group">
    <label>رابط فيديو (اختياري)</label>
    <input class="form-control" dir="ltr" name="video_url" value="<?= View::e($item['video_url'] ?? '') ?>" placeholder="https://youtube.com/watch?v=... أو رابط قوقل درايف">
    <div class="form-hint">يوتيوب أو قوقل درايف — يظهر الفيديو مشغلًا داخل صفحة الألبوم (مناسب لفيديو زواج أو مناسبة). يمكن جمع فيديو وصور في نفس الألبوم.</div>
  </div>

  <div class="form-row cols-3">
    <div class="form-group">
      <label>نوع الألبوم</label>
      <?php $albumType = $item['album_type'] ?? 'photo'; ?>
      <select class="form-control" name="album_type">
        <option value="photo" <?= $albumType === 'photo' ? 'selected' : '' ?>>صور</option>
        <option value="video" <?= $albumType === 'video' ? 'selected' : '' ?>>فيديو</option>
      </select>
    </div>
    <div class="form-group"><label>السنة</label><input class="form-control" type="number" name="year" min="1300" max="2200" value="<?= View::e((string) ($item['year'] ?? '')) ?>"></div>
    <div class="form-group"><label>ترتيب العرض</label><input class="form-control" type="number" name="sort_order" value="<?= View::e((string) ($item['sort_order'] ?? 0)) ?>"></div>
  </div>

  <div class="form-row cols-3">
    <div class="form-group">
      <label>المدينة</label>
      <select class="form-control" name="city_id">
        <option value="">بدون مدينة</option>
        <?php foreach ($cities as $c): ?>
          <option value="<?= $c['id'] ?>" <?= ($item['city_id'] ?? null) == $c['id'] ? 'selected' : '' ?>><?= View::e($c['name']) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <?php if ($branchesEnabled): ?>
    <div class="form-group">
      <label>الفرع</label>
      <select class="form-control" name="branch_id">
        <option value="">بدون فرع</option>
        <?php foreach ($branches as $b): ?>
          <option value="<?= $b['id'] ?>" <?= ($item['branch_id'] ?? null) == $b['id'] ? 'selected' : '' ?>><?= View::e($b['name']) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <?php endif; ?>
    <div class="form-group">
      <label>الحالة</label>
      <?php $status = $item['status'] ?? 'draft'; ?>
      <select class="form-control" name="status">
        <option value="draft" <?= $status === 'draft' ? 'selected' : '' ?>>مسودة</option>
        <option value="published" <?= $status === 'published' ? 'selected' : '' ?>>منشور</option>
      </select>
    </div>
  </div>

  <div class="form-row cols-3">
    <div class="form-group">
      <label>تغطية لمناسبة من الرزنامة (اختياري)</label>
      <select class="form-control" name="related_calendar_id">
        <option value="">— بدون ربط —</option>
        <?php foreach (($calendarEntries ?? []) as $ce): ?>
          <option value="<?= (int) $ce['id'] ?>" <?= (int) ($item['related_calendar_id'] ?? 0) === (int) $ce['id'] ? 'selected' : '' ?>>
            <?= View::e($ce['title']) ?> — <?= View::e(date('Y/m/d', strtotime($ce['entry_datetime']))) ?>
          </option>
        <?php endforeach; ?>
      </select>
      <div class="form-hint">عند الربط يظهر الألبوم (صوره وفيديوه) تلقائيًا في صفحة المناسبة، وتظهر شارة 📸 بجانبها في الرزنامة.</div>
    </div>
    <div class="form-group"><label>معرّف خبر مرتبط (اختياري)</label><input class="form-control" type="number" name="related_news_id" value="<?= View::e((string) ($item['related_news_id'] ?? '')) ?>"></div>
    <div class="form-group">
      <label>صورة الغلاف</label>
      <?php $cover = fam_gallery_cover_thumb($item['cover_media_id'] ?? null); if ($cover): ?><img src="<?= View::e($cover) ?>" style="height:60px;border-radius:8px;margin-bottom:8px;display:block"><?php endif; ?>
      <input class="form-control" type="file" name="cover" accept="image/*">
    </div>
  </div>

  <?php if (!$isEdit): ?>
  <div class="form-group"><label>صور الألبوم (يمكن اختيار عدة صور الآن، ويمكن إضافة المزيد لاحقًا)</label><input class="form-control" type="file" name="photos[]" accept="image/*" multiple></div>
  <?php endif; ?>

  <button class="btn btn-primary" type="submit"><?= $isEdit ? 'حفظ التعديلات' : 'إضافة الألبوم' ?></button>
</form>

<?php if ($isEdit): ?>
<div class="card-box" style="margin-top:20px">
  <h3 style="margin-top:0">صور الألبوم</h3>
  <form method="post" action="<?= Url::admin('gallery/' . $item['id'] . '/photos/store') ?>" enctype="multipart/form-data" style="display:flex;gap:10px;align-items:end;margin-bottom:18px;flex-wrap:wrap">
    <?= Csrf::field() ?>
    <div class="form-group" style="flex:1;min-width:220px"><label>إضافة صور جديدة</label><input class="form-control" type="file" name="photos[]" accept="image/*" multiple required></div>
    <button class="btn btn-secondary" type="submit">رفع</button>
  </form>

  <div class="grid grid-3">
    <?php foreach ($photos as $p): ?>
      <div class="card" style="padding:10px">
        <div class="card-img"><img src="<?= View::e(Media::thumbUrl($p['thumb_path'], $p['stored_path'])) ?>" alt=""></div>
        <form method="post" action="<?= Url::admin('gallery-photos/' . $p['id'] . '/update') ?>" style="margin-top:8px;display:flex;flex-direction:column;gap:6px">
          <?= Csrf::field() ?>
          <input class="form-control" name="caption" placeholder="وصف الصورة" value="<?= View::e($p['caption'] ?? '') ?>">
          <input class="form-control" type="number" name="sort_order" value="<?= View::e((string) $p['sort_order']) ?>" style="width:90px">
          <div style="display:flex;gap:6px">
            <button class="btn btn-secondary btn-sm" type="submit">حفظ</button>
          </div>
        </form>
        <form method="post" action="<?= Url::admin('gallery-photos/' . $p['id'] . '/delete') ?>" data-confirm="حذف هذه الصورة؟" style="margin-top:6px">
          <?= Csrf::field() ?>
          <button class="btn btn-danger btn-sm" type="submit">حذف</button>
        </form>
      </div>
    <?php endforeach; ?>
    <?php if (empty($photos)): ?><div class="form-hint">لا توجد صور في هذا الألبوم بعد.</div><?php endif; ?>
  </div>
</div>
<?php endif; ?>
