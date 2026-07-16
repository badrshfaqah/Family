<?php

namespace Modules\Directory\Admin\Controllers;

use Core\ActivityLog;
use Core\Auth;
use Core\Database;
use Core\Settings;
use Core\Support\Csrf;
use Core\Support\Request;
use Core\Support\Response;
use Core\Support\Security;
use Core\Support\Session;
use Core\Support\Str;
use Core\Support\Url;
use Core\Terms;
use Core\View;
use Modules\Directory\Support\Csv;
use Modules\Directory\Support\Phone;

final class DirectoryAdminController
{
    private const PER_PAGE = 25;
    private const IMPORT_PREVIEW_ROWS = 15;
    private const MAX_IMPORT_BYTES = 5 * 1024 * 1024;

    public function index(array $params): void
    {
        Auth::requirePermission('directory.view');

        $branchesEnabled = $this->branchesEnabled();
        $q = Request::get('q', '');
        $cityId = Request::get('city_id', '');
        $branchId = Request::get('branch_id', '');
        $status = Request::get('status', '');

        [$where, $sqlParams] = $this->buildFilters($q, $cityId, $branchId, $status, $branchesEnabled);

        $page = max(1, Request::int('page', 1));
        $perPage = self::PER_PAGE;
        $offset = ($page - 1) * $perPage;

        $total = (int) Database::fetchValue(
            'SELECT COUNT(*) FROM ' . Database::table('directory_contacts') . ' c WHERE ' . $where,
            $sqlParams
        );

        $rows = Database::fetchAll(
            'SELECT c.*, ci.name AS city_name' . ($branchesEnabled ? ', b.name AS branch_name' : '') . '
             FROM ' . Database::table('directory_contacts') . ' c
             LEFT JOIN ' . Database::table('cities') . ' ci ON ci.id = c.city_id'
            . ($branchesEnabled ? ' LEFT JOIN ' . Database::table('branches') . ' b ON b.id = c.branch_id' : '') . '
             WHERE ' . $where . '
             ORDER BY c.id DESC
             LIMIT ' . $perPage . ' OFFSET ' . $offset,
            $sqlParams
        );

        ActivityLog::record('directory_view_list', 'استعراض قائمة سجلات ' . Terms::phrase('family_directory'));

        echo View::render(CORE_PATH . '/Admin/Views/layouts/admin.php', [
            'pageTitle' => Terms::phrase('family_directory'),
            'contentView' => __DIR__ . '/../Views/index.php',
            'rows' => $rows,
            'total' => $total,
            'page' => $page,
            'perPage' => $perPage,
            'summary' => $this->summary(),
            'cities' => $this->cityList(),
            'branches' => $branchesEnabled ? $this->branchList() : [],
            'branchesEnabled' => $branchesEnabled,
            'filters' => ['q' => $q, 'city_id' => $cityId, 'branch_id' => $branchId, 'status' => $status],
        ]);
    }

    public function create(array $params): void
    {
        Auth::requirePermission('directory.manage');

        echo View::render(CORE_PATH . '/Admin/Views/layouts/admin.php', [
            'pageTitle' => 'إضافة جهة اتصال',
            'contentView' => __DIR__ . '/../Views/form.php',
            'item' => null,
            'cities' => $this->cityList(),
            'branches' => $this->branchesEnabled() ? $this->branchList() : [],
            'branchesEnabled' => $this->branchesEnabled(),
        ]);
    }

    public function store(array $params): void
    {
        Auth::requirePermission('directory.manage');
        Csrf::verifyRequestOrFail();

        [$data, $errors] = $this->readContactInput();

        if ($errors) {
            Session::flash('error', implode(' ', $errors));
            Response::redirect(Url::admin('directory/create'));
        }

        $data['source'] = 'admin_manual';
        $data['registered_at'] = date('Y-m-d H:i:s');
        $data['created_by'] = Auth::user()['id'] ?? null;
        $data['updated_at'] = date('Y-m-d H:i:s');

        Database::insert('directory_contacts', $data);

        ActivityLog::record('directory_create', 'إضافة جهة اتصال: ' . $data['name'] . ' (' . Str::maskPhone($data['phone']) . ')');
        Session::flash('success', 'تمت إضافة جهة الاتصال.');
        Response::redirect(Url::admin('directory'));
    }

