<?php
/** @var array $rows */
use Core\Support\Csrf;
use Core\Support\Str;
use Core\Support\Url;
use Core\View;
?>
<div style="display:flex;justify-content:flex-end;margin-bottom:14px">
  <a class="btn btn-primary" href="<?= Url::admin('directory-templates/create') ?>">+ إضافة قالب</a>
</div>

<div class="table-wrap">
  <table>
    <thead><tr><th>الاسم</th><th>مقتطف من النص</th><th></th></tr></thead>
    <tbody>
    <?php foreach ($rows as $r): ?>
      <tr>
        <td><?= View::e($r['name']) ?></td>
        <td><?= View::e(Str::limit($r['body'], 80)) ?></td>
        <td style="display:flex;gap:6px;flex-wrap:wrap">
          <a class="btn btn-secondary btn-sm" href="<?= Url::admin('directory-templates/' . $r['id'] . '/preview') ?>">معاينة</a>
          <a class="btn btn-secondary btn-sm" href="<?= Url::admin('directory-templates/' . $r['id'] . '/edit') ?>">تعديل</a>
          <form method="post" action="<?= Url::admin('directory-templates/' . $r['id'] . '/copy') ?>"><?= Csrf::field() ?><button class="btn btn-secondary btn-sm" type="submit">نسخ</button></form>
          <form method="post" action="<?= Url::admin('directory-templates/' . $r['id'] . '/delete') ?>" data-confirm="حذف هذا القالب؟"><?= Csrf::field() ?><button class="btn btn-danger btn-sm" type="submit">حذف</button></form>
        </td>
      </tr>
    <?php endforeach; ?>
    <?php if (empty($rows)): ?><tr><td colspan="3" class="form-hint">لا توجد قوالب بعد.</td></tr><?php endif; ?>
    </tbody>
  </table>
</div>
