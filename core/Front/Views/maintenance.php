<?php
use Core\Settings;
use Core\Support\Security;
?><!doctype html>
<html lang="ar" dir="rtl">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>الموقع تحت الصيانة</title>
<style>
body{font-family:'Tajawal',Tahoma,Arial,sans-serif;background:#0f6e5e;color:#fff;display:flex;min-height:100vh;align-items:center;justify-content:center;margin:0;padding:20px;text-align:center}
.box{max-width:420px}
h1{font-size:1.6rem}
</style>
</head>
<body>
<div class="box">
  <h1>🛠️ الموقع تحت الصيانة</h1>
  <p><?= Security::e(Settings::get('maintenance_message', 'سنعود قريبًا بإذن الله.')) ?></p>
</div>
</body>
</html>
