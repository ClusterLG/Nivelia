<?php
require_once __DIR__ . '/../config/db.php';
$pdo = getDB();

echo "--- weekly_plans indexes ---\n";
foreach ($pdo->query("SHOW INDEX FROM weekly_plans")->fetchAll(PDO::FETCH_ASSOC) as $idx) {
    echo "Key: {$idx['Key_name']}, Column: {$idx['Column_name']}, Unique: " . ($idx['Non_unique'] ? 'No' : 'Yes') . "\n";
}

echo "\n--- activities indexes ---\n";
foreach ($pdo->query("SHOW INDEX FROM activities")->fetchAll(PDO::FETCH_ASSOC) as $idx) {
    echo "Key: {$idx['Key_name']}, Column: {$idx['Column_name']}, Unique: " . ($idx['Non_unique'] ? 'No' : 'Yes') . "\n";
}
