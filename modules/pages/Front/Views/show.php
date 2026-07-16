<?php
/** @var array $page */
use Core\Support\Url;
use Core\View;
?>
<div class="container section">
  <div class="breadcrumbs"><a href="<?= Url::to('') ?>">الرئيسية</a> / <?= View::e($page['title']) ?></div>
  <article class="card" style="padding:24px">
    <h1 style="margin-top:0"><?= View::e($page['title']) ?></h1>
    <div class="page-content"><?= $page['content'] ?></div>
  </article>

  <div style="display:flex;gap:10px;margin-top:16px">
    <button class="btn btn-secondary" data-share-native data-url="<?= View::e(Url::current()) ?>" data-title="<?= View::e($page['title']) ?>">مشاركة</button>
    <button class="btn btn-secondary" data-copy-link data-url="<?= View::e(Url::current()) ?>">نسخ الرابط</button>
  </div>
</div>
