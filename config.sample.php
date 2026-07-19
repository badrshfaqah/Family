<?php
/**
 * ملف الإعدادات الرئيسي للنظام.
 * يقوم معالج التثبيت بإنشاء نسخة باسم config.php تلقائيًا.
 * لا حاجة لتعديل هذا الملف يدويًا في الأحوال الطبيعية.
 */

// -- بيانات الاتصال بقاعدة البيانات --
define('DB_HOST', 'localhost');
define('DB_PORT', '3306');
define('DB_NAME', 'database_name');
define('DB_USER', 'database_user');
define('DB_PASS', 'database_password');
define('DB_PREFIX', 'fam_');
define('DB_CHARSET', 'utf8mb4');

// -- مفاتيح الأمان (يتم توليدها تلقائيًا أثناء التثبيت، لا تُشارك مع أحد) --
define('AUTH_KEY', 'CHANGE_ME');
define('SECURE_AUTH_KEY', 'CHANGE_ME');
define('LOGGED_IN_KEY', 'CHANGE_ME');

// -- وضع التطوير: يعرض تفاصيل الأخطاء. اتركه false في بيئة الإنتاج دائمًا --
define('APP_DEBUG', false);

// العنوان العام الموثوق للموقع، ويُستخدم في روابط البريد ومنع تسميم Host.
define('APP_URL', 'https://example.com');

// ضع النسخ خارج public_html، واحتفظ بالمفتاح في مدير أسرار منفصل أيضًا.
define('BACKUP_PATH', '/absolute/private/path/family-cms-backups');
define('BACKUP_ENCRYPTION_KEY', 'CHANGE_TO_A_RANDOM_64_CHARACTER_SECRET');

// الإضافات تنفذ PHP بصلاحيات التطبيق؛ اترك الرفع معطّلًا إلا مؤقتًا لحزمة موثوقة.
define('ALLOW_MODULE_UPLOAD', false);

// -- المنطقة الزمنية الافتراضية --
define('APP_TIMEZONE', 'Asia/Riyadh');
