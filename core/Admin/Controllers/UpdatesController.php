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

    /** المستودع الرسمي للنظام — يُسحب منه التحديث تلقائيًا دون أي إعداد */
    private const DEFAULT_REPO = 'badrshfaqah/Family';

    public function index(array $params): void
    {
        $this->requireSystemAdmin();

        $latest = null;
        $error = '';

        try {
            $latest = $this->fetchLatestInfo($this->repo());
        } catch (\Throwable $e) {
            $error = $e->getMessage();
        }

        $pendingMigrations = 0;
        try {
            $pendingMigrations = \Core\Database\Migrator::pendingCount();
        } catch (\Throwable $e) {
            // لا نعطل شاشة التحديث إن تعذر فحص الترحيلات
        }

        $syncPending = [];
        try {
            $syncPending = \Core\SystemSync::pending();
        } catch (\Throwable $e) {
            // الشاشة تعرض ما تيسر
        }

        echo View::render(CORE_PATH . '/Admin/Views/layouts/admin.php', [
            'pageTitle' => 'تحديث النظام',
            'contentView' => CORE_PATH . '/Admin/Views/updates/index.php',
            'hasToken' => Settings::get('update_github_token', '') !== '',
            'currentVersion' => defined('CORE_VERSION') ? CORE_VERSION : '؟',
            'latest' => $latest,
            'checkError' => $error,
            'zipAvailable' => class_exists('ZipArchive'),
            'pendingMigrations' => $pendingMigrations,
            'syncPending' => $syncPending,
            'autoInstallModules' => Settings::get('update_auto_install_modules', '1') === '1',
        ]);
    }

    /** تطبيق ترحيلات بنية قاعدة البيانات يدويًا (جداول/أعمدة جديدة) دون تحديث الملفات */
    public function migrate(array $params): void
    {
        $this->requireSystemAdmin();
        Csrf::verifyRequestOrFail();

        try {
            $applied = \Core\Database\Migrator::migrate();
        } catch (\Throwable $e) {
            Session::flash('error', 'تعذر تحديث جداول قاعدة البيانات: ' . $e->getMessage());
            Response::redirect(Url::admin('updates'));
        }

        if ($applied) {
            ActivityLog::record('db_migrate', 'تحديث جداول قاعدة البيانات يدويًا: طُبق ' . count($applied) . ' ترحيل (' . implode('، ', $applied) . ')');
            Session::flash('success', 'تم تحديث جداول قاعدة البيانات بنجاح — طُبق ' . count($applied) . ' ترحيل جديد.');
        } else {
            Session::flash('success', 'جداول قاعدة البيانات محدثة بالفعل — لا توجد ترحيلات جديدة.');
        }
        Response::redirect(Url::admin('updates'));
    }

    public function saveRepo(array $params): void
    {
        $this->requireSystemAdmin();
        Csrf::verifyRequestOrFail();

        // مفتاح الوصول للمستودعات الخاصة: يُحفظ إن أُدخل، ويُمسح بالخيار الصريح فقط
        if (Request::post('remove_token')) {
            Settings::set('update_github_token', '');
            Session::flash('success', 'تم حذف مفتاح الوصول المحفوظ.');
        } else {
            $token = trim((string) Request::post('token', ''));
            if ($token !== '' && preg_match('/^[\w.\-]{20,255}$/', $token)) {
                Settings::set('update_github_token', $token);
                Session::flash('success', 'تم حفظ مفتاح الوصول بنجاح.');
            } else {
                Session::flash('error', $token === '' ? 'أدخل مفتاح الوصول أولًا.' : 'صيغة المفتاح غير صحيحة.');
            }
        }

        Response::redirect(Url::admin('updates'));
    }

    public function run(array $params): void
    {
        $this->requireSystemAdmin();
        Csrf::verifyRequestOrFail();
        @set_time_limit(300);

        $repo = $this->repo();
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

        // مزامنة كاملة مع النسخة الجديدة: ترحيلات النواة + ترحيل الإضافات
        // المثبتة + تثبيت الإضافات المرفقة الجديدة (حسب الإعداد)
        $syncLog = [];
        try {
            $syncLog = \Core\SystemSync::force();
        } catch (\Throwable $e) {
            Session::flash('error', 'حُدثت الملفات لكن تعذرت مزامنة قاعدة البيانات والإضافات: ' . $e->getMessage());
            Response::redirect(Url::admin('updates'));
        }

        // قراءة الإصدار الجديد من الملف مباشرة (الثابت القديم محمّل في الذاكرة)
        $newVersion = $oldVersion;
        if (preg_match("/CORE_VERSION',\s*'([^']+)'/", (string) file_get_contents(ROOT_PATH . '/core/version.php'), $m)) {
            $newVersion = $m[1];
        }

        Settings::clearCacheFile();
        $syncNote = $syncLog ? ('، والمزامنة: ' . implode('؛ ', $syncLog)) : '';
        ActivityLog::record('system_update', "تحديث النظام من GitHub: {$oldVersion} ← {$newVersion} ({$info['label']}){$syncNote}");
        Session::flash('success', "تم التحديث بنجاح: {$oldVersion} ← {$newVersion}{$syncNote}.");
        Response::redirect(Url::admin('updates'));
    }

    /** حفظ خيار التثبيت التلقائي للإضافات الجديدة وتشغيل المزامنة فورًا */
    public function syncModules(array $params): void
    {
        $this->requireSystemAdmin();
        Csrf::verifyRequestOrFail();

        Settings::set('update_auto_install_modules', Request::post('auto_install') ? '1' : '0');

        try {
            $log = \Core\SystemSync::force();
        } catch (\Throwable $e) {
            Session::flash('error', 'تعذرت المزامنة: ' . $e->getMessage());
            Response::redirect(Url::admin('updates'));
            return;
        }

        Session::flash('success', $log
            ? 'تمت المزامنة: ' . implode('؛ ', $log)
            : 'كل شيء محدث — لا توجد ترحيلات أو إضافات معلقة.');
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

    /** مستودع التحديث: المدمج في النظام، مع إمكانية تجاوزه بإعداد مخفي عند الحاجة */
    private function repo(): string
    {
        $override = (string) Settings::get('update_github_repo', '');
        return $override !== '' && $this->validRepo($override) ? $override : self::DEFAULT_REPO;
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
