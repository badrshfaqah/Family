<?php
/** @var string $slug
 *  @var array $items
 */
use Core\Support\Csrf;
use Core\Support\Url;
use Core\View;

$menuLabels = ['main' => 'القائمة الرئيسية', 'mobile' => 'قائمة الجوال', 'footer' => 'قائمة التذييل'];
?>
<div class="tabs">
  <?php foreach ($menuLabels as $key => $label): ?>
    <a class="<?= $slug === $key ? 'active' : '' ?>" href="<?= Url::admin('menus/' . $key) ?>"><?= View::e($label) ?></a>
  <?php endforeach; ?>
</div>

<div class="card-box">
  <h3 style="margin-top:0;font-size:.95rem">إضافة عنصر جديد</h3>
  <form method="post" action="<?= Url::admin('menus/' . $slug . '/store') ?>" class="form-row cols-3">
    <?= Csrf::field() ?>
    <div class="form-group"><label>العنوان</label><input class="form-control" name="label" required></div>
    <div class="form-group"><label>الرابط</label><input class="form-control" name="url" placeholder="/news أو رابط خارجي"></div>
    <div class="form-group" style="display:flex;align-items:end;gap:10px">
      <button class="btn btn-primary" type="submit">إضافة</button>
    </div>
  </form>
</div>

<div class="table-wrap">
  <table>
    <thead><tr><th>العنوان</th><th>الرابط</th><th>الترتيب</th><th>إخفاء</th><th>فتح بنافذة جديدة</th><th></th></tr></thead>
    <tbody>
    <?php foreach ($items as $item): ?>
      <tr>
        <td colspan="6">
        <form method="post" action="<?= Url::admin('menus/' . $item['id'] . '/update?slug=' . $slug) ?>" style="display:flex;gap:8px;align-items:center;flex-wrap:wrap">
          <?= Csrf::field() ?>
          <input class="form-control" name="label" value="<?= View::e($item['label']) ?>" style="flex:1;min-width:120px">
          <input class="form-control" name="url" value="<?= View::e($item['url'] ?? '') ?>" style="flex:2;min-width:160px">
          <input class="form-control" type="number" name="sort_order" value="<?= (int) $item['sort_order'] ?>" style="width:70px">
          <select class="form-control" name="hide_on" style="width:110px">
            <option value="none" <?= $item['hide_on'] === 'none' ? 'selected' : '' ?>>لا شيء</option>
            <option value="desktop" <?= $item['hide_on'] === 'desktop' ? 'selected' : '' ?>>الكمبيوتر</option>
            <option value="mobile" <?= $item['hide_on'] === 'mobile' ? 'selected' : '' ?>>الجوال</option>
          </select>
          <label style="font-size:.8rem;display:flex;gap:4px;align-items:center"><input type="checkbox" name="open_new_tab" <?= $item['open_new_tab'] ? 'checked' : '' ?>> جديدة</label>
          <button class="btn btn-secondary btn-sm" type="submit">حفظ</button>
          <button class="btn btn-danger btn-sm" type="submit" formaction="<?= Url::admin('menus/' . $item['id'] . '/delete?slug=' . $slug) ?>" onclick="return confirm('حذف هذا العنصر؟')">حذف</button>
        </form>
        </td>
      </tr>
    <?php endforeach; ?>
    <?php if (empty($items)): ?><tr><td colspan="6" class="form-hint">لا توجد عناصر في هذه القائمة بعد.</td></tr><?php endif; ?>
    </tbody>
  </table>
</div>
