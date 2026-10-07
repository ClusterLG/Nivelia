<?php
require_once __DIR__ . '/../config/db.php';
$pdo = getDB();

echo "--- USERS ---\n";
$users = $pdo->query("SELECT id, name, email, role, grade FROM users")->fetchAll(PDO::FETCH_ASSOC);
print_r($users);

echo "--- SYSTEM SETTINGS ---\n";
$settings = $pdo->query("SELECT * FROM system_settings")->fetchAll(PDO::FETCH_ASSOC);
print_r($settings);

echo "--- WEEKS DISTRIBUTION IN ACTIVITIES ---\n";
$weeks = $pdo->query("SELECT week_number, lapso, COUNT(*) as c FROM activities GROUP BY week_number, lapso ORDER BY week_number ASC LIMIT 15")->fetchAll(PDO::FETCH_ASSOC);
print_r($weeks);
