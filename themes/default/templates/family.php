<?php
/** @var string $coreVersion
 *  @var array $modules
 *  @var array $installedMap
 *  @var string $changelogHtml
 */
use Core\View;

$statusLabels = [
    'enabled' => ['مفعّلة', 'family-badge-on'],
    'disabled' => ['معطّلة', 'family-badge-off'],
];
?>
<div class="container section">

  <div class="card" style="margin-bottom:16px">
    <div class="card-body">
      <h1 style="margin:0 0 10px;font-size:1.4rem">نظام إدارة محتوى العوائل والقبائل</h1>
      <p style="color:var(--c-muted);line-height:1.8">
        نظام متكامل لإدارة الموقع الرسمي للعائلة أو القبيلة: هوية وتخصيص كامل، لوحة تحكم عربية،
        ومجموعة إضافات جاهزة للأخبار والمناسبات والجمعات وجوال العائلة وغيرها — دون كشف أي بيانات
        شخصية للزوار العامّين.
      </p>
      <p style="margin:10px 0 0"><strong>إصدار النواة الحالي:</strong> <?= View::e($coreVersion) ?></p>
    </div>
  </div>

  <div class="card" style="margin-bottom:16px">
    <div class="card-body">
      <h2 style="margin:0 0 6px;font-size:1.15rem">الإضافات والمميزات</h2>
      <p style="color:var(--c-muted);font-size:.85rem;margin:0 0 10px">هذه القائمة تُبنى مباشرة من الإضافات المثبّتة فعليًا، وتتحدث تلقائيًا عند إضافة أي مميزة جديدة.</p>
      <?php foreach ($modules as $slug => $module): ?>
        <?php
          $row = $installedMap[$slug] ?? null;
          $status = $row['status'] ?? null;
          [$statusLabel, $statusClass] = $statusLabels[$status] ?? ['غير مفعّلة', 'family-badge-off'];
        ?>
        <div class="family-module-row">
          <div>
            <p class="family-module-name"><?= View::e($module['name'] ?? $slug) ?></p>
            <p class="family-module-desc"><?= View::e($module['description'] ?? '') ?></p>
          </div>
          <div style="text-align:left;flex-shrink:0">
            <div class="family-module-version"><?= View::e($module['version'] ?? '') ?></div>
            <span class="family-badge <?= $statusClass ?>"><?= View::e($statusLabel) ?></span>
          </div>
        </div>
      <?php endforeach; ?>
      <?php if (empty($modules)): ?><p style="color:var(--c-muted)">لا توجد إضافات مكتشَفة.</p><?php endif; ?>
    </div>
  </div>

  <div class="card">
    <div class="card-body">
      <h2 style="margin:0 0 6px;font-size:1.15rem">سجل التحديثات</h2>
      <p style="color:var(--c-muted);font-size:.85rem;margin:0 0 10px">يُعرض هذا السجل مباشرة من ملف التحديثات في المشروع، ويتحدّث تلقائيًا مع كل إصدار جديد.</p>
      <div class="family-changelog">
        <?= $changelogHtml ?>
      </div>
    </div>
  </div>

</div>
