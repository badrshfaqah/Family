<?php
/** @var bool $hasDemoData */
use Core\Support\Csrf;
use Core\Support\Url;
?>
<div class="card-box">
  <p class="form-hint">تساعدك البيانات التجريبية على معاينة شكل الموقع فور تثبيته: خبر، مناسبة، موعد في الرزنامة، جمعة، سلسلة نسب بسيطة، ألبوم صور، صفحة تعريفية، وإعلان — جميعها ببيانات وهمية واضحة، بدون أي أسماء أو أرقام حقيقية.</p>

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
