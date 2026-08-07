<?php

namespace Core\Admin\Controllers;

use Core\ActivityLog;
use Core\Auth;
use Core\Settings;
use Core\Support\Csrf;
use Core\Support\Request;
use Core\Support\Response;
use Core\Support\Session;
use Core\Support\Url;
use Core\View;

/**
 * التحديث الذاتي من GitHub: يجلب أحدث نسخة من المستودع ويستبدل ملفات النظام
 * مع الحفاظ على config.php ومجلد storage. خاص بمدير النظام حصريًا.
 */
final class UpdatesController
{
    /** المسارات التي لا تُلمس أبدًا أثناء التحديث */
    private const PRESERVE = ['config.php', 'storage', '.git', '.claude'];

    public function index(array $params): void
    {
        $this->requireSystemAdmin();

        $repo = (string) Settings::get('update_github_repo', '');
        $latest = null;
        $error = '';

        if ($repo !== '' && $this->validRepo($repo)) {
            try {
                $latest = $this->fetchLatestInfo($repo);
            } catch (\Throwable $e) {
                $error = $e->getMessage();
            }
        }

        echo View::render(CORE_PATH . '/Admin/Views/layouts/admin.php', [
            'pageTitle' => 'تحديث النظام',
            'contentView' => CORE_PATH . '/Admin/Views/updates/index.php',
            'repo' => $repo,
            'hasToken' => Settings::get('update_github_token', '') !== '',
            'currentVersion' => defined('CORE_VERSION') ? CORE_VERSION : '؟',
            'latest' => $latest,
            'checkError' => $error,
            'zipAvailable' => class_exists('ZipArchive'),
        ]);
    }

    public function saveRepo(array $params): void
    {
        $this->requireSystemAdmin();
        Csrf::verifyRequestOrFail();

        $repo = trim((string) Request::post('repo', ''));
        $repo = preg_replace('~^https?://github\.com/~i', '', $repo);
        $repo = trim((string) $repo, '/');

        if ($repo !== '' && !$this->validRepo($repo)) {
            Session::flash('error', 'صيغة المستودع غير صحيحة — المطلوب: owner/repo');
            Response::redirect(Url::admin('updates'));
        }

        Settings::set('update_github_repo', $repo);

        // مفتاح الوصول للمستودعات الخاصة: يُحفظ إن أُدخل، ويُمسح بالخيار الصريح فقط
        if (Request::post('remove_token')) {
            Settings::set('update_github_token', '');
        } else {
            $token = trim((string) Request::post('token', ''));
            if ($token !== '' && preg_match('/^[\w.\-]{20,255}$/', $token)) {
                Settings::set('update_github_token', $token);
            }
        }

        Session::flash('success', $repo === '' ? 'تم مسح إعداد المستودع.' : 'تم حفظ إعدادات المستودع: ' . $repo);
        Response::redirect(Url::admin('updates'));
    }

