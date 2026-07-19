<?php
/** @var array $branches */
use Core\Support\Csrf;
use Core\Support\Url;
use Core\View;
?>
<div class="card-box">
  <h3 style="margin-top:0;font-size:.95rem">إضافة فرع جديد</h3>
  <form method="post" action="<?= Url::admin('branches/store') ?>" class="form-row cols-3">
    <?= Csrf::field() ?>
    <div class="form-group"><label>اسم الفرع</label><input class="form-control" name="name" required></div>
    <div class="form-group"><label>نبذة</label><input class="form-control" name="description"></div>
    <div class="form-group" style="display:flex;align-items:end;gap:10px">
      <label style="font-size:.85rem;display:flex;gap:6px;align-items:center"><input type="checkbox" name="show_public_page"> صفحة عامة</label>
      <button class="btn btn-primary" type="submit">إضافة</button>
    </div>
  </form>
</div>

<div class="table-wrap">
  <table>
    <thead><tr><th>الاسم</th><th>نبذة</th><th>الترتيب</th><th>صفحة عامة</th><th>الحالة</th><th></th></tr></thead>
    <tbody>
    <?php foreach ($branches as $b): ?>
      <tr>
        <td colspan="6">
        <form method="post" action="<?= Url::admin('branches/' . $b['id'] . '/update') ?>" style="display:flex;gap:8px;align-items:center;flex-wrap:wrap">
          <?= Csrf::field() ?>
          <input class="form-control" name="name" value="<?= View::e($b['name']) ?>" style="flex:1;min-width:120px">
          <input class="form-control" name="description" value="<?= View::e($b['description'] ?? '') ?>" style="flex:2;min-width:160px">
          <input class="form-control" type="number" name="sort_order" value="<?= (int) $b['sort_order'] ?>" style="width:70px">
          <label style="font-size:.8rem;display:flex;gap:4px;align-items:center"><input type="checkbox" name="show_public_page" <?= $b['show_public_page'] ? 'checked' : '' ?>> عامة</label>
          <select class="form-control" name="status" style="width:110px">
            <option value="active" <?= $b['status'] === 'active' ? 'selected' : '' ?>>مفعل</option>
            <option value="inactive" <?= $b['status'] === 'inactive' ? 'selected' : '' ?>>موقوف</option>
          </select>
          <button class="btn btn-secondary btn-sm" type="submit">حفظ</button>
          <button class="btn btn-danger btn-sm" type="submit" formaction="<?= Url::admin('branches/' . $b['id'] . '/delete') ?>" data-confirm="حذف هذا الفرع؟">حذف</button>
        </form>
        </td>
      </tr>
    <?php endforeach; ?>
    <?php if (empty($branches)): ?><tr><td colspan="6" class="form-hint">لا توجد فروع بعد. عند إضافة أول فرع، تُفعّل خصائص الفروع تلقائيًا في النظام.</td></tr><?php endif; ?>
    </tbody>
  </table>
</div>
