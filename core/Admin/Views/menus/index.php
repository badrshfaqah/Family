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

<p class="form-hint">اسحب العنصر من مقبض ⠿ لإعادة ترتيب القائمة، ويُحفظ الترتيب تلقائيًا.</p>
<div id="menu-reorder-status" class="form-hint" style="min-height:18px"></div>

<div class="table-wrap">
  <table>
    <thead><tr><th style="width:36px"></th><th>العنوان</th><th>الرابط</th><th>إخفاء</th><th>فتح بنافذة جديدة</th><th></th></tr></thead>
    <tbody id="menu-items-list" data-reorder-url="<?= Url::admin('menus/' . $slug . '/reorder') ?>" data-csrf="<?= View::e(\Core\Support\Csrf::token()) ?>">
    <?php foreach ($items as $item): ?>
      <tr draggable="true" data-id="<?= (int) $item['id'] ?>" class="menu-row">
        <td style="cursor:grab;font-size:1.2rem;color:var(--a-muted);text-align:center" class="drag-handle">⠿</td>
        <td colspan="5">
        <form method="post" action="<?= Url::admin('menus/' . $item['id'] . '/update?slug=' . $slug) ?>" style="display:flex;gap:8px;align-items:center;flex-wrap:wrap">
          <?= Csrf::field() ?>
          <input class="form-control" name="label" value="<?= View::e($item['label']) ?>" style="flex:1;min-width:120px">
          <input class="form-control" name="url" value="<?= View::e($item['url'] ?? '') ?>" style="flex:2;min-width:160px">
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

<script>
(function () {
  var list = document.getElementById('menu-items-list');
  if (!list) return;
  var status = document.getElementById('menu-reorder-status');
  var dragged = null;

  list.querySelectorAll('.menu-row').forEach(function (row) {
    row.addEventListener('dragstart', function () {
      dragged = row;
      row.style.opacity = '.4';
    });
    row.addEventListener('dragend', function () {
      row.style.opacity = '';
      saveOrder();
    });
    row.addEventListener('dragover', function (e) {
      e.preventDefault();
      var target = e.target.closest('.menu-row');
      if (!target || target === dragged) return;
      var rect = target.getBoundingClientRect();
      var before = (e.clientY - rect.top) < rect.height / 2;
      list.insertBefore(dragged, before ? target : target.nextSibling);
    });
  });

  function saveOrder() {
    var ids = Array.from(list.querySelectorAll('.menu-row')).map(function (r) { return r.getAttribute('data-id'); });
    var body = new URLSearchParams();
    body.set('csrf_token', list.getAttribute('data-csrf'));
    ids.forEach(function (id) { body.append('order[]', id); });

    status.textContent = 'جارٍ الحفظ...';
    fetch(list.getAttribute('data-reorder-url'), { method: 'POST', body: body, credentials: 'same-origin' })
      .then(function (r) { return r.json(); })
      .then(function (data) {
        status.textContent = data.ok ? 'تم حفظ الترتيب.' : 'تعذر حفظ الترتيب.';
        setTimeout(function () { status.textContent = ''; }, 2000);
      })
      .catch(function () { status.textContent = 'تعذر حفظ الترتيب.'; });
  }
})();
</script>