    public function run(array $params): void
    {
        $this->requireSystemAdmin();
        Csrf::verifyRequestOrFail();
        @set_time_limit(300);

        $repo = (string) Settings::get('update_github_repo', '');
        if ($repo === '' || !$this->validRepo($repo)) {
            Session::flash('error', 'اضبط مستودع GitHub أولًا.');
            Response::redirect(Url::admin('updates'));
        }
        if (!class_exists('ZipArchive')) {
            Session::flash('error', 'امتداد zip غير متوفر على الخادم — لا يمكن فك حزمة التحديث.');
            Response::redirect(Url::admin('updates'));
        }

        $oldVersion = defined('CORE_VERSION') ? CORE_VERSION : '؟';

        try {
            $info = $this->fetchLatestInfo($repo);
            $zipPath = $this->download($info['zip_url']);
            $extracted = $this->extract($zipPath);
            $this->applyUpdate($extracted);
        } catch (\Throwable $e) {
            Session::flash('error', 'فشل التحديث: ' . $e->getMessage());
            Response::redirect(Url::admin('updates'));
        }

        // تطبيق ترحيلات قاعدة البيانات المرافقة للنسخة الجديدة (الملفات استُبدلت للتو)
        $migrations = [];
        try {
            $migrations = \Core\Database\Migrator::migrate();
        } catch (\Throwable $e) {
            Session::flash('error', 'حُدثت الملفات لكن تعذر تطبيق ترحيلات قاعدة البيانات: ' . $e->getMessage());
            Response::redirect(Url::admin('updates'));
        }

        // قراءة الإصدار الجديد من الملف مباشرة (الثابت القديم محمّل في الذاكرة)
        $newVersion = $oldVersion;
        if (preg_match("/CORE_VERSION',\s*'([^']+)'/", (string) file_get_contents(ROOT_PATH . '/core/version.php'), $m)) {
            $newVersion = $m[1];
        }

        Settings::clearCacheFile();
        $migrationsNote = $migrations ? ('، وطُبق ' . count($migrations) . ' ترحيل على قاعدة البيانات') : '';
        ActivityLog::record('system_update', "تحديث النظام من GitHub: {$oldVersion} ← {$newVersion} ({$info['label']}){$migrationsNote}");
        Session::flash('success', "تم التحديث بنجاح: {$oldVersion} ← {$newVersion}{$migrationsNote}.");
        Response::redirect(Url::admin('updates'));
    }

    /* ─── خطوات التحديث ─────────────────────────── */

    /** @return array{label:string, zip_url:string, version:string} أحدث إصدار أو الفرع الرئيسي */
    private function fetchLatestInfo(string $repo): array
    {
        // أولًا: أحدث Release منشور
        $release = $this->githubJson("https://api.github.com/repos/{$repo}/releases/latest");
        if ($release && !empty($release['tag_name'])) {
            return [
                'label' => 'إصدار ' . $release['tag_name'],
                'version' => ltrim((string) $release['tag_name'], 'v'),
                'zip_url' => "https://api.github.com/repos/{$repo}/zipball/" . rawurlencode($release['tag_name']),
            ];
        }

        // لا توجد إصدارات منشورة: أحدث حالة للفرع الافتراضي
        $repoInfo = $this->githubJson("https://api.github.com/repos/{$repo}");
        if (!$repoInfo || empty($repoInfo['default_branch'])) {
            throw new \RuntimeException('تعذر الوصول للمستودع — تأكد من الاسم وأنه عام (Public).');
        }
        $branch = (string) $repoInfo['default_branch'];

        return [
            'label' => 'أحدث نسخة من فرع ' . $branch,
            'version' => 'فرع ' . $branch,
            'zip_url' => "https://api.github.com/repos/{$repo}/zipball/" . rawurlencode($branch),
        ];
    }

    private function githubJson(string $url): ?array
    {
        $body = $this->httpGet($url, 20);
        if ($body === null) {
            return null;
        }
        $data = json_decode($body, true);
        return is_array($data) ? $data : null;
    }

    private function download(string $url): string
    {
        $dir = STORAGE_PATH . '/temp';
        if (!is_dir($dir)) {
            @mkdir($dir, 0755, true);
        }
        $zipPath = $dir . '/update-' . date('YmdHis') . '.zip';

        $body = $this->httpGet($url, 120);
        if ($body === null || strlen($body) < 1000) {
            throw new \RuntimeException('فشل تنزيل حزمة التحديث من GitHub.');
        }
        if (strlen($body) > 50 * 1024 * 1024) {
            throw new \RuntimeException('حزمة التحديث أكبر من الحد المسموح (50MB).');
        }
        file_put_contents($zipPath, $body);

        return $zipPath;
    }

