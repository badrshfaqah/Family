<?php

namespace Modules\Obituaries\Admin\Controllers;

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

final class ObituariesAdminController
{
    public function index(array $params): void
    {
        Auth::requirePermission('content.obituaries');

        $rows = Database::fetchAll(
            'SELECT o.*, c.name AS city_name FROM ' . Database::table('obituaries') . ' o
             LEFT JOIN ' . Database::table('cities') . ' c ON c.id = o.city_id
             ORDER BY o.id DESC'
        );

        echo View::render(CORE_PATH . '/Admin/Views/layouts/admin.php', [
            'pageTitle' => 'الوفيات',
            'contentView' => __DIR__ . '/../Views/index.php',
            'rows' => $rows,
        ]);
    }

    public function create(array $params): void
    {
        Auth::requirePermission('content.obituaries');

        echo View::render(CORE_PATH . '/Admin/Views/layouts/admin.php', [
            'pageTitle' => 'إضافة إعلان وفاة',
            'contentView' => __DIR__ . '/../Views/form.php',
            'item' => null,
            'cities' => $this->cities(),
        ]);
    }

    public function store(array $params): void
    {
        Auth::requirePermission('content.obituaries');
        Csrf::verifyRequestOrFail();

        $data = $this->collect();
        if ($data['name'] === '') {
            Session::flash('error', 'اسم المتوفى مطلوب.');
            Response::redirect(Url::admin('obituaries/create'));
        }

        $data['created_by'] = Auth::user()['id'] ?? null;
        $data['created_at'] = date('Y-m-d H:i:s');
        $id = Database::insert('obituaries', $data);

        ActivityLog::record('obituary_create', 'إضافة إعلان وفاة: ' . $data['name'], 'obituaries', $id);
        Session::flash('success', 'تم حفظ إعلان الوفاة.');
        Response::redirect(Url::admin('obituaries'));
    }

    public function edit(array $params): void
    {
        Auth::requirePermission('content.obituaries');

        $item = Database::fetchOne('SELECT * FROM ' . Database::table('obituaries') . ' WHERE id = ?', [(int) $params['id']]);
        if (!$item) {
            Session::flash('error', 'الإعلان غير موجود.');
            Response::redirect(Url::admin('obituaries'));
        }

        echo View::render(CORE_PATH . '/Admin/Views/layouts/admin.php', [
            'pageTitle' => 'تعديل إعلان وفاة',
            'contentView' => __DIR__ . '/../Views/form.php',
            'item' => $item,
            'cities' => $this->cities(),
        ]);
    }

    public function update(array $params): void
    {
        Auth::requirePermission('content.obituaries');
        Csrf::verifyRequestOrFail();

        $id = (int) $params['id'];
        $data = $this->collect();
        if ($data['name'] === '') {
            Session::flash('error', 'اسم المتوفى مطلوب.');
            Response::redirect(Url::admin('obituaries/' . $id . '/edit'));
        }

        $data['updated_at'] = date('Y-m-d H:i:s');
        Database::update('obituaries', $data, ['id' => $id]);

        ActivityLog::record('obituary_update', 'تعديل إعلان وفاة: ' . $data['name'], 'obituaries', $id);
        Session::flash('success', 'تم تحديث إعلان الوفاة.');
        Response::redirect(Url::admin('obituaries'));
    }

    public function delete(array $params): void
    {
        Auth::requirePermission('content.obituaries');
        Csrf::verifyRequestOrFail();

        $id = (int) $params['id'];
        Database::delete('obituaries', ['id' => $id]);

        ActivityLog::record('obituary_delete', 'حذف إعلان وفاة', 'obituaries', $id);
        Session::flash('success', 'تم حذف الإعلان.');
        Response::redirect(Url::admin('obituaries'));
    }

    /** @return array<string,mixed> الحقول المشتركة بين الإنشاء والتحديث */
    private function collect(): array
    {
        $deceasedOn = Request::trimmed('deceased_on');

        return [
            'name' => Security::cleanText(Request::trimmed('name')),
            'city_id' => Request::int('city_id') ?: null,
            'deceased_on' => $deceasedOn !== '' ? date('Y-m-d', strtotime($deceasedOn)) : null,
            'condolence_venue' => Security::cleanText(Request::trimmed('condolence_venue')) ?: null,
            'condolence_times' => Security::cleanText(Request::trimmed('condolence_times')) ?: null,
            'condolence_map_url' => Security::cleanText(Request::trimmed('condolence_map_url')) ?: null,
            'details' => Security::cleanText(Request::trimmed('details')) ?: null,
            'status' => Request::post('status') === 'archived' ? 'archived' : 'active',
        ];
    }

    private function cities(): array
    {
        if (!Database::tableExists('cities')) {
            return [];
        }
        return Database::fetchAll('SELECT id, name FROM ' . Database::table('cities') . " WHERE status = 'active' ORDER BY sort_order ASC, name ASC");
    }
}
