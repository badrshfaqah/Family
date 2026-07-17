<?php
/** @var string $q
 *  @var array $results
 */
use Core\Support\Url;

$typeLabels = ['news' => 'خبر', 'events' => 'مناسبة', 'pages' => 'صفحة', 'archive' => 'أرشيف', 'gallery' => 'ألبوم'];
$routeLabels = ['news' => 'news', 'events' => 'events', 'pages' => 'p', 'archive' => 'archive', 'gallery' => 'gallery'];
?>
<div class="container section">
  <form method="get" action="<?= Url::to('search') ?>" style="display:flex;gap:10px;margin-bottom:20px">
    <input class="form-control" type="search" name="q" value="<?= \Core\View::e($q) ?>" placeholder="ابحث في الموقع...">
    <button class="btn btn-primary" type="submit">بحث</button>
  </form>

  <?php if ($q !== '' && empty($results)): ?>
    <div class="empty-state"><p>لا توجد نتائج مطابقة لبحثك.</p></div>
  <?php endif; ?>

  <div class="grid">
    <?php foreach ($results as $r): ?>
      <a class="card" href="<?= Url::to(($routeLabels[$r['type']] ?? $r['type']) . '/' . $r['slug']) ?>">
        <div class="card-body">
          <span class="card-meta"><?= \Core\View::e($typeLabels[$r['type']] ?? $r['type']) ?></span>
          <p class="card-title"><?= \Core\View::e($r['title']) ?></p>
        </div>
      </a>
    <?php endforeach; ?>
  </div>
</div>
