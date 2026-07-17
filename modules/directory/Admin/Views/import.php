<?php
use Core\Support\Csrf;
use Core\Support\Url;
?>
<div class="card-box" style="max-width:640px">
  <p class="form-hint">
    الاستيراد يتم عبر ملف CSV بسيط (بدون أي إضافات خارجية) — يمكنك فتحه وتحريره مباشرة من إكسل.
    يجب أن يحتوي الملف على سطر عناوين أول، ثم بيانات جهات الاتصال.
  </p>
  <p><a class="btn btn-secondary btn-sm" href="<?= Url::admin('directory/template.csv') ?>">تنزيل نموذج CSV فارغ</a></p>

  <form method="post" action="<?= Url::admin('directory/import/preview') ?>" enctype="multipart/form-data">
    <?= Csrf::field() ?>
    <div class="form-group">
      <label>ملف CSV</label>
      <input class="form-control" type="file" name="csv_file" accept=".csv,text/csv" required>
    </div>
    <button class="btn btn-primary" type="submit">معاينة الملف</button>
  </form>
</div>
