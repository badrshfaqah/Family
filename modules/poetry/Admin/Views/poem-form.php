<?php
/** @var array|null $item
 *  @var array $poets
 *  @var int $preselectedPoet
 */
use Core\Support\Csrf;
use Core\Support\Url;
use Core\View;

$isEdit = $item !== null;
$action = $isEdit ? Url::admin('poems/' . $item['id'] . '/update') : Url::admin('poems/store');
?>
<form method="post" action="<?= $action ?>" class="card-box">
  <?= Csrf::field() ?>

  <div class="form-row cols-2">
    <div class="form-group">
      <label>الشاعر</label>
      <select class="form-control" name="poet_id" required>
        <option value="">— اختر الشاعر —</option>
        <?php foreach ($poets as $p): ?>
          <option value="<?= (int) $p['id'] ?>" <?= (int) $p['id'] === $preselectedPoet ? 'selected' : '' ?>><?= View::e($p['name']) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="form-group"><label>عنوان القصيدة</label><input class="form-control" name="title" value="<?= View::e($item['title'] ?? '') ?>" required></div>
  </div>

  <div class="form-group"><label>مناسبة القصيدة (اختياري)</label><input class="form-control" name="occasion" value="<?= View::e($item['occasion'] ?? '') ?>" placeholder="مثال: قيلت في الاجتماع السنوي 1445هـ"></div>

  <div class="form-group">
    <label>نص القصيدة</label>
    <textarea class="form-control" name="content" rows="14" style="line-height:2.2;text-align:center" placeholder="اكتب كل شطر أو بيت في سطر مستقل — تُعرض للزوار بنفس تنسيق الأسطر" required><?= View::e($item['content'] ?? '') ?></textarea>
    <div class="form-hint">كل سطر يظهر كما هو للزائر — اترك سطرًا فارغًا للفصل بين المقاطع.</div>
  </div>

  <div class="form-row cols-2">
    <div class="form-group"><label>ترتيب العرض</label><input class="form-control" type="number" name="sort_order" value="<?= View::e((string) ($item['sort_order'] ?? 0)) ?>"></div>
    <div class="form-group">
      <label>الحالة</label>
      <select class="form-control" name="status">
        <option value="published" <?= ($item['status'] ?? 'published') === 'published' ? 'selected' : '' ?>>منشورة</option>
        <option value="draft" <?= ($item['status'] ?? '') === 'draft' ? 'selected' : '' ?>>مسودة</option>
      </select>
    </div>
  </div>

  <button class="btn btn-primary" type="submit"><?= $isEdit ? 'تحديث القصيدة' : 'حفظ القصيدة' ?></button>
</form>
