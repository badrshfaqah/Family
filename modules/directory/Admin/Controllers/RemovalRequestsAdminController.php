<?php

namespace Modules\Directory\Admin\Controllers;

use Core\ActivityLog;
use Core\Auth;
use Core\Database;
use Core\Support\Csrf;
use Core\Support\Request;
use Core\Support\Response;
use Core\Support\Session;
use Core\Support\Url;
use Core\View;
use Modules\Directory\Support\Phone;

/**
 * مراجعة طلبات إلغاء الاشتراك المقدَّمة من الموقع العام (بدون تسجيل دخول للزائر).
 * لا يُحذف أو يُستبعد أي سجل تلقائيًا؛ يجب أن يعتمده مدير من هنا.
 */
final class RemovalRequestsAdminController
{
    public function index(array $params): void
    {
        Auth::requirePermission('directory.manage');

        $rows = Database::fetchAll(
            'SELECT * FROM ' . Database::table('directory_removal_requests') . "
             ORDER BY (status = 'pending') DESC, id DESC"
        );

        ActivityLog::record('directory_view_removal_requests', 'استعراض طلبات إلغاء الاشتراك (' . count($rows) . ' طلب)');

        echo View::render(CORE_PATH . '/Admin/Views/layouts/admin.php', [
            'pageTitle' => 'طلبات إلغاء الاشتراك',
            'contentView' => __DIR__ . '/../Views/removal_requests.php',
            'rows' => $rows,
        ]);
    }

    public function approve(array $params): void
    {
        Auth::requirePermission('directory.manage');
        Csrf::verifyRequestOrFail();

        $id = (int) $params['id'];
        $req = Database::fetchOne('SELECT * FROM ' . Database::table('directory_removal_requests') . " WHERE id = ? AND status = 'pending'", [$id]);
        if (!$req) {
            Session::flash('error', 'الطلب غير موجود أو تمت مراجعته مسبقًا.');
            Response::redirect(Url::admin('directory/removal-requests'));
        }

        $phone = Phone::normalize($req['phone']);
        $hardDelete = Request::bool('hard_delete');

        $affected = (int) Database::fetchValue(
            'SELECT COUNT(*) FROM ' . Database::table('directory_contacts') . ' WHERE phone = ?',
            [$phone]
        );

        if ($hardDelete) {
            Database::query('DELETE FROM ' . Database::table('directory_contacts') . ' WHERE phone = ?', [$phone]);
        } else {
            Database::query(
                'UPDATE ' . Database::table('directory_contacts') . " SET status = 'excluded', updated_at = ? WHERE phone = ?",
                [date('Y-m-d H:i:s'), $phone]
            );
        }

        Database::update('directory_removal_requests', [
            'status' => 'approved',
            'reviewed_by' => Auth::user()['id'] ?? null,
            'reviewed_at' => date('Y-m-d H:i:s'),
        ], ['id' => $id]);

        // سجل إداري يثبت تنفيذ طلب الإلغاء (سجل العمليات مخصص للإدارة فقط ولا يظهر للعامة).
        ActivityLog::record(
            'directory_removal_approved',
            'تمت الموافقة على طلب إلغاء الاشتراك للرقم ' . $phone . ' — ' . ($hardDelete ? 'حذف نهائي' : 'استبعاد من التواصل فقط') . ' (' . $affected . ' سجل متأثر)',
            'directory_removal_request',
            $id
        );

        Session::flash('success', 'تم اعتماد طلب إلغاء الاشتراك.');
        Response::redirect(Url::admin('directory/removal-requests'));
    }

    public function reject(array $params): void
    {
        Auth::requirePermission('directory.manage');
        Csrf::verifyRequestOrFail();

        $id = (int) $params['id'];
        Database::update('directory_removal_requests', [
            'status' => 'rejected',
            'reviewed_by' => Auth::user()['id'] ?? null,
            'reviewed_at' => date('Y-m-d H:i:s'),
        ], ['id' => $id]);

        ActivityLog::record('directory_removal_rejected', 'رفض طلب إلغاء اشتراك رقم الطلب #' . $id, 'directory_removal_request', $id);
        Session::flash('success', 'تم رفض الطلب.');
        Response::redirect(Url::admin('directory/removal-requests'));
    }
}
