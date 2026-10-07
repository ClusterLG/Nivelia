<?php
require_once __DIR__ . '/../config/db.php';
$pdo = getDB();

echo "Activities <= 4: " . $pdo->query("SELECT COUNT(*) FROM activities WHERE week_number <= 4")->fetchColumn() . "\n";
echo "Activities > 4: " . $pdo->query("SELECT COUNT(*) FROM activities WHERE week_number > 4")->fetchColumn() . "\n";

echo "Weekly Plans <= 4: " . $pdo->query("SELECT COUNT(*) FROM weekly_plans WHERE week_number <= 4")->fetchColumn() . "\n";
echo "Weekly Plans > 4: " . $pdo->query("SELECT COUNT(*) FROM weekly_plans WHERE week_number > 4")->fetchColumn() . "\n";

echo "Material Bank <= 4: " . $pdo->query("SELECT COUNT(*) FROM material_bank WHERE absolute_week <= 4")->fetchColumn() . "\n";
echo "Material Bank > 4: " . $pdo->query("SELECT COUNT(*) FROM material_bank WHERE absolute_week > 4")->fetchColumn() . "\n";
