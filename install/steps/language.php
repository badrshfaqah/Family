<?php
use Core\Support\Csrf;
?>
<p>مرحبًا بك في معالج تثبيت النظام. الرجاء اختيار لغة التثبيت. اللغة العربية هي اللغة الافتراضية والوحيدة المتاحة حاليًا للواجهة.</p>
<form method="post">
  <?= Csrf::field() ?>
  <div class="form-group">
    <label style="display:flex;align-items:center;gap:8px;font-weight:400">
      <input type="radio" name="language" value="ar" checked disabled> العربية (افتراضي)
    </label>
  </div>
  <button class="btn btn-primary" type="submit" style="width:100%">التالي</button>
</form>
