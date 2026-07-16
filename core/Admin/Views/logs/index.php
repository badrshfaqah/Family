<?php
/** @var array $result */
use Core\Support\Url;
use Core\View;

$actionLabels = [
    'login' => 'تسجيل دخول', 'login_failed' => 'محاولة دخول فاشلة', 'logout' => 'تسجيل خروج',
    'module_install' => 'تثبيت إضافة', 'module_enable' => 'تفعيل إضافة', 'module_disable' => 'تعطيل إضافة',
    'module_uninstall' => 'إزالة إضافة', 'settings_update' => 'تحديث الإعدادات', 'user_create' => 'إضافة مستخدم',
    'user_status' => 'تغيير حالة مستخدم', 'user_delete' => 'حذف مستخدم', 'role_update' => 'تحديث صلاحيات دور',
    'backup_create' => 'إنشاء نسخة احتياطية', 'backup_download' => 'تحميل نسخة احتياطية',
    'backup_delete' => 'حذف نسخة احتياطية', 'backup_restore' => 'استعادة نسخة احتياطية',
];

$totalPages = max(1, (int) ceil($result['total'] / $result['perPage']));
?>
<div class="table-wrap">
  <table>
    <thead><tr><th>المستخدم</th><th>العملية</th><th>الوصف</th><th>العنصر</th><th>IP</th><th>التاريخ</th></tr></thead>
    <tbody>
    <?php foreach ($result['rows'] as $log): ?>
      <tr>
        <td><?= View::e($log['user_name']) ?></td>
        <td><?= View::e($actionLabels[$log['action']] ?? $log['action']) ?></td>
        <td><?= View::e($log['description']) ?></td>
        <td><?= View::e(trim(($log['subject_type'] ?? '') . ' ' . ($log['subject_id'] ?? ''))) ?></td>
        <td><?= View::e($log['ip_address']) ?></td>
        <td><?= View::e($log['created_at']) ?></td>
      </tr>
    <?php endforeach; ?>
    <?php if (empty($result['rows'])): ?><tr><td colspan="6" class="form-hint">لا توجد عمليات مسجلة.</td></tr><?php endif; ?>
    </tbody>
  </table>
</div>

<?php if ($totalPages > 1): ?>
<div style="display:flex;gap:6px;margin-top:14px">
  <?php for ($p = 1; $p <= $totalPages; $p++): ?>
    <a class="btn btn-sm <?= $p === $result['page'] ? 'btn-primary' : 'btn-secondary' ?>" href="<?= Url::admin('logs?page=' . $p) ?>"><?= $p ?></a>
  <?php endfor; ?>
</div>
<?php endif; ?>
