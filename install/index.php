<?php

require_once __DIR__ . '/../core/bootstrap.php';

use Core\Database;
use Core\Support\Csrf;
use Core\Support\Request;
use Core\Support\Security;
use Core\Support\Session;
use Core\Support\Str;
use Core\Support\Url;

Session::start();

// إذا كان النظام مثبتًا مسبقًا، يتم تحويل الزائر مباشرة لصفحة الدخول
if (APP_INSTALLED) {
    header('Location: ' . Url::admin('login'));
    exit;
}

$steps = ['language', 'requirements', 'database', 'site', 'admin', 'finish'];
$state = $_SESSION['installer'] ?? ['completed' => []];

$requestedStep = $_GET['step'] ?? 'language';
if (!in_array($requestedStep, $steps, true)) {
    $requestedStep = 'language';
}

// منع القفز لخطوة لاحقة قبل إتمام ما قبلها
$stepIndex = array_search($requestedStep, $steps, true);
$maxAllowedIndex = count($state['completed']);
if ($stepIndex > $maxAllowedIndex) {
    $requestedStep = $steps[$maxAllowedIndex] ?? 'language';
    $stepIndex = $maxAllowedIndex;
}

$errors = [];
$success = null;

function installer_mark_done(string $step): void
{
    global $state;
    if (!in_array($step, $state['completed'], true)) {
        $state['completed'][] = $step;
    }
    $_SESSION['installer'] = $state;
}

function installer_next_url(string $currentStep, array $steps): string
{
    $i = array_search($currentStep, $steps, true);
    $next = $steps[$i + 1] ?? $steps[count($steps) - 1];
    return '?step=' . $next;
}

if (Request::isPost()) {
    Csrf::verifyRequestOrFail();

    switch ($requestedStep) {
        case 'language':
            $state['language'] = 'ar';
            installer_mark_done('language');
            header('Location: ' . installer_next_url('language', $steps));
            exit;

        case 'requirements':
            $checks = require __DIR__ . '/steps/requirements_check.php';
            if ($checks['all_ok']) {
                installer_mark_done('requirements');
                header('Location: ' . installer_next_url('requirements', $steps));
                exit;
            }
            $errors[] = 'يجب استيفاء جميع المتطلبات الأساسية قبل المتابعة.';
            break;

        case 'database':
            $dbHost = Request::trimmed('db_host', 'localhost');
            $dbPort = Request::trimmed('db_port', '3306');
            $dbName = Request::trimmed('db_name');
            $dbUser = Request::trimmed('db_user');
            $dbPass = (string) Request::post('db_pass', '');
            $dbPrefix = preg_replace('/[^a-zA-Z0-9_]/', '', Request::trimmed('db_prefix', 'fam_'));

            $test = Database::testConnection($dbHost, $dbPort, $dbName, $dbUser, $dbPass);
            if (!$test['ok']) {
                $errors[] = $test['message'];
            } else {
                $state['db'] = compact('dbHost', 'dbPort', 'dbName', 'dbUser', 'dbPass', 'dbPrefix');
                try {
                    Database::connect($dbHost, $dbPort, $dbName, $dbUser, $dbPass, 'utf8mb4', $dbPrefix);
                    $schema = file_get_contents(CORE_PATH . '/Database/schema/core.sql');
                    $schema = str_replace('{prefix}', $dbPrefix, $schema);
                    $statements = array_filter(array_map('trim', explode(';', $schema)));
                    foreach ($statements as $statement) {
                        Database::pdo()->exec($statement);
                    }
                    installer_mark_done('database');
                    header('Location: ' . installer_next_url('database', $steps));
                    exit;
                } catch (\Throwable $e) {
                    \Core\Support\Logger::exception($e);
                    $errors[] = 'تعذر إنشاء الجداول: ' . $e->getMessage();
                }
            }
            break;

        case 'site':
            $officialName = Security::cleanText(Request::trimmed('official_name'));
            $shortName = Security::cleanText(Request::trimmed('short_name'));
            $termMode = in_array(Request::post('term_mode'), ['family', 'tribe', 'league', 'diwaniyah', 'group', 'custom'], true)
                ? Request::post('term_mode') : 'family';
            $termCustom = Security::cleanText(Request::trimmed('term_custom'));

            if ($officialName === '') {
                $errors[] = 'الاسم الرسمي مطلوب.';
                break;
            }

            $state['site'] = compact('officialName', 'shortName', 'termMode', 'termCustom');
            installer_mark_done('site');
            header('Location: ' . installer_next_url('site', $steps));
            exit;

        case 'admin':
            $name = Security::cleanText(Request::trimmed('admin_name'));
            $username = preg_replace('/[^a-zA-Z0-9_\.]/', '', Request::trimmed('admin_username'));
            $email = filter_var(Request::trimmed('admin_email'), FILTER_VALIDATE_EMAIL) ?: '';
            $password = (string) Request::post('admin_password', '');
            $passwordConfirm = (string) Request::post('admin_password_confirm', '');

            if ($name === '') $errors[] = 'الاسم مطلوب.';
            if ($username === '') $errors[] = 'اسم المستخدم مطلوب.';
            if ($email === '') $errors[] = 'البريد الإلكتروني غير صحيح.';
            if (strlen($password) < 8) $errors[] = 'كلمة المرور يجب ألا تقل عن 8 أحرف.';
            if ($password !== $passwordConfirm) $errors[] = 'كلمتا المرور غير متطابقتين.';

            if (!$errors) {
                $state['admin'] = compact('name', 'username', 'email', 'password');
                installer_mark_done('admin');
                header('Location: ' . installer_next_url('admin', $steps));
                exit;
            }
            break;

        case 'finish':
            try {
                $db = $state['db'];
                Database::connect($db['dbHost'], $db['dbPort'], $db['dbName'], $db['dbUser'], $db['dbPass'], 'utf8mb4', $db['dbPrefix']);

                // ضمان عدم وجود أي ملف تخزين مؤقت قديم للإعدادات قد يتبقى من محاولة تثبيت سابقة
                \Core\Settings::clearCacheFile();

                \Core\Database\Seeds\DefaultSeeder::run([
                    'term_mode' => $state['site']['termMode'],
                    'term_custom' => $state['site']['termCustom'],
                    'official_name' => $state['site']['officialName'],
                    'short_name' => $state['site']['shortName'] ?: $state['site']['officialName'],
                ]);

                $adminRoleId = Database::fetchValue(
                    'SELECT id FROM ' . Database::table('roles') . ' WHERE slug = ?',
                    ['system_admin']
                );

                Database::insert('admin_users', [
                    'name' => $state['admin']['name'],
                    'username' => $state['admin']['username'],
                    'email' => $state['admin']['email'],
                    'password_hash' => Security::hashPassword($state['admin']['password']),
                    'role_id' => $adminRoleId,
                    'status' => 'active',
                    'created_at' => date('Y-m-d H:i:s'),
                ]);

                $configContent = installer_build_config($db);
                if (!file_put_contents(ROOT_PATH . '/config.php', $configContent)) {
                    throw new \RuntimeException('تعذر إنشاء ملف الإعدادات. تأكد من صلاحيات الكتابة على المجلد الرئيسي.');
                }

                \Core\Settings::clearCacheFile();
                file_put_contents(STORAGE_PATH . '/installed.lock', date('c'));

                unset($_SESSION['installer']);
                Session::regenerate();

                header('Location: ' . Url::admin('login') . '?installed=1');
                exit;
            } catch (\Throwable $e) {
                \Core\Support\Logger::exception($e);
                $errors[] = 'حدث خطأ أثناء إتمام التثبيت: ' . $e->getMessage();
            }
            break;
    }

    $_SESSION['installer'] = $state;
}

