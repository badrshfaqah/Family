<?php

namespace Core\Admin\Controllers;

use Core\Auth;
use Core\Database;
use Core\View;

/** شاشة الإحصائيات: الزيارات والزوار يوميًا وأسبوعيًا، وأكثر الصفحات دخولًا، والمصادر */
final class StatsController
{
    public function index(array $params): void
    {
        Auth::requirePermission('reports.view');

        $t = Database::table('page_views');
        $exists = Database::tableExists('page_views');

        $range = function (string $from, string $to) use ($t): array {
            return Database::fetchOne(
                "SELECT COUNT(*) AS views, COUNT(DISTINCT visitor) AS visitors
                 FROM {$t} WHERE viewed_on BETWEEN ? AND ?",
                [$from, $to]
            ) ?: ['views' => 0, 'visitors' => 0];
        };

        $today = date('Y-m-d');
        $cards = $exists ? [
            'today' => $range($today, $today),
            'yesterday' => $range(date('Y-m-d', strtotime('-1 day')), date('Y-m-d', strtotime('-1 day'))),
            'week' => $range(date('Y-m-d', strtotime('-6 days')), $today),
            'month' => $range(date('Y-m-d', strtotime('-29 days')), $today),
            'total' => Database::fetchOne("SELECT COUNT(*) AS views, COUNT(DISTINCT CONCAT(visitor, viewed_on)) AS visitors FROM {$t}") ?: ['views' => 0, 'visitors' => 0],
        ] : null;

        // آخر 14 يومًا: زيارات وزوار لكل يوم (مع تعبئة الأيام الفارغة بصفر)
        $daily = [];
        if ($exists) {
            $rows = Database::fetchAll(
                "SELECT viewed_on, COUNT(*) AS views, COUNT(DISTINCT visitor) AS visitors
                 FROM {$t} WHERE viewed_on >= ?
                 GROUP BY viewed_on",
                [date('Y-m-d', strtotime('-13 days'))]
            );
            $byDay = [];
            foreach ($rows as $r) {
                $byDay[$r['viewed_on']] = $r;
            }
            for ($i = 13; $i >= 0; $i--) {
                $d = date('Y-m-d', strtotime("-{$i} days"));
                $daily[] = [
                    'date' => $d,
                    'views' => (int) ($byDay[$d]['views'] ?? 0),
                    'visitors' => (int) ($byDay[$d]['visitors'] ?? 0),
                ];
            }
        }

        $since30 = date('Y-m-d', strtotime('-29 days'));
        $topPages = $exists ? Database::fetchAll(
            "SELECT path, COUNT(*) AS views, COUNT(DISTINCT visitor) AS visitors
             FROM {$t} WHERE viewed_on >= ?
             GROUP BY path ORDER BY views DESC LIMIT 12",
            [$since30]
        ) : [];

        $referrers = $exists ? Database::fetchAll(
            "SELECT referrer, COUNT(*) AS views FROM {$t}
             WHERE viewed_on >= ? AND referrer IS NOT NULL
             GROUP BY referrer ORDER BY views DESC LIMIT 6",
            [$since30]
        ) : [];

        $devices = ['mobile' => 0, 'desktop' => 0];
        if ($exists) {
            foreach (Database::fetchAll("SELECT device, COUNT(*) AS c FROM {$t} WHERE viewed_on >= ? GROUP BY device", [$since30]) as $r) {
                $devices[$r['device']] = (int) $r['c'];
            }
        }

        echo View::render(CORE_PATH . '/Admin/Views/layouts/admin.php', [
            'pageTitle' => 'الإحصائيات',
            'contentView' => CORE_PATH . '/Admin/Views/stats/index.php',
            'tracking' => $exists,
            'cards' => $cards,
            'daily' => $daily,
            'topPages' => $topPages,
            'referrers' => $referrers,
            'devices' => $devices,
        ]);
    }
}
