<?php
/** @var array $ordered          قائمة العقد الظاهرة مرتبة هرميًا (DFS) مع مفتاح depth
 *  @var array $publicBranchIds  معرّفات الفروع التي تملك صفحة عامة فعلية
 *  @var string $viewMode        tree أو sequential
 */
use Core\Media;
use Core\Settings;
use Core\Support\Url;
use Core\Terms;
use Core\View;

// صورة جاهزة مرفوعة من لوحة التحكم تُعرض بدل الخريطة التفاعلية
$treeImageUrl = '';
$treeImageId = Settings::get('tree_image_media_id', '');
if ($treeImageId) {
    $media = \Core\Database::fetchOne('SELECT stored_path FROM ' . \Core\Database::table('media') . ' WHERE id = ?', [$treeImageId]);
    if ($media) {
        $treeImageUrl = Media::url($media['stored_path']);
    }
}

// بناء هيكل الأبناء من القائمة المسطحة لرسم الخريطة الذهنية
$visibleSet = [];
foreach ($ordered as $n) {
    $visibleSet[(int) $n['id']] = true;
}
$byParent = [];
foreach ($ordered as $n) {
    $pid = (int) ($n['parent_id'] ?? 0);
    $key = ($pid && isset($visibleSet[$pid])) ? $pid : 0;
    $byParent[$key][] = $n;
}

$renderNode = function (array $n, bool $isRoot) use (&$renderNode, $byParent, $publicBranchIds) {
    echo '<li>';
    $name = View::e($n['name']);
    $cls = 'tree-node' . ($isRoot ? ' tree-root' : '');
    if (!empty($n['branch_id']) && in_array((int) $n['branch_id'], $publicBranchIds, true)) {
        echo '<a class="' . $cls . '" href="' . Url::to('branches/' . (int) $n['branch_id']) . '">' . $name . '</a>';
    } else {
        echo '<span class="' . $cls . '">' . $name . '</span>';
    }
    $kids = $byParent[(int) $n['id']] ?? [];
    if ($kids) {
        echo '<ul>';
        foreach ($kids as $k) {
            $renderNode($k, false);
        }
        echo '</ul>';
    }
    echo '</li>';
};
?>
<div class="container section family-tree-page">
  <style>
    @media print {
      .site-header, .site-footer, .mobile-nav, .bottom-nav, .sadu-strip, .family-tree-actions { display: none !important; }
      .family-tree-list li { break-inside: avoid; }
    }
    .family-tree-list { list-style: none; margin: 0; padding: 0; }
    .family-tree-list li { padding: 8px 10px; border-bottom: 1px solid var(--c-border, #e5e5e5); }
    .family-tree-list .node-name { font-weight: 600; }
    .family-tree-list .node-note { color: var(--c-muted, #777); font-size: .85rem; margin-inline-start: 6px; }
    .family-tree-list .seq-num { color: var(--c-muted, #777); margin-inline-end: 6px; }
  </style>

  <div class="breadcrumbs"><a href="<?= Url::to('') ?>">الرئيسية</a> / <?= View::e(Terms::phrase('tree')) ?></div>
  <h1>🌳 <?= View::e(Terms::phrase('tree')) ?></h1>

  <div class="family-tree-actions" style="display:flex;gap:10px;margin:14px 0;flex-wrap:wrap">
    <a class="btn btn-sm <?= $viewMode === 'tree' ? 'btn-primary' : 'btn-outline-dark' ?>" href="<?= Url::to('tree') ?>"><?= $treeImageUrl ? 'الصورة' : 'الخريطة' ?></a>
    <a class="btn btn-sm <?= $viewMode === 'sequential' ? 'btn-primary' : 'btn-outline-dark' ?>" href="<?= Url::to('tree?view=sequential') ?>">عرض تسلسلي</a>
    <button class="btn btn-sm btn-outline-dark" data-print-page>طباعة</button>
  </div>

  <?php if ($viewMode === 'tree' && $treeImageUrl): ?>
    <div class="tree-image"><img src="<?= View::e($treeImageUrl) ?>" alt="<?= View::e(Terms::phrase('tree')) ?>"></div>
    <p class="form-hint" style="margin-top:8px">💡 كبّر الصورة بإصبعيك لاستعراض التفاصيل.</p>

  <?php elseif ($viewMode === 'tree'): ?>
    <?php if (empty($byParent[0])): ?>
      <div class="empty-state">لا توجد عقد ظاهرة في شجرة النسب حاليًا.</div>
    <?php else: ?>
      <div class="tree-chart-wrap">
        <div class="tree-chart">
          <ul>
            <?php foreach ($byParent[0] as $root) { $renderNode($root, true); } ?>
          </ul>
        </div>
      </div>
      <p class="form-hint" style="margin-top:4px">💡 اسحب يمينًا ويسارًا لاستعراض فروع الخريطة كاملة.</p>
    <?php endif; ?>

  <?php else: ?>
    <?php if (empty($ordered)): ?>
      <div class="empty-state">لا توجد عقد ظاهرة في شجرة النسب حاليًا.</div>
    <?php else: ?>
      <ul class="family-tree-list">
        <?php foreach ($ordered as $i => $n): ?>
          <li>
            <span class="seq-num"><?= $i + 1 ?>.</span>
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
  <?php endif; ?>
</div>
