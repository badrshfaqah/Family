<?php

declare(strict_types=1);

$root = dirname(__DIR__);
define('ROOT_PATH', $root);
define('CORE_PATH', $root . '/core');
define('STORAGE_PATH', sys_get_temp_dir() . '/hntish-security-storage-' . bin2hex(random_bytes(4)));
define('AUTH_KEY', 'test-auth-key');
define('SECURE_AUTH_KEY', 'test-secure-key');
define('BACKUP_PATH', STORAGE_PATH . '/backups');
define('BACKUP_ENCRYPTION_KEY', bin2hex(random_bytes(32)));

require CORE_PATH . '/Database.php';
require CORE_PATH . '/Support/Security.php';
require CORE_PATH . '/Support/Csv.php';
require CORE_PATH . '/Support/Url.php';
require CORE_PATH . '/TwoFactor.php';
require CORE_PATH . '/Media.php';
require CORE_PATH . '/Backup.php';
require CORE_PATH . '/Push.php';
require CORE_PATH . '/Admin/Controllers/ModulesController.php';

$failures = [];
$check = static function (bool $condition, string $message) use (&$failures): void {
    if (!$condition) {
        $failures[] = $message;
    }
};

$payloads = [
    '<iframe srcdoc=&#60;script&#62;alert(1)&#60;/script&#62;>',
    '<svg><script>alert(1)</script></svg>',
    '<a href="java&#9;script:alert(1)">x</a>',
    '<img src=x onerror=alert(1)>',
];
foreach ($payloads as $payload) {
    $safe = Core\Support\Security::cleanHtml($payload);
    $check(!preg_match('/(?:script|iframe|javascript|onerror)/i', $safe), 'XSS payload survived sanitization');
}
$check(Core\Support\Security::cspNonce() === Core\Support\Security::cspNonce(), 'CSP nonce changed during request');
$check(Core\Support\Csv::safeCell(' =1+1') === "' =1+1", 'CSV formula was not neutralized');
$check(Core\Support\Csv::safeCell('normal') === 'normal', 'Normal CSV cell was changed');

$check(Core\TwoFactor::verifyTotp('GEZDGNBVGY3TQOJQGEZDGNBVGY3TQOJQ', '287082', 59), 'RFC TOTP vector failed');

$_SERVER['HTTPS'] = 'on';
$_SERVER['SERVER_NAME'] = 'family.example';
$_SERVER['SERVER_PORT'] = 443;
$_SERVER['HTTP_HOST'] = 'evil.example';
$_SERVER['REQUEST_URI'] = '/admin/reset';
$check(Core\Support\Url::origin() === 'https://family.example', 'Host header influenced canonical origin');

$fakeImage = sys_get_temp_dir() . '/hntish-fake-' . bin2hex(random_bytes(4)) . '.webp';
file_put_contents($fakeImage, 'not an image');
$mimeMethod = new ReflectionMethod(Core\Media::class, 'verifyRealType');
$check($mimeMethod->invoke(null, $fakeImage, 'webp') === false, 'Fake WebP passed MIME validation');
@unlink($fakeImage);

$sqlMethod = new ReflectionMethod(Core\Backup::class, 'isAllowedRestoreStatement');
$check($sqlMethod->invoke(null, 'INSERT INTO `safe_table` (`id`) VALUES (1)') === true, 'Expected restore SQL was rejected');
$check($sqlMethod->invoke(null, 'DROP DATABASE production') === false, 'Dangerous restore SQL was accepted');

$check(!Core\Push::isSafeEndpoint('https://127.0.0.1/push'), 'Loopback push endpoint accepted');
$check(!Core\Push::isSafeEndpoint('https://169.254.169.254/latest/meta-data'), 'Metadata endpoint accepted');

if (class_exists('ZipArchive')) {
    $zipPath = sys_get_temp_dir() . '/hntish-zip-' . bin2hex(random_bytes(4)) . '.zip';
    $zip = new ZipArchive();
    $zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE);
    $zip->addFromString('../escape.php', 'x');
    $zip->close();
    $zip->open($zipPath);
    $controller = new Core\Admin\Controllers\ModulesController();
    $method = new ReflectionMethod($controller, 'validateArchive');
    $rejected = false;
    try { $method->invoke($controller, $zip); } catch (RuntimeException $e) { $rejected = true; }
    $check($rejected, 'ZIP traversal entry was accepted');
    $zip->close();
    @unlink($zipPath);
}

if (function_exists('sodium_crypto_secretstream_xchacha20poly1305_init_push')) {
    @mkdir(STORAGE_PATH . '/temp', 0700, true);
    @mkdir(BACKUP_PATH, 0700, true);
    $plainPath = BACKUP_PATH . '/backup.sql';
    $decryptedPath = STORAGE_PATH . '/decrypted.sql';
    $content = str_repeat('secure-backup-data', 70000);
    file_put_contents($plainPath, $content);
    $encrypt = new ReflectionMethod(Core\Backup::class, 'encryptBackup');
    $encryptedName = $encrypt->invoke(null, $plainPath, 'D');
    $encryptedPath = BACKUP_PATH . '/' . $encryptedName;
    $decrypt = new ReflectionMethod(Core\Backup::class, 'decryptBackup');
    $decrypt->invoke(null, $encryptedPath, $decryptedPath, 'D');
    $check(file_get_contents($decryptedPath) === $content, 'Encrypted backup round-trip failed');
    @unlink($encryptedPath); @unlink($decryptedPath);
}

$iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS));
foreach ($iterator as $file) {
    if ($file->getExtension() !== 'php' || str_contains($file->getPathname(), '/tests/')) continue;
    $source = file_get_contents($file->getPathname());
    $check(!preg_match('/\son[a-z]+\s*=/i', $source), 'Inline event handler found: ' . $file->getPathname());
    $check(!preg_match('/<script(?![^>]*(?:src=|nonce=))/i', $source), 'Inline script lacks nonce: ' . $file->getPathname());
}

if ($failures) {
    foreach ($failures as $failure) fwrite(STDERR, "FAIL: {$failure}\n");
    exit(1);
}
echo "Security regressions passed.\n";

$cleanup = static function (string $dir) use (&$cleanup): void {
    if (!is_dir($dir)) return;
    foreach (array_diff(scandir($dir) ?: [], ['.', '..']) as $item) {
        $path = $dir . '/' . $item;
        is_dir($path) ? $cleanup($path) : @unlink($path);
    }
    @rmdir($dir);
};
$cleanup(STORAGE_PATH);
