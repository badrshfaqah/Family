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

  <div style="display:flex;gap:10px;margin-top:16px;flex-wrap:wrap">
    <a class="btn btn-secondary" target="_blank" rel="noopener" href="https://wa.me/?text=<?= urlencode($page['title'] . ' ' . Url::current()) ?>">واتساب</a>
    <a class="btn btn-secondary" target="_blank" rel="noopener" href="https://twitter.com/intent/tweet?text=<?= urlencode($page['title']) ?>&url=<?= urlencode(Url::current()) ?>">X</a>
    <a class="btn btn-secondary" target="_blank" rel="noopener" href="https://t.me/share/url?url=<?= urlencode(Url::current()) ?>&text=<?= urlencode($page['title']) ?>">تيليجرام</a>
    <button class="btn btn-secondary" data-share-native data-url="<?= View::e(Url::current()) ?>" data-title="<?= View::e($page['title']) ?>">مشاركة</button>
    <button class="btn btn-secondary" data-copy-link data-url="<?= View::e(Url::current()) ?>">نسخ الرابط</button>
  </div>
</div>
