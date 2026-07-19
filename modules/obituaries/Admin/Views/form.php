<?php
/** @var array|null $item
 *  @var array $cities
 */
use Core\Support\Csrf;
use Core\Support\Url;
use Core\View;

$isEdit = $item !== null;
$action = $isEdit ? Url::admin('obituaries/' . $item['id'] . '/update') : Url::admin('obituaries/store');
?>
<form method="post" action="<?= $action ?>" class="card-box">
  <?= Csrf::field() ?>

  <div class="form-row cols-2">
    <div class="form-group"><label>اسم المتوفى</label><input class="form-control" name="name" value="<?= View::e($item['name'] ?? '') ?>" required></div>
    <div class="form-group">
      <label>المدينة</label>
      <select class="form-control" name="city_id">
        <option value="">— اختر —</option>
        <?php foreach ($cities as $c): ?>
          <option value="<?= (int) $c['id'] ?>" <?= (int) ($item['city_id'] ?? 0) === (int) $c['id'] ? 'selected' : '' ?>><?= View::e($c['name']) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
  </div>

  <div class="form-row cols-2">
    <div class="form-group"><label>تاريخ الوفاة</label><input class="form-control" type="date" name="deceased_on" value="<?= View::e($item['deceased_on'] ?? '') ?>"></div>
    <div class="form-group">
      <label>الحالة</label>
      <select class="form-control" name="status">
        <option value="active" <?= ($item['status'] ?? 'active') === 'active' ? 'selected' : '' ?>>نشط (يظهر في الرئيسية)</option>
        <option value="archived" <?= ($item['status'] ?? '') === 'archived' ? 'selected' : '' ?>>مؤرشف</option>
      </select>
    </div>
  </div>

  <div class="form-row cols-2">
    <div class="form-group"><label>مكان العزاء</label><input class="form-control" name="condolence_venue" value="<?= View::e($item['condolence_venue'] ?? '') ?>" placeholder="مثال: مقر العائلة — حي الروضة"></div>
    <div class="form-group"><label>أوقات العزاء</label><input class="form-control" name="condolence_times" value="<?= View::e($item['condolence_times'] ?? '') ?>" placeholder="مثال: ثلاثة أيام من بعد صلاة المغرب"></div>
  </div>

  <div class="form-group"><label>رابط موقع العزاء على الخريطة (اختياري)</label><input class="form-control" dir="ltr" name="condolence_map_url" value="<?= View::e($item['condolence_map_url'] ?? '') ?>" placeholder="https://maps.google.com/..."></div>

  <div class="form-group"><label>تفاصيل إضافية (اختياري)</label><textarea class="form-control" name="details" rows="4" placeholder="نبذة عن المتوفى، تفاصيل الصلاة والدفن، أرقام تواصل..."><?= View::e($item['details'] ?? '') ?></textarea></div>

  <button class="btn btn-primary" type="submit"><?= $isEdit ? 'تحديث الإعلان' : 'حفظ الإعلان' ?></button>
</form>
