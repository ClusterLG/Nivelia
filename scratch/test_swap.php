<?php
require_once __DIR__ . '/../config/db.php';
$pdo = getDB();

function testSwap($grade, $weekA, $weekB) {
    global $pdo;
    $pdo->beginTransaction();
    try {
        $lapsoA = (int)ceil($weekA / 10);
        $lapsoB = (int)ceil($weekB / 10);
        $tempWeek = 9999;

        // Step 1: Move week A to temp
        $stmtP1 = $pdo->prepare("UPDATE weekly_plans SET week_number = :t WHERE week_number = :a AND grade = :g");
        $stmtP1->execute(['t' => $tempWeek, 'a' => $weekA, 'g' => $grade]);

        $stmtA1 = $pdo->prepare("UPDATE activities SET week_number = :t WHERE week_number = :a AND grade = :g");
        $stmtA1->execute(['t' => $tempWeek, 'a' => $weekA, 'g' => $grade]);

        // Step 2: Move week B to week A
        $stmtP2 = $pdo->prepare("UPDATE weekly_plans SET week_number = :a, lapso = :la WHERE week_number = :b AND grade = :g");
        $stmtP2->execute(['a' => $weekA, 'la' => $lapsoA, 'b' => $weekB, 'g' => $grade]);

        $stmtA2 = $pdo->prepare("UPDATE activities SET week_number = :a, lapso = :la WHERE week_number = :b AND grade = :g");
        $stmtA2->execute(['a' => $weekA, 'la' => $lapsoA, 'b' => $weekB, 'g' => $grade]);

        // Step 3: Move temp (former week A) to week B
        $stmtP3 = $pdo->prepare("UPDATE weekly_plans SET week_number = :b, lapso = :lb WHERE week_number = :t AND grade = :g");
        $stmtP3->execute(['b' => $weekB, 'lb' => $lapsoB, 't' => $tempWeek, 'g' => $grade]);

        $stmtA3 = $pdo->prepare("UPDATE activities SET week_number = :b, lapso = :lb WHERE week_number = :t AND grade = :g");
        $stmtA3->execute(['b' => $weekB, 'lb' => $lapsoB, 't' => $tempWeek, 'g' => $grade]);

        $pdo->commit();
        return true;
    } catch (Exception $e) {
        $pdo->rollBack();
        echo "Error in testSwap: " . $e->getMessage() . "\n";
        return false;
    }
}

// Test swap week 1 and 2 for grade 5, then swap back
echo "Testing swap for Grade 5 between week 1 and 2...\n";
$res1 = testSwap(5, 1, 2);
echo "Swap 1->2 result: " . ($res1 ? "SUCCESS" : "FAILED") . "\n";

$res2 = testSwap(5, 1, 2);
echo "Swap back 2->1 result: " . ($res2 ? "SUCCESS" : "FAILED") . "\n";
