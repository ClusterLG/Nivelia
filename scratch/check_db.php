<?php
require_once __DIR__ . '/../config/db.php';
try {
    $pdo = getDB();
    echo "Connected successfully to " . DB_NAME . "\n";
    $tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
    foreach ($tables as $t) {
        $cnt = $pdo->query("SELECT COUNT(*) FROM `$t`")->fetchColumn();
        echo "$t: $cnt\n";
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