    public function edit(array $params): void
    {
        Auth::requirePermission('directory.view');

        $item = Database::fetchOne('SELECT * FROM ' . Database::table('directory_contacts') . ' WHERE id = ?', [(int) $params['id']]);
        if (!$item) {
            Response::redirect(Url::admin('directory'));
        }

        ActivityLog::record('directory_view_contact', 'عرض بيانات جهة اتصال: ' . $item['name'], 'directory_contact', $item['id']);

        echo View::render(CORE_PATH . '/Admin/Views/layouts/admin.php', [
            'pageTitle' => 'تعديل جهة اتصال',
            'contentView' => __DIR__ . '/../Views/form.php',
            'item' => $item,
            'cities' => $this->cityList(),
            'branches' => $this->branchesEnabled() ? $this->branchList() : [],
            'branchesEnabled' => $this->branchesEnabled(),
        ]);
    }

    public function update(array $params): void
    {
        Auth::requirePermission('directory.manage');
        Csrf::verifyRequestOrFail();

        $id = (int) $params['id'];
        [$data, $errors] = $this->readContactInput();

        if ($errors) {
            Session::flash('error', implode(' ', $errors));
            Response::redirect(Url::admin('directory/' . $id . '/edit'));
        }

        $data['updated_at'] = date('Y-m-d H:i:s');
        Database::update('directory_contacts', $data, ['id' => $id]);

        ActivityLog::record('directory_update', 'تعديل جهة اتصال: ' . $data['name'] . ' (' . Str::maskPhone($data['phone']) . ')', 'directory_contact', $id);
        Session::flash('success', 'تم تحديث جهة الاتصال.');
        Response::redirect(Url::admin('directory'));
    }

    public function delete(array $params): void
    {
        Auth::requirePermission('directory.manage');
        Csrf::verifyRequestOrFail();

        $id = (int) $params['id'];
        $item = Database::fetchOne('SELECT * FROM ' . Database::table('directory_contacts') . ' WHERE id = ?', [$id]);
        Database::delete('directory_contacts', ['id' => $id]);

        if ($item) {
            ActivityLog::record('directory_delete', 'حذف جهة اتصال: ' . $item['name'] . ' (' . Str::maskPhone($item['phone']) . ')', 'directory_contact', $id);
        }
        Session::flash('success', 'تم حذف جهة الاتصال.');
        Response::redirect(Url::admin('directory'));
    }

    public function toggleStatus(array $params): void
    {
        Auth::requirePermission('directory.manage');
        Csrf::verifyRequestOrFail();

        $id = (int) $params['id'];
        $item = Database::fetchOne('SELECT * FROM ' . Database::table('directory_contacts') . ' WHERE id = ?', [$id]);
        if (!$item) {
            Response::redirect(Url::admin('directory'));
        }

        $newStatus = $item['status'] === 'active' ? 'excluded' : 'active';
        Database::update('directory_contacts', ['status' => $newStatus, 'updated_at' => date('Y-m-d H:i:s')], ['id' => $id]);

        $label = $newStatus === 'excluded' ? 'إيقاف التواصل (بدون حذف)' : 'إعادة التفعيل';
        ActivityLog::record('directory_toggle_status', $label . ': ' . $item['name'] . ' (' . Str::maskPhone($item['phone']) . ')', 'directory_contact', $id);
        Session::flash('success', 'تم تحديث حالة جهة الاتصال.');
        Response::redirect(Url::admin('directory'));
    }

    public function duplicates(array $params): void
    {
        Auth::requirePermission('directory.manage');

        $phones = Database::fetchAll(
            'SELECT phone, COUNT(*) AS cnt FROM ' . Database::table('directory_contacts') . '
             GROUP BY phone HAVING COUNT(*) > 1 ORDER BY cnt DESC'
        );

        $groups = [];
        foreach ($phones as $p) {
            $groups[] = [
                'phone' => $p['phone'],
                'rows' => Database::fetchAll(
                    'SELECT * FROM ' . Database::table('directory_contacts') . ' WHERE phone = ? ORDER BY id ASC',
                    [$p['phone']]
                ),
            ];
        }

        ActivityLog::record('directory_view_duplicates', 'استعراض شاشة التكرارات (' . count($groups) . ' مجموعة)');

        echo View::render(CORE_PATH . '/Admin/Views/layouts/admin.php', [
            'pageTitle' => 'التكرارات',
            'contentView' => __DIR__ . '/../Views/duplicates.php',
            'groups' => $groups,
        ]);
    }

