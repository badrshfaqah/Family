<?php
/** @var array $poem */
use Core\Support\Url;
use Core\View;
?>
<div class="container section">
  <div class="breadcrumbs"><a href="<?= Url::to('') ?>">الرئيسية</a> / <a href="<?= Url::to('poetry') ?>">سماء الشعراء</a> / <a href="<?= Url::to('poetry/' . $poem['poet_ref']) ?>"><?= View::e($poem['poet_name']) ?></a> / <?= View::e($poem['title']) ?></div>

  <article class="poem-page">
    <header class="poem-head">
      <span class="poem-mark" style="font-size:1.8rem">🪶</span>
      <h1><?= View::e($poem['title']) ?></h1>
      <a class="poem-poet" href="<?= Url::to('poetry/' . $poem['poet_ref']) ?>"><?= View::e($poem['poet_name']) ?></a>
      <?php if (!empty($poem['occasion'])): ?><span class="poem-occasion"><?= View::e($poem['occasion']) ?></span><?php endif; ?>
    </header>

    <div class="ornament" aria-hidden="true">❖ ✦ ❖</div>

    <div class="poem-body"><?= nl2br(View::e($poem['content'])) ?></div>

    <div class="ornament" aria-hidden="true">❖ ✦ ❖</div>

    <footer class="poem-foot">
      <a class="btn btn-outline-dark btn-sm" href="<?= Url::to('poetry/' . $poem['poet_ref']) ?>">← بقية قصائد <?= View::e($poem['poet_name']) ?></a>
      <button class="btn btn-primary btn-sm" data-share-native data-title="<?= View::e($poem['title'] . ' — ' . $poem['poet_name']) ?>">مشاركة القصيدة 📤</button>
    </footer>
  </article>
</div>
