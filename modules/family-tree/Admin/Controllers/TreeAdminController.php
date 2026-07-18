<?php

namespace Modules\FamilyTree\Admin\Controllers;

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
use Modules\FamilyTree\TreeHelper;

final class TreeAdminController
{
    public function index(array $params): void
    {
        Auth::requirePermission('content.tree');

        $nodes = $this->allNodes();
        $ordered = TreeHelper::flatten($nodes);
        $branches = $this->branchOptions();

        echo View::render(CORE_PATH . '/Admin/Views/layouts/admin.php', [
            'pageTitle' => 'شجرة النسب',
            'contentView' => __DIR__ . '/../Views/index.php',
            'ordered' => $ordered,
            'branches' => $branches,
        ]);
    }

    /** رفع أو إزالة صورة جاهزة تُعرض للزوار بدل الشجرة التفاعلية */
    public function saveImage(array $params): void
    {
        Auth::requirePermission('content.tree');
        Csrf::verifyRequestOrFail();

        if (Request::post('remove_image')) {
            \Core\Settings::set('tree_image_media_id', '');
            ActivityLog::record('tree_image', 'إزالة صورة شجرة النسب والعودة للعرض التفاعلي');
            Session::flash('success', 'تمت إزالة الصورة وعاد العرض التفاعلي للزوار.');
            Response::redirect(Url::admin('tree-nodes'));
        }

        $file = Request::file('tree_image');
        if (!$file) {
            Session::flash('error', 'اختر ملف صورة أولًا.');
            Response::redirect(Url::admin('tree-nodes'));
        }

        try {
            $data = \Core\Media::handleUpload($file, Auth::user()['id'] ?? null);
            $mediaId = Database::insert('media', array_merge($data, ['created_at' => date('Y-m-d H:i:s')]));
            \Core\Settings::set('tree_image_media_id', (string) $mediaId);
            ActivityLog::record('tree_image', 'رفع صورة لشجرة النسب');
            Session::flash('success', 'تم اعتماد الصورة وستظهر للزوار بدل الشجرة التفاعلية.');
        } catch (\Throwable $e) {
            Session::flash('error', $e->getMessage());
        }

        Response::redirect(Url::admin('tree-nodes'));
    }

    public function create(array $params): void
    {
        Auth::requirePermission('content.tree');

        echo View::render(CORE_PATH . '/Admin/Views/layouts/admin.php', [
            'pageTitle' => 'إضافة عقدة جديدة',
            'contentView' => __DIR__ . '/../Views/form.php',
            'item' => null,
            'parentOptions' => TreeHelper::flatten($this->allNodes()),
            'branches' => $this->branchOptions(),
        ]);
    }

    public function store(array $params): void
    {
        Auth::requirePermission('content.tree');
        Csrf::verifyRequestOrFail();

        $name = Security::cleanText(Request::trimmed('name'));
        if ($name === '') {
            Session::flash('error', 'اسم العقدة مطلوب.');
            Response::redirect(Url::admin('tree-nodes/create'));
        }

        $maxOrder = (int) Database::fetchValue('SELECT COALESCE(MAX(sort_order),0) FROM ' . Database::table('tree_nodes'));

        Database::insert('tree_nodes', [
            'name' => $name,
            'parent_id' => Request::int('parent_id') ?: null,
            'sort_order' => Request::post('sort_order') !== null && Request::trimmed('sort_order') !== '' ? Request::int('sort_order') : $maxOrder + 1,
            'note' => Security::cleanText(Request::trimmed('note')),
            'branch_id' => Request::int('branch_id') ?: null,
            'is_visible' => Request::bool('is_visible') ? 1 : 0,
            'created_at' => date('Y-m-d H:i:s'),
        ]);

        ActivityLog::record('tree_node_create', 'إضافة عقدة نسب: ' . $name);
        Session::flash('success', 'تمت إضافة العقدة.');
        Response::redirect(Url::admin('tree-nodes'));
    }

    public function edit(array $params): void
    {
        Auth::requirePermission('content.tree');

        $id = (int) $params['id'];
        $item = Database::fetchOne('SELECT * FROM ' . Database::table('tree_nodes') . ' WHERE id = ?', [$id]);
        if (!$item) {
            Response::redirect(Url::admin('tree-nodes'));
        }

        $nodes = $this->allNodes();
        $excluded = array_merge([$id], TreeHelper::descendantIds($nodes, $id));
        $available = array_values(array_filter($nodes, fn($n) => !in_array((int) $n['id'], $excluded, true)));

        echo View::render(CORE_PATH . '/Admin/Views/layouts/admin.php', [
            'pageTitle' => 'تعديل عقدة',
            'contentView' => __DIR__ . '/../Views/form.php',
            'item' => $item,
            'parentOptions' => TreeHelper::flatten($available),
            'branches' => $this->branchOptions(),
        ]);
    }

    public function update(array $params): void
    {
        Auth::requirePermission('content.tree');
        Csrf::verifyRequestOrFail();

        $id = (int) $params['id'];
        $name = Security::cleanText(Request::trimmed('name'));
        if ($name === '') {
            Session::flash('error', 'اسم العقدة مطلوب.');
            Response::redirect(Url::admin('tree-nodes/' . $id . '/edit'));
        }

        $nodes = $this->allNodes();
        $forbidden = array_merge([$id], TreeHelper::descendantIds($nodes, $id));
        $parentId = Request::int('parent_id') ?: null;
        if ($parentId !== null && in_array($parentId, $forbidden, true)) {
            // منع اختيار العقدة نفسها أو أحد أحفادها كأب لها (حلقة مفرغة)
            $parentId = null;
        }

        Database::update('tree_nodes', [
            'name' => $name,
            'parent_id' => $parentId,
            'sort_order' => Request::int('sort_order', 0),
            'note' => Security::cleanText(Request::trimmed('note')),
            'branch_id' => Request::int('branch_id') ?: null,
            'is_visible' => Request::bool('is_visible') ? 1 : 0,
            'updated_at' => date('Y-m-d H:i:s'),
        ], ['id' => $id]);

        ActivityLog::record('tree_node_update', 'تعديل عقدة نسب: ' . $name, 'tree_node', $id);
        Session::flash('success', 'تم تحديث العقدة.');
        Response::redirect(Url::admin('tree-nodes'));
    }

    public function delete(array $params): void
    {
        Auth::requirePermission('content.tree');
        Csrf::verifyRequestOrFail();

        $id = (int) $params['id'];
        // ON DELETE SET NULL على parent_id يكفل عدم فقدان أبناء العقدة المحذوفة، بل تصبح جذورًا مستقلة.
        Database::delete('tree_nodes', ['id' => $id]);
        ActivityLog::record('tree_node_delete', 'حذف عقدة نسب رقم: ' . $id);
        Session::flash('success', 'تم حذف العقدة.');
        Response::redirect(Url::admin('tree-nodes'));
    }

    private function allNodes(): array
    {
        return Database::fetchAll('SELECT * FROM ' . Database::table('tree_nodes'));
    }

    private function branchOptions(): array
    {
        if (!Database::tableExists('branches')) {
            return [];
        }
        return Database::fetchAll('SELECT id, name FROM ' . Database::table('branches') . ' ORDER BY sort_order ASC, id ASC');
    }
}
