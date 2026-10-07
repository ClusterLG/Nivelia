<?php
require_once __DIR__ . '/../config/db.php';
$pdo = getDB();

echo "Iniciando limpieza: Dejar solo octubre de 2026 (Semanas 1 a 4)...\n";

$pdo->beginTransaction();
try {
    // 1. Eliminar preguntas de actividades de semanas > 4
    $stmtQ = $pdo->exec("
        DELETE aq FROM activity_questions aq 
        INNER JOIN activities a ON aq.activity_id = a.id 
        WHERE a.week_number > 4
    ");
    echo "Preguntas eliminadas de semanas > 4: $stmtQ\n";

    // 2. Eliminar actividades de semanas > 4
    $stmtA = $pdo->exec("DELETE FROM activities WHERE week_number > 4");
    echo "Actividades eliminadas de semanas > 4: $stmtA\n";

    // 3. Eliminar planes semanales de semanas > 4
    $stmtP = $pdo->exec("DELETE FROM weekly_plans WHERE week_number > 4");
    echo "Planes semanales eliminados de semanas > 4: $stmtP\n";

    // 4. Eliminar preguntas de material_bank de semanas > 4
    $stmtMBQ = $pdo->exec("
        DELETE mbq FROM material_bank_questions mbq 
        INNER JOIN material_bank mb ON mbq.material_id = mb.id 
        WHERE mb.absolute_week > 4
    ");
    echo "Preguntas de material_bank eliminadas de semanas > 4: $stmtMBQ\n";

    // 5. Eliminar material_bank de semanas > 4
    $stmtMB = $pdo->exec("DELETE FROM material_bank WHERE absolute_week > 4");
    echo "Material_bank eliminadas de semanas > 4: $stmtMB\n";

    // 6. Asegurar que las semanas activas estén dentro de octubre (Semana 1)
    setSystemSetting('active_week_5to', 1);
    setSystemSetting('active_week_6to', 1);
    setSystemSetting('current_lapso', 1);

    $pdo->commit();
    echo "✅ Limpieza completada con éxito. Solo queda disponible Octubre 2026 (Semanas 1 a 4).\n";
} catch (Exception $e) {
    $pdo->rollBack();
    echo "❌ Error durante la limpieza: " . $e->getMessage() . "\n";
}
