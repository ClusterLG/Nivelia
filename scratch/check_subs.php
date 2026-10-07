<?php
require_once __DIR__ . '/../config/db.php';
$pdo = getDB();
$subs = $pdo->query("SELECT sub.*, a.title, a.week_number FROM student_submissions sub JOIN activities a ON sub.activity_id = a.id")->fetchAll(PDO::FETCH_ASSOC);
print_r($subs);
