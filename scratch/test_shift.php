<?php
require_once __DIR__ . '/../config/db.php';
$pdo = getDB();

function shiftWeeklyBlocks($grade, $fromWeek, $toWeek) {
    global $pdo;
    if ($fromWeek == $toWeek) return true;
    
    $pdo->beginTransaction();
    try {
        $tempWeek = 9999;
        // Step 1: Move fromWeek to temp
        $pdo->prepare("UPDATE weekly_plans SET week_number = :t WHERE week_number = :f AND grade = :g")
            ->execute(['t' => $tempWeek, 'f' => $fromWeek, 'g' => $grade]);
        $pdo->prepare("UPDATE activities SET week_number = :t WHERE week_number = :f AND grade = :g")
            ->execute(['t' => $tempWeek, 'f' => $fromWeek, 'g' => $grade]);

        if ($fromWeek < $toWeek) {
            // Shift intermediate weeks down by 1 (e.g., fromWeek+1 .. toWeek become fromWeek .. toWeek-1)
            for ($w = $fromWeek + 1; $w <= $toWeek; $w++) {
                $target = $w - 1;
                $lapso = (int)ceil($target / 10);
                $pdo->prepare("UPDATE weekly_plans SET week_number = :target, lapso = :lapso WHERE week_number = :w AND grade = :g")
                    ->execute(['target' => $target, 'lapso' => $lapso, 'w' => $w, 'g' => $grade]);
                $pdo->prepare("UPDATE activities SET week_number = :target, lapso = :lapso WHERE week_number = :w AND grade = :g")
                    ->execute(['target' => $target, 'lapso' => $lapso, 'w' => $w, 'g' => $grade]);
            }
        } else {
            // Shift intermediate weeks up by 1 (e.g., fromWeek-1 down to toWeek become fromWeek down to toWeek+1)
            for ($w = $fromWeek - 1; $w >= $toWeek; $w--) {
                $target = $w + 1;
                $lapso = (int)ceil($target / 10);
                $pdo->prepare("UPDATE weekly_plans SET week_number = :target, lapso = :lapso WHERE week_number = :w AND grade = :g")
                    ->execute(['target' => $target, 'lapso' => $lapso, 'w' => $w, 'g' => $grade]);
                $pdo->prepare("UPDATE activities SET week_number = :target, lapso = :lapso WHERE week_number = :w AND grade = :g")
                    ->execute(['target' => $target, 'lapso' => $lapso, 'w' => $w, 'g' => $grade]);
            }
        }

        // Step 3: Put temp into toWeek
        $lapsoFinal = (int)ceil($toWeek / 10);
        $pdo->prepare("UPDATE weekly_plans SET week_number = :to, lapso = :lapso WHERE week_number = :t AND grade = :g")
            ->execute(['to' => $toWeek, 'lapso' => $lapsoFinal, 't' => $tempWeek, 'g' => $grade]);
        $pdo->prepare("UPDATE activities SET week_number = :to, lapso = :lapso WHERE week_number = :t AND grade = :g")
            ->execute(['to' => $toWeek, 'lapso' => $lapsoFinal, 't' => $tempWeek, 'g' => $grade]);

        $pdo->commit();
        return true;
    } catch (Exception $e) {
        $pdo->rollBack();
        echo "Error in shift: " . $e->getMessage() . "\n";
        return false;
    }
}

// Test shifting week 1 to 3, then 3 to 1 for grade 5
echo "Testing shift 1->3 for Grade 5...\n";
$res1 = shiftWeeklyBlocks(5, 1, 3);
echo "Shift 1->3 result: " . ($res1 ? "SUCCESS" : "FAILED") . "\n";

$res2 = shiftWeeklyBlocks(5, 3, 1);
echo "Shift 3->1 result: " . ($res2 ? "SUCCESS" : "FAILED") . "\n";
