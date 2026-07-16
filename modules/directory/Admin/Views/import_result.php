<?php
/** @var int $imported
 *  @var int $totalDataRows
 *  @var array $skipped
 */
use Core\Support\Url;
use Core\View;
?>
<div class="grid-cards">
  <div class="stat-box"><b><?= (int) $imported ?></b><span>تم استيرادهم</span></div>
  <div class="stat-box"><b><?= count($skipped) ?></b><span>تم تجاوزهم</span></div>
  <div class="stat-box"><b><?= (int) $totalDataRows ?></b><span>إجمالي صفوف الملف</span></div>
</div>

<?php if (!empty($skipped)): ?>
<div class="card-box">
  <h3 style="margin-top:0;font-size:1rem">الصفوف المتجاوَزة</h3>
  <div class="table-wrap">
    <table>
      <thead><tr><th>رقم الصف</th><th>السبب</th></tr></thead>
      <tbody>
      <?php foreach ($skipped as $s): ?>
        <tr><td><?= (int) $s['row'] ?></td><td><?= View::e($s['reason']) ?></td></tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
<?php endif; ?>

<a class="btn btn-primary" href="<?= Url::admin('directory') ?>">الذهاب إلى قائمة السجلات</a>
