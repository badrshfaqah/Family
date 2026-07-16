<?php
/** @var string $coreVersion
 *  @var array $modules
 *  @var array $installedMap
 *  @var string $changelogHtml
 */
use Core\View;

$statusLabels = [
    'enabled' => ['نشطة', 'badge-green'],
    'disabled' => ['معطّلة', 'badge-gray'],
];
?>
<div class="card-box">
  <h2 style="margin-top:0">نظام إدارة محتوى العوائل والقبائل</h2>
  <p class="form-hint">
    نظام متكامل لإدارة الموقع الرسمي للعائلة أو القبيلة: هوية وتخصيص كامل، لوحة تحكم عربية RTL،
    ومجموعة إضافات جاهزة للأخبار والمناسبات والجمعات وجوال العائلة وغيرها — دون كشف أي بيانات
    شخصية للزوار العامّين.
  </p>
  <p><strong>إصدار النواة الحالي:</strong> <?= View::e($coreVersion) ?></p>
</div>

<div class="card-box">
  <h2 style="margin-top:0">الإضافات المتوفرة</h2>
  <p class="form-hint">هذه القائمة مبنية مباشرة من ملفات الإضافات الموجودة على الخادم، وستظهر أي إضافة جديدة هنا تلقائيًا فور إضافتها.</p>
  <div class="table-wrap">
    <table>
      <thead><tr><th>الإضافة</th><th>الوصف</th><th>الإصدار</th><th>الحالة</th></tr></thead>
      <tbody>
      <?php foreach ($modules as $slug => $module): ?>
        <?php
          $row = $installedMap[$slug] ?? null;
          $status = $row['status'] ?? null;
          [$statusLabel, $statusClass] = $statusLabels[$status] ?? ['غير مثبَّتة', 'badge-gray'];
        ?>
        <tr>
          <td><?= View::e($module['name'] ?? $slug) ?></td>
          <td><?= View::e($module['description'] ?? '') ?></td>
          <td><?= View::e($module['version'] ?? '') ?></td>
          <td><span class="badge <?= $statusClass ?>"><?= View::e($statusLabel) ?></span></td>
        </tr>
      <?php endforeach; ?>
      <?php if (empty($modules)): ?><tr><td colspan="4" class="form-hint">لا توجد إضافات مكتشَفة.</td></tr><?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<div class="card-box">
  <h2 style="margin-top:0">سجل التحديثات</h2>
  <p class="form-hint">يُعرض هذا السجل مباشرة من ملف CHANGELOG.md في جذر المشروع، ويتحدّث تلقائيًا مع كل إصدار جديد.</p>
  <div class="markdown-body">
    <?= $changelogHtml ?>
  </div>
</div>
