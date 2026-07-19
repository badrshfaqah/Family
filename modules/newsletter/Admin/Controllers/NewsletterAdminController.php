<?php

namespace Modules\Newsletter\Admin\Controllers;

use Core\ActivityLog;
use Core\Auth;
use Core\Database;
use Core\Support\Csrf;
use Core\Support\Request;
use Core\Support\Response;
use Core\Support\Security;
use Core\Support\Session;
use Core\Support\Url;
use Core\View;

final class NewsletterAdminController
{
    private const PER_PAGE = 50;
    private const IMPORT_MAX_ROWS = 5000;
    private const IMPORT_SESSION_KEY = '_newsletter_import_valid';

    public function index(array $params): void
    {
        Auth::requirePermission('newsletter.manage');

        $filters = $this->filtersFromRequest();
        [$where, $bind] = $this->buildWhere($filters);

        $page = max(1, Request::int('page', 1));
        $offset = ($page - 1) * self::PER_PAGE;

        $total = (int) Database::fetchValue(
            'SELECT COUNT(*) FROM ' . Database::table('newsletter_subscribers') . " WHERE {$where}",
            $bind
        );

        $rows = Database::fetchAll(
            'SELECT * FROM ' . Database::table('newsletter_subscribers') . "
             WHERE {$where} ORDER BY id DESC LIMIT " . self::PER_PAGE . " OFFSET {$offset}",
            $bind
        );

        $categories = Database::fetchAll(
            'SELECT DISTINCT category FROM ' . Database::table('newsletter_subscribers') . "
             WHERE category IS NOT NULL AND category != '' ORDER BY category ASC"
        );

        $summary = [
            'total' => (int) Database::fetchValue('SELECT COUNT(*) FROM ' . Database::table('newsletter_subscribers')),
            'subscribed' => (int) Database::fetchValue(
                'SELECT COUNT(*) FROM ' . Database::table('newsletter_subscribers') . " WHERE status = 'subscribed'"
            ),
            'unsubscribed' => (int) Database::fetchValue(
                'SELECT COUNT(*) FROM ' . Database::table('newsletter_subscribers') . " WHERE status = 'unsubscribed'"
            ),
            'recent' => (int) Database::fetchValue(
                'SELECT COUNT(*) FROM ' . Database::table('newsletter_subscribers') . "
                 WHERE subscribed_at IS NOT NULL AND subscribed_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)"
            ),
        ];

