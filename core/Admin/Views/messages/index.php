<?php
/** @var array $rows */
use Core\Support\Csrf;
use Core\Support\Url;
use Core\View;
?>
<p class="form-hint" style="margin:0 0 14px">رسائل الزوار الواردة من نموذج "راسل الإدارة" — الرسائل الجديدة مميزة بخلفية ذهبية.</p>

<?php if (empty($rows)): ?>
  <div style="background:#fff;border:1.5px dashed #ddd;border-radius:12px;padding:36px;text-align:center;color:#888">
    ✉️ لا توجد رسائل بعد — رابط النموذج للزوار: <a href="<?= Url::to('contact') ?>" target="_blank"><?= Url::to('contact') ?></a>
  </div>
<?php endif; ?>

<?php foreach ($rows as $m): ?>
<div style="background:<?= $m['status'] === 'new' ? '#fff8ec' : '#fff' ?>;border:1px solid <?= $m['status'] === 'new' ? '#e0b95a' : '#e5e5e5' ?>;border-radius:12px;padding:14px 16px;margin-bottom:10px">
  <div style="display:flex;gap:10px;align-items:center;flex-wrap:wrap;margin-bottom:8px">
    <?php if ($m['status'] === 'new'): ?><span class="badge badge-green">جديدة</span><?php endif; ?>
    <b><?= View::e($m['name']) ?></b>
    <?php if (!empty($m['phone'])): ?><span dir="ltr" style="color:#666;font-size:.85rem">📱 <?= View::e($m['phone']) ?></span><?php endif; ?>
    <span style="color:#999;font-size:.78rem;margin-inline-start:auto"><?= View::e($m['created_at']) ?></span>
  </div>
  <?php if (!empty($m['subject'])): ?><p style="margin:0 0 6px;font-weight:700;font-size:.9rem">📌 <?= View::e($m['subject']) ?></p><?php endif; ?>
  <p style="margin:0;color:#444;font-size:.9rem;line-height:1.9;white-space:pre-line"><?= View::e($m['message']) ?></p>
  <div style="display:flex;gap:6px;margin-top:12px">
    <?php if ($m['status'] === 'new'): ?>
    <form method="post" action="<?= Url::admin('messages/' . $m['id'] . '/read') ?>"><?= Csrf::field() ?><button class="btn btn-secondary btn-sm" type="submit">تمت القراءة ✓</button></form>
    <?php endif; ?>
    <?php if (!empty($m['phone'])): ?>
    <a class="btn btn-secondary btn-sm" target="_blank" rel="noopener" href="https://wa.me/<?= View::e(preg_replace('/^0/', '966', preg_replace('/\D/', '', $m['phone']))) ?>">رد واتساب 💬</a>
    <?php endif; ?>
    <form method="post" action="<?= Url::admin('messages/' . $m['id'] . '/delete') ?>"><?= Csrf::field() ?><button class="btn btn-danger btn-sm" type="submit" data-confirm="حذف هذه الرسالة نهائيًا؟">حذف</button></form>
  </div>
</div>
<?php endforeach; ?>
