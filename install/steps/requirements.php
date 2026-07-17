<?php
use Core\Support\Csrf;

$checks = require __DIR__ . '/requirements_check.php';
?>
<h3 style="margin-top:0;font-size:1rem">إصدار PHP</h3>
<div class="check-row">
  <span>الإصدار الحالي: <?= htmlspecialchars($checks['php_version']) ?> (المطلوب 8.1 فأعلى)</span>
  <span class="<?= $checks['php_ok'] ? 'check-ok' : 'check-fail' ?>"><?= $checks['php_ok'] ? '✓ متوافق' : '✗ غير متوافق' ?></span>
</div>

<h3 style="font-size:1rem">إضافات PHP المطلوبة</h3>
<?php foreach ($checks['extensions'] as $ext => $c): ?>
  <div class="check-row">
    <span><?= htmlspecialchars($ext) ?> — <?= htmlspecialchars($c['label']) ?></span>
    <span class="<?= $c['ok'] ? 'check-ok' : 'check-fail' ?>"><?= $c['ok'] ? '✓ متوفرة' : '✗ غير متوفرة' ?></span>
  </div>
<?php endforeach; ?>

<h3 style="font-size:1rem">إضافات اختيارية</h3>
<?php foreach ($checks['optional'] as $ext => $c): ?>
  <div class="check-row">
    <span><?= htmlspecialchars($ext) ?> — <?= htmlspecialchars($c['label']) ?></span>
    <span class="<?= $c['ok'] ? 'check-ok' : '' ?>" style="color:<?= $c['ok'] ? '' : '#a98a2b' ?>"><?= $c['ok'] ? '✓ متوفرة' : '△ غير متوفرة (اختيارية)' ?></span>
  </div>
<?php endforeach; ?>

<h3 style="font-size:1rem">صلاحيات المجلدات</h3>
<?php foreach ($checks['permissions'] as $path => $c): ?>
  <div class="check-row">
    <span><?= htmlspecialchars($c['label']) ?></span>
    <span class="<?= $c['ok'] ? 'check-ok' : 'check-fail' ?>"><?= $c['ok'] ? '✓ قابل للكتابة' : '✗ غير قابل للكتابة' ?></span>
  </div>
<?php endforeach; ?>

<form method="post" style="margin-top:20px">
  <?= Csrf::field() ?>
  <?php if (!$checks['all_ok']): ?>
    <p class="form-hint">الرجاء تصحيح المتطلبات غير المستوفاة أعلاه (عادة برفع صلاحيات المجلدات إلى 755 عبر مدير الملفات أو FTP، أو تفعيل الإضافة من لوحة تحكم الاستضافة)، ثم إعادة تحميل الصفحة.</p>
    <a class="btn btn-secondary" href="?step=requirements">إعادة الفحص</a>
  <?php else: ?>
    <button class="btn btn-primary" type="submit" style="width:100%">جميع المتطلبات مستوفاة — متابعة</button>
  <?php endif; ?>
</form>
