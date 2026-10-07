<?php
require_once __DIR__ . '/../config/db.php';
$pdo = getDB();

echo "Activities count by week:\n";
$rows = $pdo->query("SELECT week_number, COUNT(*) as c FROM activities GROUP BY week_number ORDER BY week_number ASC")->fetchAll(PDO::FETCH_ASSOC);
foreach ($rows as $r) {
    echo "Week {$r['week_number']}: {$r['c']} activities\n";
}

echo "\nWeekly plans count by week:\n";
$plans = $pdo->query("SELECT week_number, COUNT(*) as c FROM weekly_plans GROUP BY week_number ORDER BY week_number ASC")->fetchAll(PDO::FETCH_ASSOC);
foreach ($plans as $p) {
    echo "Week {$p['week_number']}: {$p['c']} plans\n";
}
