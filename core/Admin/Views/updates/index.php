<?php
/** @var string $repo
 *  @var bool $hasToken
 *  @var string $currentVersion
 *  @var array|null $latest
 *  @var string $checkError
 *  @var bool $zipAvailable
 *  @var int $pendingMigrations
 */
use Core\Support\Csrf;
use Core\Support\Url;
use Core\View;
?>
<p class="form-hint" style="margin:0 0 16px">
  التحديث يسحب أحدث نسخة من مستودع GitHub ويستبدل ملفات النظام تلقائيًا،
  مع الحفاظ الكامل على الإعدادات (config.php) وكل المرفوعات والنسخ (storage).
</p>

<div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:10px;margin-bottom:18px">
  <div style="background:#fff;border:1px solid #e5e5e5;border-radius:12px;padding:16px;text-align:center">
    <b style="font-size:1.4rem;display:block"><?= View::e($currentVersion) ?></b>
    <span class="form-hint">الإصدار المركّب حاليًا</span>
  </div>
  <div style="background:#fff;border:1px solid #e5e5e5;border-radius:12px;padding:16px;text-align:center">
    <b style="font-size:1.05rem;display:block"><?= $latest ? View::e($latest['label']) : '—' ?></b>
    <span class="form-hint">الأحدث على GitHub</span>
  </div>
</div>

<?php if ($checkError): ?><div class="alert alert-error"><?= View::e($checkError) ?></div><?php endif; ?>

<form method="post" action="<?= Url::admin('updates/migrate') ?>" class="card-box" style="max-width:560px;margin-bottom:18px">
  <?= Csrf::field() ?>
  <p style="margin:0 0 6px"><b>🗄 تحديث جداول قاعدة البيانات</b></p>
  <p class="form-hint" style="margin:0 0 14px">
    يضيف الجداول والأعمدة الجديدة التي تتطلبها النسخة الحالية من الملفات دون المساس ببياناتك.
    استخدمه إذا رفعت ملفات النظام يدويًا (FTP / لوحة الاستضافة) بدل التحديث الذاتي،
    أو ظهرت أخطاء تدل على جداول أو أعمدة ناقصة.
    <?php if ($pendingMigrations > 0): ?>
      <br><b style="color:#b45309">يوجد <?= (int) $pendingMigrations ?> ترحيل غير مطبق على قاعدة البيانات.</b>
    <?php else: ?>
      <br>✅ جداول قاعدة البيانات محدثة مع النسخة الحالية.
    <?php endif; ?>
  </p>
  <button class="btn btn-secondary" type="submit">🗄 تحديث الجداول الآن</button>
</form>
<?php if (!$zipAvailable): ?><div class="alert alert-error">امتداد zip غير متوفر على الخادم — التحديث الذاتي يتطلبه لفك الحزمة.</div><?php endif; ?>

<form method="post" action="<?= Url::admin('updates/save-repo') ?>" class="card-box" style="max-width:560px;margin-bottom:18px">
  <?= Csrf::field() ?>
  <div class="form-group">
    <label>مستودع GitHub</label>
    <input class="form-control" dir="ltr" name="repo" value="<?= View::e($repo) ?>" placeholder="owner/repo — مثال: almgrat/family-cms">
    <div class="form-hint">يقبل الصيغة owner/repo أو رابط GitHub كاملًا. عند وجود Releases يُسحب أحدث إصدار منشور، وإلا فأحدث نسخة من الفرع الرئيسي.</div>
  </div>
  <div class="form-group">
    <label>مفتاح وصول GitHub (للمستودعات الخاصة) <?= $hasToken ? '— ✅ محفوظ' : '' ?></label>
    <input class="form-control" dir="ltr" type="password" name="token" autocomplete="off" placeholder="<?= $hasToken ? 'اتركه فارغًا للإبقاء على المفتاح الحالي' : 'github_pat_...' ?>">
    <div class="form-hint">
      مطلوب فقط إذا كان المستودع خاصًا (Private): أنشئ Fine-grained token من
      GitHub → Settings → Developer settings، مقصورًا على هذا المستودع بصلاحية
      Contents: Read-only فقط.
    </div>
    <?php if ($hasToken): ?>
    <label style="display:flex;gap:6px;align-items:center;font-weight:400;margin-top:8px">
      <input type="checkbox" name="remove_token" value="1"> حذف المفتاح المحفوظ
    </label>
    <?php endif; ?>
  </div>
  <button class="btn btn-secondary" type="submit">حفظ الإعدادات</button>
</form>

<?php if ($repo !== '' && $zipAvailable): ?>
<form method="post" action="<?= Url::admin('updates/run') ?>" class="card-box" style="max-width:560px">
  <?= Csrf::field() ?>
  <p style="margin:0 0 6px"><b>⬆️ تحديث النظام الآن</b></p>
  <p class="form-hint" style="margin:0 0 14px">
    يُنصح بأخذ نسخة احتياطية من <a href="<?= Url::admin('backup') ?>">شاشة النسخ الاحتياطي</a> قبل التحديث.
    يستبدل التحديث ملفات النظام (النواة ولوحة التحكم والإضافات والقالب) ثم يطبق تلقائيًا
    أي ترحيلات جديدة على بنية قاعدة البيانات — دون المساس بالإعدادات أو المرفوعات أو بياناتك.
  </p>
  <button class="btn btn-primary btn-block" type="submit" data-confirm="سحب أحدث نسخة من GitHub وتحديث النظام الآن؟ تأكد من وجود نسخة احتياطية حديثة.">⬆️ تحديث إلى الأحدث</button>
</form>
<?php endif; ?>
