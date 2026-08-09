<?php

namespace Core\Admin\Controllers;

use Core\ActivityLog;
use Core\Auth;
use Core\Database;
use Core\Support\Csrf;
use Core\Support\Response;
use Core\Support\Session;
use Core\Support\Url;
use Core\View;

/** رسائل الزوار الواردة من نموذج المراسلة العام */
final class MessagesController
{
    public function index(array $params): void
    {
        Auth::requirePermission('system.settings');

        $rows = Database::tableExists('contact_messages')
            ? Database::fetchAll('SELECT * FROM ' . Database::table('contact_messages') . ' ORDER BY id DESC LIMIT 200')
            : [];

        echo View::render(CORE_PATH . '/Admin/Views/layouts/admin.php', [
            'pageTitle' => 'الرسائل',
            'contentView' => CORE_PATH . '/Admin/Views/messages/index.php',
            'rows' => $rows,
        ]);
    }

    public function markRead(array $params): void
    {
        Auth::requirePermission('system.settings');
        Csrf::verifyRequestOrFail();

        Database::update('contact_messages', ['status' => 'read'], ['id' => (int) $params['id']]);
        Response::redirect(Url::admin('messages'));
    }

    public function delete(array $params): void
    {
        Auth::requirePermission('system.settings');
        Csrf::verifyRequestOrFail();

        $id = (int) $params['id'];
        Database::delete('contact_messages', ['id' => $id]);
        ActivityLog::record('contact_delete', 'حذف رسالة زائر', 'contact_messages', $id);
        Session::flash('success', 'تم حذف الرسالة.');
        Response::redirect(Url::admin('messages'));
    }
}