    /** يفك الحزمة ويعيد مسار مجلد جذر النظام داخلها */
    private function extract(string $zipPath): string
    {
        $target = STORAGE_PATH . '/temp/update-extract-' . date('YmdHis');
        $zip = new \ZipArchive();
        if ($zip->open($zipPath) !== true) {
            throw new \RuntimeException('تعذر فتح حزمة التحديث.');
        }
        if ($zip->numFiles > 5000) {
            $zip->close();
            throw new \RuntimeException('حزمة التحديث تحتوي ملفات أكثر من المتوقع.');
        }
        // فحص ضد تجاوز المسارات
        for ($i = 0; $i < $zip->numFiles; $i++) {
            $name = (string) $zip->getNameIndex($i);
            if (str_contains($name, '..') || str_starts_with($name, '/')) {
                $zip->close();
                throw new \RuntimeException('الحزمة تحتوي مسارات غير آمنة.');
            }
        }
        @mkdir($target, 0755, true);
        $zip->extractTo($target);
        $zip->close();
        @unlink($zipPath);

        // zipball يغلف الملفات بمجلد واحد باسم المستودع والـ commit
        $entries = array_values(array_filter((array) scandir($target), fn($e) => $e !== '.' && $e !== '..'));
        $root = count($entries) === 1 && is_dir($target . '/' . $entries[0]) ? $target . '/' . $entries[0] : $target;

        if (!is_file($root . '/core/version.php') || !is_file($root . '/index.php')) {
            $this->removeDir($target);
            throw new \RuntimeException('الحزمة لا تبدو نسخة صالحة من البرنامج (لا تحتوي core/version.php).');
        }

        return $root;
    }

    /** نسخ ملفات النسخة الجديدة فوق التركيبة مع الحفاظ على الإعدادات والرفع */
    private function applyUpdate(string $sourceRoot): void
    {
        $this->copyDir($sourceRoot, ROOT_PATH);
        // تنظيف مجلد الاستخراج المؤقت
        $this->removeDir(dirname($sourceRoot) === STORAGE_PATH . '/temp' ? $sourceRoot : dirname($sourceRoot));
    }

    private function copyDir(string $from, string $to): void
    {
        foreach ((array) scandir($from) as $entry) {
            if ($entry === '.' || $entry === '..') {
                continue;
            }
            if ($to === ROOT_PATH && in_array($entry, self::PRESERVE, true)) {
                continue;
            }
            $src = $from . '/' . $entry;
            $dst = $to . '/' . $entry;
            if (is_dir($src)) {
                if (!is_dir($dst)) {
                    @mkdir($dst, 0755, true);
                }
                $this->copyDir($src, $dst);
            } else {
                @copy($src, $dst);
            }
        }
    }

    private function removeDir(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }
        foreach ((array) scandir($dir) as $entry) {
            if ($entry === '.' || $entry === '..') {
                continue;
            }
            $path = $dir . '/' . $entry;
            is_dir($path) ? $this->removeDir($path) : @unlink($path);
        }
        @rmdir($dir);
    }

    private function httpGet(string $url, int $timeout): ?string
    {
        $headers = ['Accept: application/vnd.github+json'];
        $token = (string) Settings::get('update_github_token', '');
        if ($token !== '') {
            $headers[] = 'Authorization: Bearer ' . $token;
        }

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_MAXREDIRS => 5,
            CURLOPT_TIMEOUT => $timeout,
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_PROTOCOLS => CURLPROTO_HTTPS,
            CURLOPT_REDIR_PROTOCOLS => CURLPROTO_HTTPS,
            CURLOPT_USERAGENT => 'Family-CMS-Updater',
            CURLOPT_HTTPHEADER => $headers,
        ]);
        $body = curl_exec($ch);
        $code = (int) curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
        curl_close($ch);

        if ($body === false || $code < 200 || $code >= 300) {
            return null;
        }
        return (string) $body;
    }

    private function validRepo(string $repo): bool
    {
        return (bool) preg_match('~^[\w.-]+/[\w.-]+$~', $repo);
    }

    private function requireSystemAdmin(): void
    {
        Auth::requireLogin();
        if ((Auth::user()['role_slug'] ?? '') !== 'system_admin') {
            http_response_code(403);
            echo 'هذه الشاشة متاحة لمدير النظام فقط.';
            exit;
        }
    }
}
