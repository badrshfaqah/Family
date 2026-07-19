<?php
/**
 * فحص متطلبات السيرفر الأساسية. يُستدعى كملف يعيد مصفوفة النتائج.
 */

$phpOk = version_compare(PHP_VERSION, '8.1.0', '>=');

$requiredExtensions = [
    'pdo_mysql' => 'الاتصال بقاعدة بيانات MySQL',
    'mbstring' => 'معالجة النصوص العربية بصورة صحيحة',
    'gd' => 'معالجة الصور وإنشاء الأحجام المصغرة',
    'fileinfo' => 'التحقق من أنواع الملفات المرفوعة',
    'json' => 'تبادل البيانات',
    'session' => 'جلسات تسجيل الدخول',
    'dom' => 'تنقية HTML الآمنة ومنع XSS',
    'openssl' => 'تشفير مفاتيح المصادقة الثنائية',
];

$extensionChecks = [];
foreach ($requiredExtensions as $ext => $desc) {
    $extensionChecks[$ext] = ['ok' => extension_loaded($ext), 'label' => $desc];
}

$optionalExtensions = [
    'zip' => 'رفع الإضافات بصيغة ZIP والنسخ الاحتياطي للملفات',
    'sodium' => 'تشفير النسخ الاحتياطية بصورة تدفقية (يلزم فقط عند ضبط BACKUP_ENCRYPTION_KEY)',
];
$optionalChecks = [];
foreach ($optionalExtensions as $ext => $desc) {
    $optionalChecks[$ext] = ['ok' => extension_loaded($ext), 'label' => $desc];
}

$pathsToCheck = [
    ROOT_PATH => 'المجلد الرئيسي (لإنشاء ملف الإعدادات)',
    STORAGE_PATH => 'مجلد storage',
    STORAGE_PATH . '/uploads' => 'مجلد الرفع',
    STORAGE_PATH . '/cache' => 'مجلد التخزين المؤقت',
    STORAGE_PATH . '/logs' => 'مجلد السجلات',
    STORAGE_PATH . '/backups' => 'مجلد النسخ الاحتياطي',
];

$permissionChecks = [];
foreach ($pathsToCheck as $path => $label) {
    $permissionChecks[$path] = ['ok' => is_dir($path) && is_writable($path), 'label' => $label];
}

$requiredExtOk = !in_array(false, array_column($extensionChecks, 'ok'), true);
$permissionsOk = !in_array(false, array_column($permissionChecks, 'ok'), true);

return [
    'php_ok' => $phpOk,
    'php_version' => PHP_VERSION,
    'extensions' => $extensionChecks,
    'optional' => $optionalChecks,
    'permissions' => $permissionChecks,
    'all_ok' => $phpOk && $requiredExtOk && $permissionsOk,
];