    public function mergeDuplicates(array $params): void
    {
        Auth::requirePermission('directory.manage');
        Csrf::verifyRequestOrFail();

        $phone = Request::trimmed('phone');
        $keepId = Request::int('keep_id');

        $keepRow = Database::fetchOne(
            'SELECT * FROM ' . Database::table('directory_contacts') . ' WHERE id = ? AND phone = ?',
            [$keepId, $phone]
        );

        if ($keepRow) {
            $deleted = Database::query(
                'DELETE FROM ' . Database::table('directory_contacts') . ' WHERE phone = ? AND id != ?',
                [$phone, $keepId]
            )->rowCount();

            ActivityLog::record(
                'directory_merge_duplicates',
                'دمج تكرارات الرقم ' . Str::maskPhone($phone) . ' — الإبقاء على السجل #' . $keepId . ' وحذف ' . $deleted . ' سجل مكرر'
            );
            Session::flash('success', 'تم دمج السجلات المكررة.');
        } else {
            Session::flash('error', 'تعذر تنفيذ الدمج، الرجاء المحاولة مجددًا.');
        }

        Response::redirect(Url::admin('directory/duplicates'));
    }

    public function importForm(array $params): void
    {
        Auth::requirePermission('directory.manage');

        echo View::render(CORE_PATH . '/Admin/Views/layouts/admin.php', [
            'pageTitle' => 'استيراد Excel (CSV)',
            'contentView' => __DIR__ . '/../Views/import.php',
        ]);
    }

    public function importPreview(array $params): void
    {
        Auth::requirePermission('directory.manage');
        Csrf::verifyRequestOrFail();

        $file = Request::file('csv_file');
        if (!$file || $file['error'] !== UPLOAD_ERR_OK) {
            Session::flash('error', 'الرجاء اختيار ملف CSV صالح.');
            Response::redirect(Url::admin('directory/import'));
        }

        if ($file['size'] > self::MAX_IMPORT_BYTES) {
            Session::flash('error', 'حجم الملف أكبر من الحد المسموح (5 ميجابايت).');
            Response::redirect(Url::admin('directory/import'));
        }

        $content = (string) file_get_contents($file['tmp_name']);
        $rows = Csv::parse($content);

        if (count($rows) < 1) {
            Session::flash('error', 'الملف فارغ أو لا يمكن قراءته.');
            Response::redirect(Url::admin('directory/import'));
        }

        $header = array_shift($rows);
        $totalDataRows = count($rows);
        $previewRows = array_slice($rows, 0, self::IMPORT_PREVIEW_ROWS);

        echo View::render(CORE_PATH . '/Admin/Views/layouts/admin.php', [
            'pageTitle' => 'معاينة الاستيراد',
            'contentView' => __DIR__ . '/../Views/import_preview.php',
            'header' => $header,
            'previewRows' => $previewRows,
            'totalDataRows' => $totalDataRows,
            'csvDataB64' => base64_encode($content),
            'branchesEnabled' => $this->branchesEnabled(),
            'mappingError' => null,
        ]);
    }

