<?php

namespace Modules\FamilyTree;

/**
 * أدوات مشتركة لترتيب عقد شجرة النسب هرميًا (تُستخدم في لوحة التحكم وفي الموقع العام).
 * تعتمد على قائمة مسطّحة من العقد (id, parent_id, sort_order, ...) وتبنى الترتيب في PHP
 * بدل استعلامات متكررة، لأن العمق غير محدود ولا داعي لتعقيد الاستعلام بـ CTE.
 */
final class TreeHelper
{
    /**
     * يحوّل قائمة العقد المسطّحة إلى قائمة مرتّبة هرميًا (DFS) مع إضافة مفتاح depth لكل عقدة.
     * $nodes: مصفوفة عناصر تحوي على الأقل id و parent_id و sort_order.
     */
    public static function flatten(array $nodes, ?int $parentId = null, int $depth = 0): array
    {
        $children = array_values(array_filter($nodes, function ($n) use ($parentId) {
            $p = $n['parent_id'] !== null && $n['parent_id'] !== '' ? (int) $n['parent_id'] : null;
            return $p === $parentId;
        }));

        usort($children, function ($a, $b) {
            return ((int) $a['sort_order'] <=> (int) $b['sort_order']) ?: ((int) $a['id'] <=> (int) $b['id']);
        });

        $result = [];
        foreach ($children as $child) {
            $child['depth'] = $depth;
            $result[] = $child;
            $result = array_merge($result, self::flatten($nodes, (int) $child['id'], $depth + 1));
        }

        return $result;
    }

    /** يجمع معرّفات كل الأحفاد (المتحدرين) لعقدة معيّنة، لمنع اختيارها كأب لأحد أسلافها (حلقة مفرغة). */
    public static function descendantIds(array $nodes, int $id): array
    {
        $ids = [];
        foreach ($nodes as $n) {
            $p = $n['parent_id'] !== null && $n['parent_id'] !== '' ? (int) $n['parent_id'] : null;
            if ($p === $id) {
                $ids[] = (int) $n['id'];
                $ids = array_merge($ids, self::descendantIds($nodes, (int) $n['id']));
            }
        }
        return $ids;
    }
}
