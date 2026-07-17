<?php
/** @var array|null $item */
use Core\Support\Csrf;
use Core\Support\Url;
use Core\View;

$isEdit = $item !== null;
$action = $isEdit ? Url::admin('directory-templates/' . $item['id'] . '/update') : Url::admin('directory-templates/store');
?>
<form method="post" action="<?= $action ?>" class="card-box" style="max-width:640px">
  <?= Csrf::field() ?>
  <div class="form-group"><label>اسم القالب</label><input class="form-control" name="name" value="<?= View::e($item['name'] ?? '') ?>" required></div>
  <div class="form-group">
    <label>نص الرسالة</label>
    <textarea class="form-control" name="body" rows="8" required><?= View::e($item['body'] ?? '') ?></textarea>
    <p class="form-hint">يمكن استخدام المتغيرات: <code>{event_name}</code> <code>{date}</code> <code>{time}</code> <code>{city}</code> <code>{link}</code> — تُستبدل تلقائيًا عند المعاينة.</p>
  </div>
  <button class="btn btn-primary" type="submit"><?= $isEdit ? 'حفظ التعديلات' : 'إضافة' ?></button>
</form>
