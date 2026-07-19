<?php
/** @var array $users
 *  @var int $selectedUserId
 *  @var array|null $result
 *  @var array|null $stats
 */
use Core\Support\Url;
use Core\View;

$actionLabels = [
    'login' => 'تسجيل دخول', 'login_failed' => 'محاولة دخول فاشلة', 'logout' => 'تسجيل خروج',
    'module_install' => 'تثبيت إضافة', 'module_enable' => 'تفعيل إضافة', 'module_disable' => 'تعطيل إضافة',
    'module_uninstall' => 'إزالة إضافة', 'settings_update' => 'تحديث الإعدادات', 'user_create' => 'إضافة مستخدم',
    'user_status' => 'تغيير حالة مستخدم', 'user_delete' => 'حذف مستخدم', 'role_update' => 'تحديث صلاحيات دور',
    'backup_create' => 'إنشاء نسخة احتياطية', 'backup_download' => 'تحميل نسخة احتياطية',
    'backup_delete' => 'حذف نسخة احتياطية', 'backup_restore' => 'استعادة نسخة احتياطية',
    'demo_data_seed' => 'توليد بيانات تجريبية', 'demo_data_purge' => 'حذف البيانات التجريبية',
    'obituary_create' => 'إضافة إعلان وفاة', 'obituary_update' => 'تعديل إعلان وفاة', 'obituary_delete' => 'حذف إعلان وفاة',
    'tree_image' => 'صورة شجرة النسب',
];

$selectedUser = null;
foreach ($users as $u) {
    if ((int) $u['id'] === $selectedUserId) {
        $selectedUser = $u;
        break;
    }
}
?>
<p class="form-hint" style="margin:0 0 14px">شاشة خاصة بمدير النظام: اختر العضو لاستعراض كامل نشاطه المسجل — كل عملية تظهر بوقتها وعنوان IP الذي نُفذت منه.</p>

<form method="get" action="<?= Url::admin('logs/users') ?>" style="display:flex;gap:10px;align-items:end;flex-wrap:wrap;margin-bottom:18px">
  <div class="form-group" style="margin:0;min-width:260px">
    <label>العضو</label>
    <select class="form-control" name="user_id" data-auto-submit>
      <option value="">— اختر عضوًا —</option>
      <?php foreach ($users as $u): ?>
        <option value="<?= (int) $u['id'] ?>" <?= (int) $u['id'] === $selectedUserId ? 'selected' : '' ?>>
          <?= View::e($u['name']) ?> (<?= View::e($u['role_name'] ?? 'بدون دور') ?>)<?= $u['status'] !== 'active' ? ' — موقوف' : '' ?>
        </option>
      <?php endforeach; ?>
    </select>
  </div>
  <noscript><button class="btn btn-primary" type="submit">عرض</button></noscript>
</form>

<?php if ($selectedUser && $stats): ?>
<div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(170px,1fr));gap:10px;margin-bottom:18px">
  <div style="background:#fff;border:1px solid #e5e5e5;border-radius:12px;padding:14px;text-align:center">
    <b style="font-size:1.3rem;display:block"><?= (int) $stats['total'] ?></b>
    <span class="form-hint">إجمالي العمليات</span>
  </div>
  <div style="background:#fff;border:1px solid #e5e5e5;border-radius:12px;padding:14px;text-align:center">
    <b style="font-size:.95rem;display:block"><?= $stats['last_at'] ? View::e($stats['last_at']) : '—' ?></b>
    <span class="form-hint">آخر نشاط</span>
  </div>
  <div style="background:#fff;border:1px solid #e5e5e5;border-radius:12px;padding:14px;text-align:center">
    <b style="font-size:.95rem;display:block"><?= $stats['last_login'] ? View::e($stats['last_login']) : '—' ?></b>
    <span class="form-hint">آخر تسجيل دخول</span>
  </div>
</div>

<div class="table-wrap">
  <table>
    <thead><tr><th>العملية</th><th>الوصف</th><th>العنصر</th><th>IP</th><th>التاريخ</th></tr></thead>
    <tbody>
    <?php foreach ($result['rows'] as $log): ?>
      <tr>
        <td><?= View::e($actionLabels[$log['action']] ?? $log['action']) ?></td>
        <td><?= View::e($log['description']) ?></td>
        <td><?= View::e(trim(($log['subject_type'] ?? '') . ' ' . ($log['subject_id'] ?? ''))) ?></td>
        <td><?= View::e($log['ip_address']) ?></td>
        <td><?= View::e($log['created_at']) ?></td>
      </tr>
    <?php endforeach; ?>
    <?php if (empty($result['rows'])): ?><tr><td colspan="5" class="form-hint">لا يوجد نشاط مسجل لهذا العضو.</td></tr><?php endif; ?>
    </tbody>
  </table>
</div>

<?php $totalPages = max(1, (int) ceil($result['total'] / $result['perPage'])); ?>
<?php if ($totalPages > 1): ?>
<div style="display:flex;gap:6px;margin-top:14px;flex-wrap:wrap">
  <?php for ($p = 1; $p <= $totalPages; $p++): ?>
    <a class="btn btn-sm <?= $p === $result['page'] ? 'btn-primary' : 'btn-secondary' ?>" href="<?= Url::admin('logs/users?user_id=' . $selectedUserId . '&page=' . $p) ?>"><?= $p ?></a>
  <?php endfor; ?>
</div>
<?php endif; ?>

<?php elseif ($selectedUserId > 0): ?>
  <div class="form-hint">العضو المحدد غير موجود.</div>
<?php endif; ?>
