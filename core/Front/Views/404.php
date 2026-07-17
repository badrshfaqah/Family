<?php

use Core\View;

$layout = CORE_PATH . '/Front/Views/layouts/main.php';
$body = <<<'HTML'
<div class="container">
  <div class="empty-state">
    <h1 style="font-size:2rem">404</h1>
    <p>عذرًا، الصفحة التي تبحث عنها غير موجودة أو تم نقلها.</p>
    <a class="btn btn-primary" href="%HOME%">العودة للصفحة الرئيسية</a>
  </div>
</div>
HTML;

$body = str_replace('%HOME%', \Core\Support\Url::to(''), $body);

$content = $body;
echo View::render($layout, ['content' => $content, 'pageTitle' => 'الصفحة غير موجودة']);
