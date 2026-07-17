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

// -- المنطقة الزمنية الافتراضية --
define('APP_TIMEZONE', 'Asia/Riyadh');