    public function importCommit(array $params): void
    {
        Auth::requirePermission('directory.manage');
        Csrf::verifyRequestOrFail();

        $content = base64_decode((string) Request::post('csv_data', ''), true);
        $rows = $content !== false ? Csv::parse($content) : [];

        if (count($rows) < 1) {
            Session::flash('error', 'تعذرت قراءة بيانات الملف، الرجاء إعادة رفعه.');
            Response::redirect(Url::admin('directory/import'));
        }

        $header = array_shift($rows);
        $totalDataRows = count($rows);

        $mapName = Request::post('map_name', '');
        $mapPhone = Request::post('map_phone', '');
        $mapCity = Request::post('map_city', '');
        $mapBranch = Request::post('map_branch', '');
        $mapEmail = Request::post('map_email', '');

        if ($mapName === '' || $mapPhone === '') {
            echo View::render(CORE_PATH . '/Admin/Views/layouts/admin.php', [
                'pageTitle' => 'معاينة الاستيراد',
                'contentView' => __DIR__ . '/../Views/import_preview.php',
                'header' => $header,
                'previewRows' => array_slice($rows, 0, self::IMPORT_PREVIEW_ROWS),
                'totalDataRows' => $totalDataRows,
                'csvDataB64' => base64_encode($content),
                'branchesEnabled' => $this->branchesEnabled(),
                'mappingError' => 'يجب تحديد عمودي الاسم والجوال على الأقل قبل الاستيراد.',
            ]);
            return;
        }

        $branchesEnabled = $this->branchesEnabled();
        $imported = 0;
        $skipped = [];

        foreach ($rows as $i => $row) {
            $rowNum = $i + 2; // بعد سطر العناوين
            $name = Security::cleanText($row[$mapName] ?? '');
            $phone = Phone::normalize((string) ($row[$mapPhone] ?? ''));

            $reasons = [];
            if ($name === '') {
                $reasons[] = 'الاسم مفقود';
            }
            if (!Phone::isValid($phone)) {
                $reasons[] = 'رقم الجوال مفقود أو غير صالح';
            }

            if ($reasons) {
                $skipped[] = ['row' => $rowNum, 'reason' => implode('، ', $reasons)];
                continue;
            }

            $exists = (int) Database::fetchValue(
                'SELECT COUNT(*) FROM ' . Database::table('directory_contacts') . " WHERE phone = ? AND status = 'active'",
                [$phone]
            );
            if ($exists) {
                $skipped[] = ['row' => $rowNum, 'reason' => 'رقم مكرر (موجود مسبقًا ضمن السجلات النشطة)'];
                continue;
            }

            $cityId = null;
            if ($mapCity !== '' && trim((string) ($row[$mapCity] ?? '')) !== '') {
                $cityId = $this->resolveCityId(trim($row[$mapCity]));
            }

            $branchId = null;
            if ($branchesEnabled && $mapBranch !== '' && trim((string) ($row[$mapBranch] ?? '')) !== '') {
                $branchId = $this->resolveBranchId(trim($row[$mapBranch]));
            }

            $email = null;
            if ($mapEmail !== '' && trim((string) ($row[$mapEmail] ?? '')) !== '') {
                $candidate = trim($row[$mapEmail]);
                $email = filter_var($candidate, FILTER_VALIDATE_EMAIL) ? $candidate : null;
            }

            Database::insert('directory_contacts', [
                'name' => $name,
                'phone' => $phone,
                'city_id' => $cityId,
                'branch_id' => $branchId,
                'email' => $email,
                'status' => 'active',
                'source' => 'import_csv',
                'consent_at' => null,
                'registered_at' => date('Y-m-d H:i:s'),
                'created_by' => Auth::user()['id'] ?? null,
                'updated_at' => date('Y-m-d H:i:s'),
            ]);
            $imported++;
        }

        ActivityLog::record(
            'directory_import',
            'استيراد CSV: تم إدخال ' . $imported . ' سجل من أصل ' . $totalDataRows . ' صف (تم تجاوز ' . count($skipped) . ' صف)'
        );

        echo View::render(CORE_PATH . '/Admin/Views/layouts/admin.php', [
            'pageTitle' => 'نتيجة الاستيراد',
            'contentView' => __DIR__ . '/../Views/import_result.php',
            'imported' => $imported,
            'totalDataRows' => $totalDataRows,
            'skipped' => $skipped,
        ]);
    }

    public function templateCsv(array $params): void
    {
        Auth::requirePermission('directory.manage');

        $out = Csv::startDownload('directory-template.csv');
        fputcsv($out, ['name', 'phone', 'city', 'branch', 'email']);
        fclose($out);
    }

    public function exportCsv(array $params): void
    {
        Auth::requirePermission('directory.export');

        $branchesEnabled = $this->branchesEnabled();
        $q = Request::get('q', '');
        $cityId = Request::get('city_id', '');
        $branchId = Request::get('branch_id', '');
        $status = Request::get('status', '');

        [$where, $sqlParams] = $this->buildFilters($q, $cityId, $branchId, $status, $branchesEnabled);

        $rows = Database::fetchAll(
            'SELECT c.*, ci.name AS city_name' . ($branchesEnabled ? ', b.name AS branch_name' : '') . '
             FROM ' . Database::table('directory_contacts') . ' c
             LEFT JOIN ' . Database::table('cities') . ' ci ON ci.id = c.city_id'
            . ($branchesEnabled ? ' LEFT JOIN ' . Database::table('branches') . ' b ON b.id = c.branch_id' : '') . '
             WHERE ' . $where . '
             ORDER BY c.id DESC',
            $sqlParams
        );

        $statusLabels = ['active' => 'نشط', 'excluded' => 'مستبعد من التواصل'];
        $sourceLabels = ['website_form' => 'نموذج الموقع', 'admin_manual' => 'إدخال يدوي', 'import_csv' => 'استيراد CSV'];

        $out = Csv::startDownload('directory-export-' . date('Ymd-His') . '.csv');
        fputcsv($out, ['الاسم', 'الجوال', 'المدينة', 'الفرع', 'البريد الإلكتروني', 'الحالة', 'المصدر', 'تاريخ التسجيل']);
        foreach ($rows as $r) {
            fputcsv($out, [
                $r['name'],
                $r['phone'],
                $r['city_name'] ?? '',
                $r['branch_name'] ?? '',
                $r['email'] ?? '',
                $statusLabels[$r['status']] ?? $r['status'],
                $sourceLabels[$r['source']] ?? $r['source'],
                $r['registered_at'],
            ]);
        }
        fclose($out);

        ActivityLog::record('directory_export', 'تصدير CSV — ' . count($rows) . ' جهة اتصال');
    }

