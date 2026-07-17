<?php
/** @var string $requestedStep
 *  @var array $steps
 *  @var int $stepIndex
 *  @var array $errors
 *  @var array $state
 */
use Core\Support\Csrf;
use Core\Support\Security;
use Core\Support\Url;

$stepLabels = [
    'language' => 'اللغة', 'requirements' => 'فحص المتطلبات', 'database' => 'قاعدة البيانات',
    'site' => 'بيانات الموقع', 'admin' => 'حساب المدير', 'finish' => 'الإنهاء',
];
?><!doctype html>
<html lang="ar" dir="rtl">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>معالج التثبيت</title>
<meta name="robots" content="noindex, nofollow">
<link rel="stylesheet" href="<?= Url::to('admin/assets/css/admin.css') ?>">
<style>
body{background:#0f231d}
.installer-wrap{max-width:640px;margin:0 auto;padding:24px 16px 60px}
.installer-card{background:#fff;border-radius:16px;padding:26px;margin-top:18px}
.steps-bar{display:flex;gap:4px;padding:20px 0 0}
.steps-bar div{flex:1;height:4px;border-radius:4px;background:rgba(255,255,255,.18)}
.steps-bar div.done{background:#c9a24b}
.steps-bar div.current{background:#fff}
.installer-title{color:#fff;text-align:center;padding-top:10px}
.check-row{display:flex;justify-content:space-between;align-items:center;padding:9px 0;border-bottom:1px solid var(--a-border);font-size:.9rem}
.check-ok{color:#1c6e3c;font-weight:700}
.check-fail{color:#a12d24;font-weight:700}
</style>
</head>
<body>
<div class="installer-wrap">
  <div class="installer-title">
    <h1 style="font-size:1.3rem;margin-bottom:4px">معالج تثبيت النظام</h1>
    <p style="opacity:.8;font-size:.85rem">خطوة <?= $stepIndex + 1 ?> من <?= count($steps) ?>: <?= Security::e($stepLabels[$requestedStep] ?? '') ?></p>
  </div>
  <div class="steps-bar">
    <?php foreach ($steps as $i => $s): ?>
      <div class="<?= $i < $stepIndex ? 'done' : ($i === $stepIndex ? 'current' : '') ?>"></div>
    <?php endforeach; ?>
  </div>

  <div class="installer-card">
    <?php if (!empty($errors)): ?>
      <?php foreach ($errors as $e): ?><div class="alert alert-error"><?= Security::e($e) ?></div><?php endforeach; ?>
    <?php endif; ?>

    <?php require __DIR__ . '/' . $requestedStep . '.php'; ?>
  </div>
</div>
</body>
</html>