function installer_build_config(array $db): string
{
    $authKey = bin2hex(random_bytes(32));
    $secureKey = bin2hex(random_bytes(32));
    $loggedKey = bin2hex(random_bytes(32));
    $backupKey = bin2hex(random_bytes(32));
    $backupPath = dirname(ROOT_PATH) . '/family-cms-backups';

    $php = "<?php\n";
    $php .= "// تم إنشاء هذا الملف تلقائيًا بواسطة معالج التثبيت في " . date('Y-m-d H:i:s') . "\n\n";
    $php .= "define('DB_HOST', " . var_export($db['dbHost'], true) . ");\n";
    $php .= "define('DB_PORT', " . var_export($db['dbPort'], true) . ");\n";
    $php .= "define('DB_NAME', " . var_export($db['dbName'], true) . ");\n";
    $php .= "define('DB_USER', " . var_export($db['dbUser'], true) . ");\n";
    $php .= "define('DB_PASS', " . var_export($db['dbPass'], true) . ");\n";
    $php .= "define('DB_PREFIX', " . var_export($db['dbPrefix'], true) . ");\n";
    $php .= "define('DB_CHARSET', 'utf8mb4');\n\n";
    $php .= "define('AUTH_KEY', " . var_export($authKey, true) . ");\n";
    $php .= "define('SECURE_AUTH_KEY', " . var_export($secureKey, true) . ");\n";
    $php .= "define('LOGGED_IN_KEY', " . var_export($loggedKey, true) . ");\n\n";
    $php .= "define('APP_DEBUG', false);\n";
    $php .= "define('APP_URL', " . var_export(\Core\Support\Url::origin(), true) . ");\n";
    $php .= "define('BACKUP_PATH', " . var_export($backupPath, true) . ");\n";
    $php .= "define('BACKUP_ENCRYPTION_KEY', " . var_export($backupKey, true) . ");\n";
    $php .= "define('ALLOW_MODULE_UPLOAD', false);\n";
    $php .= "define('APP_TIMEZONE', 'Asia/Riyadh');\n";

    return $php;
}

$viewData = compact('requestedStep', 'steps', 'stepIndex', 'errors', 'state');
require __DIR__ . '/steps/layout.php';