    // ------------------------------------------------------------------
    // أدوات داخلية
    // ------------------------------------------------------------------

    /** @return array{0: array, 1: array} */
    private function readContactInput(): array
    {
        $errors = [];

        $name = Security::cleanText(Request::trimmed('name'));
        if ($name === '') {
            $errors[] = 'الاسم مطلوب.';
        }

        $phone = Phone::normalize(Request::trimmed('phone'));
        if (!Phone::isValid($phone)) {
            $errors[] = 'رقم الجوال غير صالح.';
        }

        $email = Security::cleanText(Request::trimmed('email'));
        if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'صيغة البريد الإلكتروني غير صحيحة.';
        }

        $status = in_array(Request::post('status'), ['active', 'excluded'], true) ? Request::post('status') : 'active';

        $data = [
            'name' => $name,
            'phone' => $phone,
            'city_id' => Request::int('city_id') ?: null,
            'branch_id' => $this->branchesEnabled() ? (Request::int('branch_id') ?: null) : null,
            'email' => $email !== '' ? $email : null,
            'status' => $status,
        ];

        return [$data, $errors];
    }

    private function buildFilters(string $q, string $cityId, string $branchId, string $status, bool $branchesEnabled): array
    {
        $where = ['1=1'];
        $params = [];

        $q = trim($q);
        if ($q !== '') {
            $where[] = '(c.name LIKE ? OR c.phone LIKE ?)';
            $params[] = '%' . $q . '%';
            $params[] = '%' . $q . '%';
        }

        if ($cityId !== '' && ctype_digit((string) $cityId)) {
            $where[] = 'c.city_id = ?';
            $params[] = (int) $cityId;
        }

        if ($branchesEnabled && $branchId !== '' && ctype_digit((string) $branchId)) {
            $where[] = 'c.branch_id = ?';
            $params[] = (int) $branchId;
        }

        if (in_array($status, ['active', 'excluded'], true)) {
            $where[] = 'c.status = ?';
            $params[] = $status;
        }

        return [implode(' AND ', $where), $params];
    }

    private function summary(): array
    {
        $table = Database::table('directory_contacts');
        return [
            'total' => (int) Database::fetchValue("SELECT COUNT(*) FROM {$table}"),
            'active' => (int) Database::fetchValue("SELECT COUNT(*) FROM {$table} WHERE status = 'active'"),
            'excluded' => (int) Database::fetchValue("SELECT COUNT(*) FROM {$table} WHERE status = 'excluded'"),
            'recent' => Database::fetchAll("SELECT name, phone, registered_at, source FROM {$table} ORDER BY id DESC LIMIT 10"),
        ];
    }

    private function cityList(): array
    {
        if (!Database::tableExists('cities')) {
            return [];
        }
        return Database::fetchAll('SELECT id, name FROM ' . Database::table('cities') . " WHERE status = 'active' ORDER BY sort_order ASC, name ASC");
    }

    private function branchList(): array
    {
        if (!Database::tableExists('branches')) {
            return [];
        }
        return Database::fetchAll('SELECT id, name FROM ' . Database::table('branches') . " WHERE status = 'active' ORDER BY sort_order ASC, name ASC");
    }

    private function branchesEnabled(): bool
    {
        return Settings::get('branches_enabled', '0') === '1';
    }

    private function resolveCityId(string $name): ?int
    {
        if (!Database::tableExists('cities')) {
            return null;
        }
        $id = Database::fetchValue('SELECT id FROM ' . Database::table('cities') . ' WHERE name = ? LIMIT 1', [$name]);
        return $id ? (int) $id : null;
    }

    private function resolveBranchId(string $name): ?int
    {
        if (!Database::tableExists('branches')) {
            return null;
        }
        $id = Database::fetchValue('SELECT id FROM ' . Database::table('branches') . ' WHERE name = ? LIMIT 1', [$name]);
        return $id ? (int) $id : null;
    }
}
