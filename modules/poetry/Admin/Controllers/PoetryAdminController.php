<?php

namespace Modules\Poetry\Admin\Controllers;

use Core\ActivityLog;
use Core\Auth;
use Core\Database;
use Core\Media;
use Core\Support\Csrf;
use Core\Support\Request;
use Core\Support\Response;
use Core\Support\Security;
use Core\Support\Session;
use Core\Support\Url;
use Core\View;

final class PoetryAdminController
{
    /* ─── الشعراء ─────────────────────────────── */

    public function index(array $params): void
    {
        Auth::requirePermission('content.poetry');

        $rows = Database::fetchAll(
            'SELECT p.*, (SELECT COUNT(*) FROM ' . Database::table('poems') . ' po WHERE po.poet_id = p.id) AS poems_count
             FROM ' . Database::table('poets') . ' p
             ORDER BY p.sort_order ASC, p.id DESC'
        );

        echo View::render(CORE_PATH . '/Admin/Views/layouts/admin.php', [
            'pageTitle' => 'سماء الشعراء',
            'contentView' => __DIR__ . '/../Views/index.php',
            'rows' => $rows,
        ]);
    }

    public function create(array $params): void
    {
        Auth::requirePermission('content.poetry');

        echo View::render(CORE_PATH . '/Admin/Views/layouts/admin.php', [
            'pageTitle' => 'إضافة شاعر',
            'contentView' => __DIR__ . '/../Views/poet-form.php',
            'item' => null,
            'poems' => [],
        ]);
    }

    public function store(array $params): void
    {
        Auth::requirePermission('content.poetry');
        Csrf::verifyRequestOrFail();

        $name = Security::cleanText(Request::trimmed('name'));
        if ($name === '') {
            Session::flash('error', 'اسم الشاعر مطلوب.');
            Response::redirect(Url::admin('poetry/create'));
        }

        $id = Database::insert('poets', [
            'name' => $name,
            'bio' => Security::cleanText(Request::trimmed('bio')) ?: null,
            'photo_media_id' => $this->handlePhotoUpload(),
            'sort_order' => Request::int('sort_order', 0),
            'status' => Request::post('status') === 'hidden' ? 'hidden' : 'active',
            'created_at' => date('Y-m-d H:i:s'),
        ]);

        ActivityLog::record('poet_create', 'إضافة شاعر: ' . $name, 'poets', $id);
        Session::flash('success', 'تمت إضافة الشاعر — أضف قصائده الآن.');
        Response::redirect(Url::admin('poetry/' . $id . '/edit'));
    }

    public function edit(array $params): void
    {
        Auth::requirePermission('content.poetry');

        $item = Database::fetchOne('SELECT * FROM ' . Database::table('poets') . ' WHERE id = ?', [(int) $params['id']]);
        if (!$item) {
            Session::flash('error', 'الشاعر غير موجود.');
            Response::redirect(Url::admin('poetry'));
        }

        $poems = Database::fetchAll(
            'SELECT * FROM ' . Database::table('poems') . ' WHERE poet_id = ? ORDER BY sort_order ASC, id DESC',
            [$item['id']]
        );

        echo View::render(CORE_PATH . '/Admin/Views/layouts/admin.php', [
            'pageTitle' => 'الشاعر: ' . $item['name'],
            'contentView' => __DIR__ . '/../Views/poet-form.php',
            'item' => $item,
            'poems' => $poems,
        ]);
    }

    public function update(array $params): void
    {
        Auth::requirePermission('content.poetry');
        Csrf::verifyRequestOrFail();

        $id = (int) $params['id'];
        $name = Security::cleanText(Request::trimmed('name'));
        if ($name === '') {
            Session::flash('error', 'اسم الشاعر مطلوب.');
            Response::redirect(Url::admin('poetry/' . $id . '/edit'));
        }

        $data = [
            'name' => $name,
            'bio' => Security::cleanText(Request::trimmed('bio')) ?: null,
            'sort_order' => Request::int('sort_order', 0),
            'status' => Request::post('status') === 'hidden' ? 'hidden' : 'active',
            'updated_at' => date('Y-m-d H:i:s'),
        ];

        $photoId = $this->handlePhotoUpload();
        if ($photoId) {
            $data['photo_media_id'] = $photoId;
        }

        Database::update('poets', $data, ['id' => $id]);

        ActivityLog::record('poet_update', 'تعديل شاعر: ' . $name, 'poets', $id);
        Session::flash('success', 'تم تحديث بيانات الشاعر.');
        Response::redirect(Url::admin('poetry/' . $id . '/edit'));
    }

    public function delete(array $params): void
    {
        Auth::requirePermission('content.poetry');
        Csrf::verifyRequestOrFail();

        $id = (int) $params['id'];
        Database::delete('poems', ['poet_id' => $id]);
        Database::delete('poets', ['id' => $id]);

        ActivityLog::record('poet_delete', 'حذف شاعر وقصائده', 'poets', $id);
        Session::flash('success', 'تم حذف الشاعر وجميع قصائده.');
        Response::redirect(Url::admin('poetry'));
    }

