<?php
/** @var bool $supported
 *  @var int $subscribers
 *  @var array $history
 */
use Core\Support\Csrf;
use Core\Support\Url;
use Core\View;
?>
<?php if (!$supported): ?>
  <div class="alert alert-error">التنبيهات غير متاحة: الخادم لا يوفر دوال التشفير المطلوبة (OpenSSL / hash_hkdf / cURL).</div>
<?php else: ?>

<div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:10px;margin-bottom:18px">
  <div style="background:#fff;border:1px solid #e5e5e5;border-radius:12px;padding:14px;text-align:center">
    <b style="font-size:1.5rem;display:block"><?= $subscribers ?></b>
    <span class="form-hint">جهاز مشترك في التنبيهات</span>
  </div>
</div>

<p class="form-hint" style="margin:0 0 14px">
  يشترك الزوار من صفحة <a href="<?= Url::to('install-app') ?>" target="_blank">إضافة الموقع للشاشة الرئيسية</a>.
  التنبيه يصل للأجهزة المشتركة فورًا حتى والموقع مغلق (على الآيفون يتطلب تثبيت الموقع كتطبيق أولًا).
</p>

<form method="post" action="<?= Url::admin('notifications/send') ?>" class="card-box" style="max-width:560px">
  <?= Csrf::field() ?>

  <div class="form-group">
    <label>عنوان التنبيه</label>
    <input class="form-control" name="title" maxlength="80" placeholder="مثال: دعوة لحضور اجتماع العائلة" required>
  </div>

  <div class="form-group">
    <label>نص التنبيه</label>
    <textarea class="form-control" name="body" rows="3" maxlength="200" placeholder="مثال: يسرنا دعوتكم لحضور الاجتماع السنوي يوم الجمعة القادمة بعد صلاة العصر"></textarea>
  </div>

  <div class="form-group">
    <label>الرابط عند الضغط على التنبيه (اختياري)</label>
    <input class="form-control" dir="ltr" name="url" placeholder="calendar أو رابط كامل https://...">
    <div class="form-hint">اتركه فارغًا لفتح الصفحة الرئيسية.</div>
  </div>

  <button class="btn btn-primary btn-block" type="submit" data-confirm="إرسال التنبيه الآن لجميع الأجهزة المشتركة؟">🔔 إرسال التنبيه للجميع (<?= $subscribers ?>)</button>
</form>

<h3 style="margin:26px 0 10px">التنبيهات السابقة</h3>
<div class="table-wrap">
  <table>
    <thead><tr><th>التنبيه</th><th>الرابط</th><th>أرسله</th><th>النتيجة</th><th>التاريخ</th></tr></thead>
    <tbody>
    <?php foreach ($history as $h): ?>
      <tr>
        <td>
          <b><?= View::e($h['title']) ?></b>
          <?php if (!empty($h['body'])): ?><div class="form-hint" style="margin-top:2px"><?= View::e($h['body']) ?></div><?php endif; ?>
        </td>
        <td dir="ltr" style="max-width:180px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap"><?= View::e($h['url'] ?? '—') ?></td>
        <td><?= View::e($h['sent_by_name']) ?></td>
        <td>
          <span class="badge badge-green">وصل <?= (int) $h['sent_count'] ?></span>
          <?php if ($h['failed_count']): ?><span class="badge badge-gray">فشل <?= (int) $h['failed_count'] ?></span><?php endif; ?>
        </td>
        <td style="white-space:nowrap"><?= View::e($h['created_at']) ?></td>
      </tr>
    <?php endforeach; ?>
    <?php if (empty($history)): ?><tr><td colspan="5" class="form-hint">لم تُرسل أي تنبيهات بعد.</td></tr><?php endif; ?>
    </tbody>
  </table>
</div>

<?php endif; ?>