        echo View::render(CORE_PATH . '/Admin/Views/layouts/admin.php', [
            'pageTitle' => 'القائمة البريدية',
            'contentView' => __DIR__ . '/../Views/index.php',
            'rows' => $rows,
            'categories' => array_column($categories, 'category'),
            'summary' => $summary,
            'filters' => $filters,
            'page' => $page,
            'perPage' => self::PER_PAGE,
            'total' => $total,
        ]);
    }

    public function create(array $params): void
    {
        Auth::requirePermission('newsletter.manage');
        echo View::render(CORE_PATH . '/Admin/Views/layouts/admin.php', [
            'pageTitle' => 'إضافة مشترك',
            'contentView' => __DIR__ . '/../Views/form.php',
            'item' => null,
        ]);
    }

    public function store(array $params): void
    {
        Auth::requirePermission('newsletter.manage');
        Csrf::verifyRequestOrFail();

        $name = Security::cleanText(Request::trimmed('name'));
        $email = strtolower(trim(Request::trimmed('email')));
        $category = Security::cleanText(Request::trimmed('category'));
        $status = $this->resolveStatus();

        if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            Session::flash('error', 'الرجاء إدخال بريد إلكتروني صحيح.');
            Response::redirect(Url::admin('newsletter/create'));
        }

        $exists = Database::fetchValue(
            'SELECT COUNT(*) FROM ' . Database::table('newsletter_subscribers') . ' WHERE LOWER(email) = ?',
            [$email]
        );
        if ((int) $exists > 0) {
            Session::flash('error', 'هذا البريد الإلكتروني موجود بالفعل في القائمة.');
            Response::redirect(Url::admin('newsletter/create'));
        }

        Database::insert('newsletter_subscribers', [
            'name' => $name !== '' ? $name : null,
            'email' => $email,
            'status' => $status,
            'category' => $category !== '' ? $category : null,
            'source' => 'admin_manual',
            'subscribed_at' => $status === 'subscribed' ? date('Y-m-d H:i:s') : null,
            'unsubscribed_at' => $status === 'unsubscribed' ? date('Y-m-d H:i:s') : null,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        ActivityLog::record('newsletter_create', 'إضافة مشترك: ' . $email);
        Session::flash('success', 'تمت إضافة المشترك.');
        Response::redirect(Url::admin('newsletter'));
    }

    public function edit(array $params): void
    {
        Auth::requirePermission('newsletter.manage');
        $item = Database::fetchOne('SELECT * FROM ' . Database::table('newsletter_subscribers') . ' WHERE id = ?', [(int) $params['id']]);
        if (!$item) {
            Response::redirect(Url::admin('newsletter'));
        }

        echo View::render(CORE_PATH . '/Admin/Views/layouts/admin.php', [
            'pageTitle' => 'تعديل مشترك',
            'contentView' => __DIR__ . '/../Views/form.php',
            'item' => $item,
        ]);
    }

    public function update(array $params): void
    {
        Auth::requirePermission('newsletter.manage');
        Csrf::verifyRequestOrFail();

        $id = (int) $params['id'];
        $item = Database::fetchOne('SELECT * FROM ' . Database::table('newsletter_subscribers') . ' WHERE id = ?', [$id]);
        if (!$item) {
            Response::redirect(Url::admin('newsletter'));
        }

        $name = Security::cleanText(Request::trimmed('name'));
        $email = strtolower(trim(Request::trimmed('email')));
        $category = Security::cleanText(Request::trimmed('category'));
        $status = $this->resolveStatus();

        if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            Session::flash('error', 'الرجاء إدخال بريد إلكتروني صحيح.');
            Response::redirect(Url::admin('newsletter/' . $id . '/edit'));
        }

        $exists = Database::fetchValue(
            'SELECT COUNT(*) FROM ' . Database::table('newsletter_subscribers') . ' WHERE LOWER(email) = ? AND id != ?',
            [$email, $id]
        );
        if ((int) $exists > 0) {
            Session::flash('error', 'هذا البريد الإلكتروني مستخدم من قبل مشترك آخر.');
            Response::redirect(Url::admin('newsletter/' . $id . '/edit'));
        }

        $data = [
            'name' => $name !== '' ? $name : null,
            'email' => $email,
            'status' => $status,
            'category' => $category !== '' ? $category : null,
            'updated_at' => date('Y-m-d H:i:s'),
        ];

        if ($status === 'subscribed') {
            $data['subscribed_at'] = $item['subscribed_at'] ?: date('Y-m-d H:i:s');
            $data['unsubscribed_at'] = null;
        } else {
            $data['unsubscribed_at'] = date('Y-m-d H:i:s');
        }

        Database::update('newsletter_subscribers', $data, ['id' => $id]);

        ActivityLog::record('newsletter_update', 'تعديل مشترك: ' . $email, 'newsletter_subscribers', $id);
        Session::flash('success', 'تم تحديث بيانات المشترك.');
        Response::redirect(Url::admin('newsletter'));
    }

    public function delete(array $params): void
    {
        Auth::requirePermission('newsletter.manage');
        Csrf::verifyRequestOrFail();

        $id = (int) $params['id'];
        Database::delete('newsletter_subscribers', ['id' => $id]);
        ActivityLog::record('newsletter_delete', 'حذف مشترك رقم: ' . $id);
        Session::flash('success', 'تم حذف المشترك.');
        Response::redirect(Url::admin('newsletter'));
    }

    public function bulkCategory(array $params): void
    {
        Auth::requirePermission('newsletter.manage');
        Csrf::verifyRequestOrFail();

        $ids = array_filter(array_map('intval', (array) Request::post('ids', [])));
        $category = Security::cleanText(Request::trimmed('bulk_category'));

        if (!empty($ids)) {
            $placeholders = implode(',', array_fill(0, count($ids), '?'));
            $bind = $category !== '' ? array_merge([$category, date('Y-m-d H:i:s')], $ids) : array_merge([date('Y-m-d H:i:s')], $ids);
            Database::query(
                'UPDATE ' . Database::table('newsletter_subscribers') . "
                 SET category = " . ($category !== '' ? '?' : 'NULL') . ", updated_at = ?
                 WHERE id IN ({$placeholders})",
                $bind
            );
            ActivityLog::record('newsletter_bulk_category', 'تصنيف جماعي لعدد ' . count($ids) . ' مشترك: ' . ($category !== '' ? $category : 'بدون تصنيف'));
            Session::flash('success', 'تم تحديث تصنيف ' . count($ids) . ' مشترك.');
        } else {
            Session::flash('error', 'لم يتم تحديد أي مشترك.');
        }

        Response::redirect(Url::admin('newsletter') . $this->currentFilterQuery());
    }

    public function exportCsv(array $params): void
    {
        Auth::requirePermission('newsletter.manage');

        $filters = $this->filtersFromRequest();
        [$where, $bind] = $this->buildWhere($filters);

        $rows = Database::fetchAll(
            'SELECT * FROM ' . Database::table('newsletter_subscribers') . " WHERE {$where} ORDER BY id DESC",
            $bind
        );

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="newsletter_subscribers.csv"');

        $out = fopen('php://output', 'w');
        // BOM لضمان فتح الأحرف العربية بشكل صحيح في برامج مثل إكسل.
        fwrite($out, "\xEF\xBB\xBF");
        \Core\Support\Csv::writeRow($out, ['id', 'name', 'email', 'status', 'category', 'source', 'subscribed_at', 'unsubscribed_at']);
        foreach ($rows as $r) {
            \Core\Support\Csv::writeRow($out, [
                $r['id'], $r['name'], $r['email'], $r['status'], $r['category'],
                $r['source'], $r['subscribed_at'], $r['unsubscribed_at'],
            ]);
        }
        fclose($out);
        ActivityLog::record('newsletter_export', 'تصدير CSV لعدد ' . count($rows) . ' مشترك');
        exit;
    }

    public function importForm(array $params): void
    {
        Auth::requirePermission('newsletter.manage');
        echo View::render(CORE_PATH . '/Admin/Views/layouts/admin.php', [
            'pageTitle' => 'استيراد مشتركين (CSV)',
            'contentView' => __DIR__ . '/../Views/import.php',
        ]);
    }

    public function importPreview(array $params): void
    {
        Auth::requirePermission('newsletter.manage');
        Csrf::verifyRequestOrFail();

        $file = Request::file('csv_file');
        if (!$file || $file['error'] !== UPLOAD_ERR_OK) {
            Session::flash('error', 'الرجاء اختيار ملف CSV صحيح.');
            Response::redirect(Url::admin('newsletter/import'));
        }

        $handle = fopen($file['tmp_name'], 'r');
        if (!$handle) {
            Session::flash('error', 'تعذر قراءة الملف المرفوع.');
            Response::redirect(Url::admin('newsletter/import'));
        }

        $header = fgetcsv($handle, 0, ',', '"', '\\');
        if (!$header) {
            fclose($handle);
            Session::flash('error', 'الملف فارغ أو غير صالح.');
            Response::redirect(Url::admin('newsletter/import'));
        }

        // إزالة BOM المحتمل من أول عمود في الترويسة.
        $header[0] = preg_replace('/^\xEF\xBB\xBF/', '', (string) $header[0]);
        $map = $this->detectColumnMap($header);

        if ($map['email'] === null) {
            fclose($handle);
            Session::flash('error', 'تعذر التعرف على عمود البريد الإلكتروني في الملف. تأكد من وجود عمود باسم email أو "البريد الإلكتروني".');
            Response::redirect(Url::admin('newsletter/import'));
        }

        $existingEmails = array_column(
            Database::fetchAll('SELECT email FROM ' . Database::table('newsletter_subscribers')),
            'email'
        );
        $existingEmails = array_flip(array_map('strtolower', $existingEmails));

        $seenInFile = [];
        $preview = [];
        $validRows = [];
        $rowNum = 1;

        while (($row = fgetcsv($handle, 0, ',', '"', '\\')) !== false && $rowNum <= self::IMPORT_MAX_ROWS) {
            $rowNum++;
            if (count($row) === 1 && trim((string) $row[0]) === '') {
                continue; // سطر فارغ
            }

            $email = strtolower(trim((string) ($row[$map['email']] ?? '')));
            $name = $map['name'] !== null ? Security::cleanText((string) ($row[$map['name']] ?? '')) : '';
            $category = $map['category'] !== null ? Security::cleanText((string) ($row[$map['category']] ?? '')) : '';

            $status = 'صالح';
            $reason = '';

            if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $status = 'خطأ';
                $reason = 'بريد إلكتروني غير صالح';
            } elseif (isset($seenInFile[$email])) {
                $status = 'مكرر';
                $reason = 'مكرر داخل نفس الملف';
            } elseif (isset($existingEmails[$email])) {
                $status = 'موجود';
                $reason = 'مشترك بالفعل في القائمة';
            }

            if ($status === 'صالح') {
                $seenInFile[$email] = true;
                $validRows[] = ['name' => $name !== '' ? $name : null, 'email' => $email, 'category' => $category !== '' ? $category : null];
            }

            $preview[] = ['row' => $rowNum - 1, 'name' => $name, 'email' => $email, 'category' => $category, 'status' => $status, 'reason' => $reason];
        }
        fclose($handle);

        Session::set(self::IMPORT_SESSION_KEY, $validRows);

        echo View::render(CORE_PATH . '/Admin/Views/layouts/admin.php', [
            'pageTitle' => 'معاينة الاستيراد',
            'contentView' => __DIR__ . '/../Views/import.php',
            'preview' => $preview,
            'validCount' => count($validRows),
            'totalCount' => count($preview),
        ]);
    }

    public function importCommit(array $params): void
    {
        Auth::requirePermission('newsletter.manage');
        Csrf::verifyRequestOrFail();

        $validRows = Session::get(self::IMPORT_SESSION_KEY, []);
        Session::remove(self::IMPORT_SESSION_KEY);

        if (empty($validRows)) {
            Session::flash('error', 'لا توجد بيانات صالحة للاستيراد. الرجاء إعادة رفع الملف.');
            Response::redirect(Url::admin('newsletter/import'));
        }

        $inserted = 0;
        foreach ($validRows as $row) {
            // تحقق أخير قبل الإدراج تحسبًا لاشتراك جديد بنفس البريد بين المعاينة والتأكيد.
            $exists = Database::fetchValue(
                'SELECT COUNT(*) FROM ' . Database::table('newsletter_subscribers') . ' WHERE LOWER(email) = ?',
                [$row['email']]
            );
            if ((int) $exists > 0) {
                continue;
            }

            Database::insert('newsletter_subscribers', [
                'name' => $row['name'],
                'email' => $row['email'],
                'status' => 'subscribed',
                'category' => $row['category'],
                'source' => 'import_csv',
                'subscribed_at' => date('Y-m-d H:i:s'),
                'unsubscribed_at' => null,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ]);
            $inserted++;
        }

        ActivityLog::record('newsletter_import', 'استيراد CSV: إضافة ' . $inserted . ' مشترك');
        Session::flash('success', 'تم استيراد ' . $inserted . ' مشترك بنجاح.');
        Response::redirect(Url::admin('newsletter'));
    }

    private function detectColumnMap(array $header): array
    {
        $map = ['name' => null, 'email' => null, 'category' => null];
        $emailKeys = ['email', 'e-mail', 'البريد الإلكتروني', 'البريد', 'بريد إلكتروني', 'بريد'];
        $nameKeys = ['name', 'الاسم', 'اسم'];
        $categoryKeys = ['category', 'التصنيف', 'تصنيف'];

        foreach ($header as $index => $col) {
            $col = mb_strtolower(trim((string) $col));
            if ($map['email'] === null && in_array($col, $emailKeys, true)) {
                $map['email'] = $index;
                continue;
            }
            if ($map['name'] === null && in_array($col, $nameKeys, true)) {
                $map['name'] = $index;
                continue;
            }
            if ($map['category'] === null && in_array($col, $categoryKeys, true)) {
                $map['category'] = $index;
            }
        }

        return $map;
    }

    private function resolveStatus(): string
    {
        $status = Request::post('status', 'subscribed');
        return in_array($status, ['subscribed', 'unsubscribed'], true) ? $status : 'subscribed';
    }

    private function filtersFromRequest(): array
    {
        return [
            'q' => Request::trimmed('q'),
            'status' => Request::trimmed('status'),
            'category' => Request::trimmed('category'),
        ];
    }

    private function buildWhere(array $filters): array
    {
        $where = ['1=1'];
        $bind = [];

        if ($filters['q'] !== '') {
            $where[] = '(name LIKE ? OR email LIKE ?)';
            $like = '%' . $filters['q'] . '%';
            $bind[] = $like;
            $bind[] = $like;
        }
        if ($filters['status'] !== '' && in_array($filters['status'], ['subscribed', 'unsubscribed'], true)) {
            $where[] = 'status = ?';
            $bind[] = $filters['status'];
        }
        if ($filters['category'] !== '') {
            $where[] = 'category = ?';
            $bind[] = $filters['category'];
        }

        return [implode(' AND ', $where), $bind];
    }

    private function currentFilterQuery(): string
    {
        $filters = array_filter($this->filtersFromRequest());
        return $filters ? ('?' . http_build_query($filters)) : '';
    }
}
