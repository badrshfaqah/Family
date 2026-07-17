<?php
/** @var array $groups */
use Core\Support\Csrf;
use Core\Support\Url;
use Core\View;

$statusLabels = ['active' => 'نشط', 'excluded' => 'مستبعد من التواصل'];
$sourceLabels = ['website_form' => 'نموذج الموقع', 'admin_manual' => 'إدخال يدوي', 'import_csv' => 'استيراد CSV'];
?>
<p class="form-hint">تظهر هنا أرقام الجوال المتكررة بين أكثر من سجل. اختر السجل الذي تريد الإبقاء عليه لكل رقم، وسيتم حذف بقية السجلات المكررة لنفس الرقم.</p>

<?php if (empty($groups)): ?>
  <div class="card-box"><p class="form-hint">لا توجد أرقام مكررة حاليًا.</p></div>
<?php endif; ?>

<?php foreach ($groups as $group): ?>
  <form method="post" action="<?= Url::admin('directory/duplicates/merge') ?>" class="card-box" data-confirm="سيتم حذف كل السجلات الأخرى لهذا الرقم عدا السجل المختار. متابعة؟">
    <?= Csrf::field() ?>
    <input type="hidden" name="phone" value="<?= View::e($group['phone']) ?>">
    <h3 style="margin-top:0" dir="ltr"><?= View::e($group['phone']) ?> <span style="font-size:.8rem;color:var(--a-muted)">(<?= count($group['rows']) ?> سجل)</span></h3>
    <div class="table-wrap">
      <table>
        <thead><tr><th>إبقاء</th><th>الاسم</th><th>الحالة</th><th>المصدر</th><th>تاريخ التسجيل</th></tr></thead>
        <tbody>
        <?php foreach ($group['rows'] as $i => $r): ?>
          <tr>
            <td><input type="radio" name="keep_id" value="<?= (int) $r['id'] ?>" <?= $i === 0 ? 'checked' : '' ?> required></td>
            <td><?= View::e($r['name']) ?></td>
            <td><?= View::e($statusLabels[$r['status']] ?? $r['status']) ?></td>
            <td><?= View::e($sourceLabels[$r['source']] ?? $r['source']) ?></td>
            <td><?= View::e($r['registered_at']) ?></td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <button class="btn btn-primary btn-sm" style="margin-top:10px" type="submit">دمج والاحتفاظ بالسجل المختار</button>
  </form>
<?php endforeach; ?>
