<?php

declare(strict_types=1);

define('ROOT_PATH', dirname(__DIR__));
define('CORE_PATH', __DIR__);
define('MODULES_PATH', ROOT_PATH . '/modules');
define('STORAGE_PATH', ROOT_PATH . '/storage');

require_once CORE_PATH . '/Autoloader.php';
require_once CORE_PATH . '/version.php';
if (!Autoloader::isRegistered()) {
    Autoloader::register();
}

use Core\Auth;
use Core\Database;
use Core\Support\Logger;
use Core\Support\Security;
use Core\Support\Session;

$configFile = ROOT_PATH . '/config.php';
$installLock = STORAGE_PATH . '/installed.lock';

if (!is_file($configFile) || !is_file($installLock)) {
    // النظام غير مثبت بعد، يجب توجيه المستخدم لمعالج التثبيت من نقاط الدخول (index.php / admin)
    define('APP_INSTALLED', false);
} else {
    require $configFile;
    define('APP_INSTALLED', true);

    date_default_timezone_set(defined('APP_TIMEZONE') ? APP_TIMEZONE : 'Asia/Riyadh');

    error_reporting(E_ALL);
    ini_set('display_errors', APP_DEBUG ? '1' : '0');
    ini_set('log_errors', '1');

    set_error_handler(function ($severity, $message, $file, $line) {
        if (!(error_reporting() & $severity)) {
            return false;
        }
        Logger::error($message, ['file' => $file, 'line' => $line]);
        return true;
    });

    set_exception_handler(function (\Throwable $e) {
        Logger::exception($e);
        http_response_code(500);
        if (APP_DEBUG) {
            echo '<pre style="direction:ltr;text-align:left;padding:20px;background:#fff3f3;color:#900">';
            echo Security::e($e->getMessage()) . "\n" . Security::e($e->getTraceAsString());
            echo '</pre>';
        } else {
            echo 'حدث خطأ غير متوقع. الرجاء المحاولة لاحقًا.';
        }
    });

    try {
        Database::connect(DB_HOST, DB_PORT, DB_NAME, DB_USER, DB_PASS, DB_CHARSET, DB_PREFIX);
    } catch (\Throwable $e) {
        Logger::exception($e);
        http_response_code(500);
        echo 'تعذر الاتصال بقاعدة البيانات. الرجاء إبلاغ إدارة الموقع.';
        exit;
    }

    Session::start();
    Security::sendSecurityHeaders();

    // المزامنة التلقائية بعد أي تحديث للملفات (سحب من GitHub أو رفع يدوي):
    // ترحيلات النواة + ترحيل الإضافات المثبتة لإصداراتها الجديدة + تثبيت
    // وتفعيل الإضافات المرفقة الجديدة (حسب الإعداد). تعمل مرة واحدة فقط
    // بعد كل تغيير فعلي في إصدار النواة أو أي إضافة — انظر Core\SystemSync.
    try {
        \Core\SystemSync::autoRun();
    } catch (\Throwable $e) {
        Logger::exception($e);
    }
}
