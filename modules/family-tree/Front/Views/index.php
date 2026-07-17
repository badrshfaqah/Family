<?php
/** @var array $ordered          قائمة العقد الظاهرة مرتبة هرميًا (DFS) مع مفتاح depth
 *  @var array $publicBranchIds  معرّفات الفروع التي تملك صفحة عامة فعلية
 *  @var string $viewMode        tree أو sequential
 */
use Core\Support\Url;
use Core\Terms;
use Core\View;
?>
<div class="container section family-tree-page">
  <style>
    /* طباعة نظيفة: إخفاء عناصر الواجهة غير الضرورية عند الطباعة */
    @media print {
      .site-header, .site-footer, .mobile-nav, .family-tree-actions { display: none !important; }
      .family-tree-list li { break-inside: avoid; }
    }
    .family-tree-list { list-style: none; margin: 0; padding: 0; }
    .family-tree-list li {
      padding: 8px 10px;
      border-bottom: 1px solid var(--c-border, #e5e5e5);
    }
    .family-tree-list .node-name { font-weight: 600; }
    .family-tree-list .node-note { color: var(--c-muted, #777); font-size: .85rem; margin-inline-start: 6px; }
    .family-tree-list .seq-num { color: var(--c-muted, #777); margin-inline-end: 6px; }
  </style>

  <div class="breadcrumbs"><a href="<?= Url::to('') ?>">الرئيسية</a> / <?= View::e(Terms::phrase('tree')) ?></div>
  <h1><?= View::e(Terms::phrase('tree')) ?></h1>
  <p class="form-hint">تسلسل نسب مبسّط، دون أي بيانات شخصية حساسة (لا أرقام جوال ولا صور فردية ولا تواريخ ميلاد).</p>

  <div class="family-tree-actions" style="display:flex;gap:10px;margin:14px 0;flex-wrap:wrap">
    <a class="btn <?= $viewMode === 'tree' ? 'btn-primary' : 'btn-secondary' ?>" href="<?= Url::to('tree') ?>">عرض شجري</a>
    <a class="btn <?= $viewMode === 'sequential' ? 'btn-primary' : 'btn-secondary' ?>" href="<?= Url::to('tree?view=sequential') ?>">عرض تسلسلي مبسّط (يناسب الجوال)</a>
    <button class="btn btn-secondary" onclick="window.print()">طباعة</button>
    <!-- ملاحظة: التكبير التفاعلي على سطح المكتب وتصدير PDF مخطط لها كميزة مستقبلية، وليست ضمن النطاق الحالي. -->
  </div>

  <?php if (empty($ordered)): ?>
    <div class="empty-state">لا توجد عقد ظاهرة في شجرة النسب حاليًا.</div>
  <?php else: ?>
    <ul class="family-tree-list">
      <?php foreach ($ordered as $i => $n): ?>
        <li style="<?= $viewMode === 'tree' ? 'padding-inline-start:' . (10 + ((int) $n['depth'] * 22)) . 'px' : '' ?>">
          <?php if ($viewMode === 'sequential'): ?><span class="seq-num"><?= $i + 1 ?>.</span><?php endif; ?>
          <?php if ($viewMode === 'tree' && $n['depth'] > 0): ?><span aria-hidden="true">↳ </span><?php endif; ?>
          <?php if (!empty($n['branch_id']) && in_array((int) $n['branch_id'], $publicBranchIds, true)): ?>
            <a class="node-name" href="<?= Url::to('branches/' . (int) $n['branch_id']) ?>"><?= View::e($n['name']) ?></a>
          <?php else: ?>
            <span class="node-name"><?= View::e($n['name']) ?></span>
          <?php endif; ?>
          <?php if (!empty($n['note'])): ?><span class="node-note"><?= View::e($n['note']) ?></span><?php endif; ?>
        </li>
      <?php endforeach; ?>
    </ul>
  <?php endif; ?>
</div>