    /* ─── القصائد ─────────────────────────────── */

    public function poemCreate(array $params): void
    {
        Auth::requirePermission('content.poetry');

        echo View::render(CORE_PATH . '/Admin/Views/layouts/admin.php', [
            'pageTitle' => 'إضافة قصيدة',
            'contentView' => __DIR__ . '/../Views/poem-form.php',
            'item' => null,
            'poets' => $this->poets(),
            'preselectedPoet' => Request::int('poet_id', 0),
        ]);
    }

    public function poemStore(array $params): void
    {
        Auth::requirePermission('content.poetry');
        Csrf::verifyRequestOrFail();

        $data = $this->collectPoem();
        if ($data === null) {
            Response::redirect(Url::admin('poems/create'));
        }

        $data['created_at'] = date('Y-m-d H:i:s');
        $id = Database::insert('poems', $data);

        ActivityLog::record('poem_create', 'إضافة قصيدة: ' . $data['title'], 'poems', $id);
        Session::flash('success', 'تم حفظ القصيدة.');
        Response::redirect(Url::admin('poetry/' . $data['poet_id'] . '/edit'));
    }

    public function poemEdit(array $params): void
    {
        Auth::requirePermission('content.poetry');

        $item = Database::fetchOne('SELECT * FROM ' . Database::table('poems') . ' WHERE id = ?', [(int) $params['id']]);
        if (!$item) {
            Session::flash('error', 'القصيدة غير موجودة.');
            Response::redirect(Url::admin('poetry'));
        }

        echo View::render(CORE_PATH . '/Admin/Views/layouts/admin.php', [
            'pageTitle' => 'تعديل قصيدة',
            'contentView' => __DIR__ . '/../Views/poem-form.php',
            'item' => $item,
            'poets' => $this->poets(),
            'preselectedPoet' => (int) $item['poet_id'],
        ]);
    }

    public function poemUpdate(array $params): void
    {
        Auth::requirePermission('content.poetry');
        Csrf::verifyRequestOrFail();

        $id = (int) $params['id'];
        $data = $this->collectPoem();
        if ($data === null) {
            Response::redirect(Url::admin('poems/' . $id . '/edit'));
        }

        $data['updated_at'] = date('Y-m-d H:i:s');
        Database::update('poems', $data, ['id' => $id]);

        ActivityLog::record('poem_update', 'تعديل قصيدة: ' . $data['title'], 'poems', $id);
        Session::flash('success', 'تم تحديث القصيدة.');
        Response::redirect(Url::admin('poetry/' . $data['poet_id'] . '/edit'));
    }

    public function poemDelete(array $params): void
    {
        Auth::requirePermission('content.poetry');
        Csrf::verifyRequestOrFail();

        $id = (int) $params['id'];
        $poem = Database::fetchOne('SELECT poet_id FROM ' . Database::table('poems') . ' WHERE id = ?', [$id]);
        Database::delete('poems', ['id' => $id]);

        ActivityLog::record('poem_delete', 'حذف قصيدة', 'poems', $id);
        Session::flash('success', 'تم حذف القصيدة.');
        Response::redirect(Url::admin('poetry' . ($poem ? '/' . $poem['poet_id'] . '/edit' : '')));
    }

    /* ─── مساعدات ─────────────────────────────── */

    /** @return array<string,mixed>|null بيانات القصيدة أو null عند نقص الحقول */
    private function collectPoem(): ?array
    {
        $poetId = Request::int('poet_id');
        $title = Security::cleanText(Request::trimmed('title'));
        $content = trim((string) Request::post('content', ''));
        $content = Security::cleanText($content);

        if (!$poetId || $title === '' || $content === '') {
            Session::flash('error', 'الشاعر وعنوان القصيدة ونصها حقول مطلوبة.');
            return null;
        }

        return [
            'poet_id' => $poetId,
            'title' => $title,
            'content' => $content,
            'occasion' => Security::cleanText(Request::trimmed('occasion')) ?: null,
            'sort_order' => Request::int('sort_order', 0),
            'status' => Request::post('status') === 'draft' ? 'draft' : 'published',
        ];
    }

    private function handlePhotoUpload(): ?int
    {
        $file = Request::file('photo');
        if (!$file) {
            return null;
        }
        try {
            $data = Media::handleUpload($file, Auth::user()['id'] ?? null);
            return (int) Database::insert('media', array_merge($data, ['created_at' => date('Y-m-d H:i:s')]));
        } catch (\Throwable $e) {
            Session::flash('error', $e->getMessage());
            return null;
        }
    }

    private function poets(): array
    {
        return Database::fetchAll('SELECT id, name FROM ' . Database::table('poets') . ' ORDER BY sort_order ASC, name ASC');
    }
}
