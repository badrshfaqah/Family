<?php

namespace Modules\FamilyTree\Front\Controllers;

use Core\Database;
use Core\Settings;
use Core\Support\Request;
use Core\Terms;
use Core\View;
use Modules\FamilyTree\TreeHelper;

final class FamilyTreeFrontController
{
    public function index(array $params): void
    {
        // نجلب كل العقد (وليس الظاهرة فقط) لبناء الترتيب الهرمي الصحيح بعمقه الحقيقي،
        // ثم نستبعد العقد المخفية من العرض بعد ذلك. لو صُفّيت العقد المخفية قبل البناء
        // الهرمي، فإن أي عقدة ظاهرة أبوها مخفي كانت ستُفقد من الشجرة تمامًا (يتيم بلا جذر).
        $allNodes = Database::fetchAll('SELECT * FROM ' . Database::table('tree_nodes'));
        $ordered = array_values(array_filter(
            TreeHelper::flatten($allNodes),
            fn($n) => (int) $n['is_visible'] === 1
        ));

        // الفروع التي لها صفحة عامة فعليًا، لربط اسم العقدة بصفحة الفرع عند توفره.
        $publicBranchIds = [];
        if (Database::tableExists('branches')) {
            $rows = Database::fetchAll(
                'SELECT id FROM ' . Database::table('branches') . " WHERE show_public_page = 1 AND status = 'active'"
            );
            $publicBranchIds = array_map(fn($r) => (int) $r['id'], $rows);
        }

        $viewMode = Request::get('view', 'tree') === 'sequential' ? 'sequential' : 'tree';

        $layout = CORE_PATH . '/Front/Views/layouts/main.php';
        $view = __DIR__ . '/../Views/index.php';

        echo View::renderLayout($layout, $view, [
            'ordered' => $ordered,
            'publicBranchIds' => $publicBranchIds,
            'viewMode' => $viewMode,
            'pageTitle' => Terms::phrase('tree'),
            'metaDescription' => Settings::get('seo_default_description', ''),
        ]);
    }
}
