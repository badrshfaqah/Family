<?php
/** @var array $cities */
use Core\Support\Csrf;
use Core\Support\Url;
use Core\View;
?>
<div class="card-box">
  <form method="post" action="<?= Url::admin('cities/store') ?>" style="display:flex;gap:10px">
    <?= Csrf::field() ?>
    <input class="form-control" name="name" placeholder="اسم مدينة جديدة" required>
    <button class="btn btn-primary" type="submit">إضافة</button>
  </form>
</div>

<div class="table-wrap">
  <table>
    <thead><tr><th>الاسم</th><th>الترتيب</th><th>الحالة</th><th></th></tr></thead>
    <tbody>
    <?php foreach ($cities as $c): ?>
      <tr>
        <td colspan="4">
        <form method="post" action="<?= Url::admin('cities/' . $c['id'] . '/update') ?>" style="display:flex;gap:8px;align-items:center;flex-wrap:wrap">
        <?= Csrf::field() ?>
        <input class="form-control" name="name" value="<?= View::e($c['name']) ?>" style="flex:1;min-width:140px">
        <input class="form-control" type="number" name="sort_order" value="<?= (int) $c['sort_order'] ?>" style="width:80px">
        <select class="form-control" name="status" style="width:120px">
          <option value="active" <?= $c['status'] === 'active' ? 'selected' : '' ?>>مفعلة</option>
          <option value="inactive" <?= $c['status'] === 'inactive' ? 'selected' : '' ?>>موقوفة</option>
        </select>
        <button class="btn btn-secondary btn-sm" type="submit">حفظ</button>
        <button class="btn btn-danger btn-sm" type="submit" formaction="<?= Url::admin('cities/' . $c['id'] . '/delete') ?>" data-confirm="حذف هذه المدينة؟">حذف</button>
        </form>
        </td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
</div>
