<?php
/** @var array $item
 *  @var array $related
 */
use Core\Media;
use Core\Support\Url;
use Core\Terms;
use Core\View;

function fam_news_cover($mediaId)
{
    if (!$mediaId) return '';
    $row = \Core\Database::fetchOne('SELECT stored_path FROM ' . \Core\Database::table('media') . ' WHERE id = ?', [$mediaId]);
    return $row ? Media::url($row['stored_path']) : '';
}

$cover = fam_news_cover($item['cover_media_id']);
$tags = array_filter(array_map('trim', explode(',', $item['tags'] ?? '')));

$jsonLd = [
    '@context' => 'https://schema.org',
    '@type' => 'NewsArticle',
    'headline' => $item['title'],
    'datePublished' => date('c', strtotime($item['published_at'])),
    'dateModified' => date('c', strtotime($item['updated_at'] ?? $item['published_at'])),
    'mainEntityOfPage' => Url::current(),
];
if ($cover) {
    $jsonLd['image'] = [Url::origin() . $cover];
}
if (!empty($item['author_name'])) {
    $jsonLd['author'] = ['@type' => 'Organization', 'name' => $item['author_name']];
}
if (!empty($item['excerpt'])) {
    $jsonLd['description'] = $item['excerpt'];
}
?>
<script type="application/ld+json" nonce="<?= \Core\Support\Security::cspNonce() ?>"><?= str_replace('</', '<\/', json_encode($jsonLd, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)) ?></script>
<div class="container section">
  <div class="breadcrumbs"><a href="<?= Url::to('') ?>">الرئيسية</a> / <a href="<?= Url::to('news') ?>"><?= View::e(Terms::phrase('news')) ?></a> / <?= View::e($item['title']) ?></div>

  <article class="card" style="padding:0;overflow:hidden">
    <?php if ($cover): ?><div class="card-img" style="aspect-ratio:16/8"><img src="<?= View::e($cover) ?>" alt=""></div><?php endif; ?>
    <div style="padding:22px">
      <?php if (!empty($item['category_name'])): ?><span class="card-meta"><?= View::e($item['category_name']) ?></span><?php endif; ?>
      <h1 style="margin:8px 0"><?= View::e($item['title']) ?></h1>
      <div class="card-meta" style="margin-bottom:16px">
        <span><?= View::e(date('Y/m/d', strtotime($item['published_at']))) ?></span>
        <?php if (!empty($item['author_name'])): ?><span><?= View::e($item['author_name']) ?></span><?php endif; ?>
      </div>

      <?php if (!empty($item['video_url'])): ?>
        <p><a href="<?= View::e($item['video_url']) ?>" target="_blank" rel="noopener">مشاهدة الفيديو المرفق ↗</a></p>
      <?php endif; ?>

      <div class="page-content"><?= $item['content'] ?></div>

      <?php if ($tags): ?>
        <div style="margin-top:18px;display:flex;gap:8px;flex-wrap:wrap">
          <?php foreach ($tags as $tag): ?><span class="badge badge-gray">#<?= View::e($tag) ?></span><?php endforeach; ?>
        </div>
      <?php endif; ?>
    </div>
  </article>

  <div style="display:flex;gap:10px;margin-top:16px;flex-wrap:wrap">
    <a class="btn btn-secondary" target="_blank" rel="noopener" href="https://wa.me/?text=<?= urlencode($item['title'] . ' ' . Url::current()) ?>">واتساب</a>
    <a class="btn btn-secondary" target="_blank" rel="noopener" href="https://twitter.com/intent/tweet?text=<?= urlencode($item['title']) ?>&url=<?= urlencode(Url::current()) ?>">X</a>
    <a class="btn btn-secondary" target="_blank" rel="noopener" href="https://t.me/share/url?url=<?= urlencode(Url::current()) ?>&text=<?= urlencode($item['title']) ?>">تيليجرام</a>
    <button class="btn btn-secondary" data-share-native data-url="<?= View::e(Url::current()) ?>" data-title="<?= View::e($item['title']) ?>">مشاركة</button>
    <button class="btn btn-secondary" data-copy-link data-url="<?= View::e(Url::current()) ?>">نسخ الرابط</button>
  </div>

  <?php if (!empty($related)): ?>
  <div class="section">
    <div class="section-head"><h2>أخبار ذات صلة</h2></div>
    <div class="grid grid-3">
      <?php foreach ($related as $r): ?>
        <a class="card" href="<?= Url::to('news/' . $r['slug']) ?>">
          <div class="card-body"><p class="card-title"><?= View::e($r['title']) ?></p></div>
        </a>
      <?php endforeach; ?>
    </div>
  </div>
  <?php endif; ?>
</div>
