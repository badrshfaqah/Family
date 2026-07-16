<?php
/** @var array $stats
 *  @var array $recentActivity
 *  @var int $storageUsed
 *  @var int $modulesCount
 */
use Core\Terms;
use Core\View;

function fam_format_size(int $bytes): string
{
    if ($bytes >= 1073741824) return round($bytes / 1073741824, 2) . ' جيجابايت';
    if ($bytes >= 1048576) return round($bytes / 1048576, 2) . ' ميجابايت';
    if ($bytes >= 1024) return round($bytes / 1024, 2) . ' كيلوبايت';
    return $bytes . ' بايت';
}

$actionLabels = [
    'login' => 'تسجيل دخول', 'login_failed' => 'محاولة دخول فاشلة', 'logout' => 'تسجيل خروج',
    'module_install' => 'تثبيت إضافة', 'module_enable' => 'تفعيل إضافة', 'module_disable' => 'تعطيل إضافة',
];
?>
<div class="grid-cards">
  <div class="stat-box"><b><?= (int) $stats['news'] ?></b><span><?= View::e(Terms::phrase('news')) ?></span></div>
  <div class="stat-box"><b><?= (int) $stats['events'] ?></b><span><?= View::e(Terms::phrase('events')) ?></span></div>
  <div class="stat-box"><b><?= (int) $stats['gatherings_active'] ?></b><span>جمعات نشطة</span></div>
  <div class="stat-box"><b><?= (int) $stats['directory'] ?></b><span><?= View::e(Terms::phrase('family_directory')) ?></span></div>
  <div class="stat-box"><b><?= (int) $stats['newsletter'] ?></b><span>مشتركو البريد</span></div>
  <div class="stat-box"><b><?= fam_format_size($storageUsed) ?></b><span>مساحة الوسائط المستخدمة</span></div>
  <div class="stat-box"><b><?= $modulesCount ?></b><span>إضافة مثبتة</span></div>
</div>

<div class="card-box">
  <h2 style="margin-top:0;font-size:1rem">آخر العمليات</h2>
  <?php if (empty($recentActivity)): ?>
    <p class="form-hint">لا توجد عمليات مسجلة بعد.</p>
  <?php else: ?>
    <div class="table-wrap">
      <table>
        <thead><tr><th>المستخدم</th><th>العملية</th><th>الوصف</th><th>التاريخ</th></tr></thead>
        <tbody>
        <?php foreach ($recentActivity as $log): ?>
          <tr>
            <td><?= View::e($log['user_name']) ?></td>
            <td><?= View::e($actionLabels[$log['action']] ?? $log['action']) ?></td>
            <td><?= View::e($log['description']) ?></td>
            <td><?= View::e($log['created_at']) ?></td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  <?php endif; ?>
</div>
