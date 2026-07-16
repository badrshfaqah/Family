<?php
/** @var array $modules
 *  @var bool $zipSupported
 */
use Core\Support\Csrf;
use Core\Support\Url;
use Core\View;
?>
<div class="card-box">
  <h2 style="margin-top:0;font-size:1rem">رفع إضافة جديدة (ملف ZIP)</h2>
  <?php if (!$zipSupported): ?>
    <p class="form-hint">مكتبة ZipArchive غير مفعّلة على هذا السيرفر. يمكنك رفع مجلد الإضافة يدويًا عبر مدير الملفات إلى مجلد modules ثم تحديث هذه الصفحة.</p>
  <?php else: ?>
  <form method="post" action="<?= Url::admin('modules/upload') ?>" enctype="multipart/form-data" style="display:flex;gap:10px;align-items:end">
    <?= Csrf::field() ?>
    <div class="form-group" style="flex:1;margin:0"><input class="form-control" type="file" name="module_zip" accept=".zip" required></div>
    <button class="btn btn-primary" type="submit">رفع</button>
  </form>
  <?php endif; ?>
</div>

<div class="grid-cards">
  <?php foreach ($modules as $slug => $m): $manifest = $m['manifest']; $row = $m['row']; ?>
    <div class="card-box">
      <div style="display:flex;justify-content:space-between;align-items:start">
        <h3 style="margin:0 0 6px"><?= View::e($manifest['name'] ?? $slug) ?></h3>
        <?php if ($row): ?>
          <span class="badge <?= $row['status'] === 'enabled' ? 'badge-green' : 'badge-gray' ?>"><?= $row['status'] === 'enabled' ? 'مفعّلة' : 'معطّلة' ?></span>
        <?php else: ?>
          <span class="badge badge-gray">غير مثبتة</span>
        <?php endif; ?>
      </div>
      <p class="form-hint"><?= View::e($manifest['description'] ?? '') ?></p>
      <p class="form-hint">الإصدار: <?= View::e($manifest['version'] ?? '1.0.0') ?> — يتطلب نواة: <?= View::e($manifest['core_version'] ?? '>=1.0.0') ?></p>

      <div style="display:flex;gap:8px;flex-wrap:wrap;margin-top:10px">
        <?php if (!$row): ?>
          <form method="post" action="<?= Url::admin('modules/' . $slug . '/install') ?>"><?= Csrf::field() ?><button class="btn btn-primary btn-sm" type="submit">تثبيت</button></form>
        <?php elseif ($row['status'] === 'enabled'): ?>
          <form method="post" action="<?= Url::admin('modules/' . $slug . '/disable') ?>"><?= Csrf::field() ?><button class="btn btn-secondary btn-sm" type="submit">تعطيل</button></form>
        <?php else: ?>
          <form method="post" action="<?= Url::admin('modules/' . $slug . '/enable') ?>"><?= Csrf::field() ?><button class="btn btn-primary btn-sm" type="submit">تفعيل</button></form>
          <form method="post" action="<?= Url::admin('modules/' . $slug . '/uninstall') ?>" data-confirm="سيتم إزالة الإضافة. هل تريد الاحتفاظ ببياناتها؟ اضغط موافق للمتابعة.">
            <?= Csrf::field() ?>
            <label style="display:flex;align-items:center;gap:6px;font-size:.8rem;margin-bottom:6px"><input type="checkbox" name="keep_data" value="1" checked> الاحتفاظ بالبيانات</label>
            <button class="btn btn-danger btn-sm" type="submit">إزالة الإضافة</button>
          </form>
        <?php endif; ?>
      </div>
    </div>
  <?php endforeach; ?>
</div>
