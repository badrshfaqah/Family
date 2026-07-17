<?php
/** @var array $header
 *  @var array $previewRows
 *  @var int $totalDataRows
 *  @var string $csvDataB64
 *  @var bool $branchesEnabled
 *  @var string|null $mappingError
 */
use Core\Support\Csrf;
use Core\Support\Url;
use Core\View;

$fields = [
    'map_name' => 'الاسم *',
    'map_phone' => 'رقم الجوال *',
    'map_city' => 'المدينة',
    'map_email' => 'البريد الإلكتروني',
];
if ($branchesEnabled) {
    $fields['map_branch'] = 'الفرع';
}
?>
<?php if ($mappingError): ?>
  <div class="alert alert-error"><?= View::e($mappingError) ?></div>
<?php endif; ?>

<p class="form-hint">تم العثور على <?= (int) $totalDataRows ?> صف بيانات. حدد أي عمود من ملفك يقابل كل حقل، ثم اضغط "استيراد".</p>

<form method="post" action="<?= Url::admin('directory/import/commit') ?>" class="card-box">
  <?= Csrf::field() ?>
  <input type="hidden" name="csv_data" value="<?= $csvDataB64 ?>">

  <div class="form-row cols-3">
    <?php foreach ($fields as $name => $label): ?>
      <div class="form-group">
        <label><?= View::e($label) ?></label>
        <select class="form-control" name="<?= $name ?>">
          <option value="">— تجاهل —</option>
          <?php foreach ($header as $i => $h): ?>
            <option value="<?= (int) $i ?>"><?= View::e($h) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
    <?php endforeach; ?>
  </div>

  <h3 style="font-size:.95rem">معاينة أول <?= count($previewRows) ?> صف</h3>
  <div class="table-wrap">
    <table>
      <thead><tr><?php foreach ($header as $h): ?><th><?= View::e($h) ?></th><?php endforeach; ?></tr></thead>
      <tbody>
      <?php foreach ($previewRows as $row): ?>
        <tr><?php foreach ($header as $i => $h): ?><td><?= View::e($row[$i] ?? '') ?></td><?php endforeach; ?></tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>

  <button class="btn btn-primary" style="margin-top:14px" type="submit">استيراد</button>
  <a class="btn btn-secondary" href="<?= Url::admin('directory/import') ?>">إلغاء</a>
</form>
