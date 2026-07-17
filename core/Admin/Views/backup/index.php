<?php
/** @var array $backups
 *  @var bool $zipSupported
 */
use Core\Support\Csrf;
use Core\Support\Url;
use Core\View;

function fam_bk_size(int $bytes): string
{
    if ($bytes >= 1048576) return round($bytes / 1048576, 2) . ' م.ب';
    if ($bytes >= 1024) return round($bytes / 1024, 2) . ' ك.ب';
    return $bytes . ' بايت';
}
$typeLabels = ['database' => 'قاعدة البيانات', 'files' => 'الملفات', 'full' => 'نسخة كاملة'];
?>
<div class="grid-cards">
  <div class="card-box">
    <h3 style="margin-top:0">نسخة قاعدة البيانات</h3>
    <p class="form-hint">تُنشئ ملف SQL كامل لجميع جداول النظام.</p>
    <form method="post" action="<?= Url::admin('backup/database') ?>"><?= Csrf::field() ?><button class="btn btn-primary" type="submit">إنشاء نسخة الآن</button></form>
  </div>
  <div class="card-box">
    <h3 style="margin-top:0">نسخة الملفات</h3>
    <p class="form-hint">تُنشئ أرشيف ZIP لمجلد الوسائط المرفوعة وملف الإعدادات.</p>
    <?php if ($zipSupported): ?>
      <form method="post" action="<?= Url::admin('backup/files') ?>"><?= Csrf::field() ?><button class="btn btn-primary" type="submit">إنشاء نسخة الآن</button></form>
    <?php else: ?>
      <p class="form-hint">مكتبة ZipArchive غير متوفرة على هذا السيرفر.</p>
    <?php endif; ?>
  </div>
  <div class="card-box">
    <h3 style="margin-top:0">استعادة نسخة</h3>
    <p class="form-hint" style="color:#a12d24">تحذير: ستُستبدل بيانات قاعدة البيانات الحالية بالكامل بعد أخذ نسخة أمان تلقائية.</p>
    <form method="post" action="<?= Url::admin('backup/restore') ?>" enctype="multipart/form-data" data-confirm="هل أنت متأكد من استعادة هذه النسخة؟ سيتم استبدال البيانات الحالية.">
      <?= Csrf::field() ?>
      <input class="form-control" type="file" name="backup_file" accept=".sql" required style="margin-bottom:10px">
      <button class="btn btn-danger" type="submit">استعادة</button>
    </form>
  </div>
</div>

<div class="table-wrap">
  <table>
    <thead><tr><th>النوع</th><th>الحجم</th><th>التاريخ</th><th></th></tr></thead>
    <tbody>
    <?php foreach ($backups as $b): ?>
      <tr>
        <td><?= View::e($typeLabels[$b['type']] ?? $b['type']) ?></td>
        <td><?= fam_bk_size((int) $b['size_bytes']) ?></td>
        <td><?= View::e($b['created_at']) ?></td>
        <td style="display:flex;gap:6px">
          <a class="btn btn-secondary btn-sm" href="<?= Url::admin('backup/' . rawurlencode($b['file_path']) . '/download') ?>">تحميل</a>
          <form method="post" action="<?= Url::admin('backup/' . $b['id'] . '/delete') ?>" data-confirm="هل تريد حذف هذه النسخة؟"><?= Csrf::field() ?><button class="btn btn-danger btn-sm" type="submit">حذف</button></form>
        </td>
      </tr>
    <?php endforeach; ?>
    <?php if (empty($backups)): ?><tr><td colspan="4" class="form-hint">لا توجد نسخ احتياطية بعد.</td></tr><?php endif; ?>
    </tbody>
  </table>
</div>
