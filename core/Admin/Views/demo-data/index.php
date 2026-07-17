<?php
/** @var bool $hasDemoData
 *  @var array $disabledModules
 *  @var bool $canManageModules
 */
use Core\Support\Csrf;
use Core\Support\Url;
use Core\View;
?>
<div class="card-box">
  <p class="form-hint">تساعدك البيانات التجريبية على معاينة شكل الموقع فور تثبيته: أخبار ومناسبات ومواعيد وجمعات وشجرة نسب وألبومات وأرشيف وإعلانات وصفحات — جميعها ببيانات وهمية واضحة، بدون أي أسماء أو أرقام حقيقية.</p>

  <?php if ($disabledModules): ?>
    <div class="alert alert-error" style="margin-top:10px">
      <strong>تنبيه:</strong> البيانات التجريبية تُولَّد فقط للإضافات المفعّلة. الإضافات التالية غير مفعّلة حاليًا ولن يُولَّد لها أي محتوى:
      <div style="margin-top:6px"><?= View::e(implode('، ', array_column($disabledModules, 'name'))) ?></div>
    </div>
    <?php if ($canManageModules): ?>
      <form method="post" action="<?= Url::admin('demo-data/enable-modules') ?>" style="margin-bottom:14px">
        <?= Csrf::field() ?>
        <button class="btn btn-secondary" type="submit">تثبيت وتفعيل جميع الإضافات دفعة واحدة</button>
      </form>
    <?php else: ?>
      <p class="form-hint">اطلب من مدير النظام تفعيلها من شاشة "الإضافات" أولًا.</p>
    <?php endif; ?>
  <?php endif; ?>

  <?php if ($hasDemoData): ?>
    <div class="alert alert-success" style="margin-top:10px">توجد بيانات تجريبية في الموقع حاليًا.</div>
    <form method="post" action="<?= Url::admin('demo-data/purge') ?>" data-confirm="سيتم حذف جميع البيانات التجريبية التي أُنشئت من هذه الشاشة فقط، ولن يتأثر أي محتوى آخر أضفته بنفسك. متابعة؟">
      <?= Csrf::field() ?>
      <button class="btn btn-danger" type="submit">حذف جميع البيانات التجريبية</button>
    </form>
  <?php else: ?>
    <form method="post" action="<?= Url::admin('demo-data/seed') ?>" style="margin-top:10px">
      <?= Csrf::field() ?>
      <button class="btn btn-primary" type="submit">توليد بيانات تجريبية الآن</button>
    </form>
  <?php endif; ?>
</div>
