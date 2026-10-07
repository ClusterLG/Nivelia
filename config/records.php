<?php
/**
 * Sistema de Registro y Archivo en Disco
 * Guarda todas las respuestas, evaluaciones e historial en la carpeta 'respuestas/'
 */

require_once __DIR__ . '/db.php';

function getResponsesBaseDir() {
    $dir = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'respuestas';
    if (!is_dir($dir)) {
        mkdir($dir, 0777, true);
    }
    return $dir;
}

/**
 * Sanitizar texto para nombres de archivo y carpetas
 */
function sanitizeFileName($str) {
    $str = iconv('UTF-8', 'ASCII//TRANSLIT', $str);
    $str = preg_replace('/[^A-Za-z0-9_\-]/', '_', $str);
    $str = preg_replace('/_+/', '_', $str);
    return trim($str, '_');
}

/**
 * Guarda o actualiza el archivo físico de una entrega en respuestas/[Alumno]/
 */
function recordSubmissionToDisk($submissionId, $pdo) {
    $stmt = $pdo->prepare("
        SELECT sub.*, 
               u.name as student_name, u.email as student_email, u.grade as student_grade,
               a.title as activity_title, a.day_of_week, a.xp_reward, a.summary as activity_summary,
               s.name as subject_name, s.slug as subject_slug
        FROM student_submissions sub
        JOIN users u ON sub.user_id = u.id
        JOIN activities a ON sub.activity_id = a.id
        JOIN subjects s ON a.subject_id = s.id
        WHERE sub.id = :id
    ");
    $stmt->execute(['id' => $submissionId]);
    $sub = $stmt->fetch();
    if (!$sub) return false;

    // Obtener preguntas y respuestas de esta entrega
    $stmtAns = $pdo->prepare("
        SELECT sa.*, q.question_text, q.question_type, q.correct_answer, q.explanation, q.points as max_points
        FROM submission_answers sa
        JOIN activity_questions q ON sa.question_id = q.id
        WHERE sa.submission_id = :sid
        ORDER BY q.id ASC
    ");
    $stmtAns->execute(['sid' => $submissionId]);
    $answers = $stmtAns->fetchAll();

    // Crear carpeta del alumno: respuestas/[Nombre]/
    $studentFolder = getResponsesBaseDir() . DIRECTORY_SEPARATOR . sanitizeFileName($sub['student_name']);
    if (!is_dir($studentFolder)) {
        mkdir($studentFolder, 0777, true);
    }

    // Nombre base del archivo: YYYY-MM-DD_dia_materia_titulo
    $datePart = date('Y-m-d', strtotime($sub['submitted_at']));
    $dayPart = sanitizeFileName($sub['day_of_week']);
    $subjectPart = sanitizeFileName($sub['subject_slug']);
    $titlePart = substr(sanitizeFileName($sub['activity_title']), 0, 35);
    $baseFileName = "{$datePart}_{$dayPart}_{$subjectPart}_{$titlePart}";

    // 1. Guardar como JSON estructurado
    $jsonData = [
        'submission_id' => $sub['id'],
        'student' => [
            'name' => $sub['student_name'],
            'email' => $sub['student_email'],
            'grade' => $sub['student_grade']
        ],
        'activity' => [
            'title' => $sub['activity_title'],
            'subject' => $sub['subject_name'],
            'day_of_week' => $sub['day_of_week'],
            'xp_reward' => $sub['xp_reward']
        ],
        'submitted_at' => $sub['submitted_at'],
        'score' => $sub['score'],
        'max_score' => $sub['max_score'],
        'percentage' => ($sub['max_score'] > 0) ? round(($sub['score'] / $sub['max_score']) * 100, 1) : 0,
        'status' => $sub['status'],
        'evaluation' => [
            'parent_stars' => $sub['parent_stars'],
            'parent_feedback' => $sub['parent_feedback'],
            'graded_at' => $sub['graded_at']
        ],
        'answers' => array_map(function($a) {
            return [
                'question' => $a['question_text'],
                'type' => $a['question_type'],
                'student_answer' => $a['answer_text'],
                'is_correct' => $a['is_correct'],
                'points_awarded' => $a['points_awarded'],
                'max_points' => $a['max_points'],
                'expected_answer' => $a['correct_answer'],
                'explanation' => $a['explanation']
            ];
        }, $answers)
    ];

    $jsonFilePath = $studentFolder . DIRECTORY_SEPARATOR . $baseFileName . '.json';
    file_put_contents($jsonFilePath, json_encode($jsonData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

    // 2. Guardar como Reporte Legible (Markdown / Texto)
    $md = "# 📋 Registro de Actividad Académica\n\n";
    $md .= "**Estudiante:** " . $sub['student_name'] . " (" . $sub['student_grade'] . "to Grado)\n";
    $md .= "**Materia:** " . $sub['subject_name'] . "\n";
    $md .= "**Misión:** " . $sub['activity_title'] . "\n";
    $md .= "**Día Escolar:** " . ucfirst($sub['day_of_week']) . "\n";
    $md .= "**Fecha de Entrega:** " . $sub['submitted_at'] . "\n\n";
    $md .= "---\n\n";
    $md .= "## 🎯 Puntuación Obtenida\n";
    $md .= "- **Puntaje:** " . $sub['score'] . " / " . $sub['max_score'] . " puntos (" . $jsonData['percentage'] . "%)\n";
    $md .= "- **Recompensa XP:** +" . $sub['xp_reward'] . " XP\n\n";

    if ($sub['parent_stars'] > 0 || !empty($sub['parent_feedback'])) {
        $md .= "## ⭐ Evaluación y Comentarios de Papá\n";
        if ($sub['parent_stars'] > 0) {
            $md .= "- **Estrellas otorgadas:** " . str_repeat('⭐', $sub['parent_stars']) . " (" . $sub['parent_stars'] . "/5)\n";
        }
        if (!empty($sub['parent_feedback'])) {
            $md .= "- **Mensaje de Papá:** \"" . $sub['parent_feedback'] . "\"\n";
        }
        $md .= "- **Fecha de Calificación:** " . ($sub['graded_at'] ?? 'Pendiente') . "\n\n";
    }

    $md .= "---\n\n";
    $md .= "## ✍️ Detalle de Preguntas y Respuestas\n\n";
    foreach ($answers as $idx => $ans) {
        $num = $idx + 1;
        $md .= "### Pregunta {$num}: " . $ans['question_text'] . "\n";
        $md .= "- **Respuesta de {$sub['student_name']}:** " . $ans['answer_text'] . "\n";
        
        if ($ans['is_correct'] === 1) {
            $md .= "- **Resultado:** ✅ Correcto (+{$ans['points_awarded']} pts)\n";
        } elseif ($ans['is_correct'] === 0) {
            $md .= "- **Resultado:** ❌ Incorrecto (Esperado: {$ans['correct_answer']})\n";
        } else {
            $md .= "- **Resultado:** 📝 Respuesta abierta (+{$ans['points_awarded']} pts asignados)\n";
        }

        if (!empty($ans['explanation'])) {
            $md .= "- *Explicación didáctica:* {$ans['explanation']}\n";
        }
        $md .= "\n";
    }

    $mdFilePath = $studentFolder . DIRECTORY_SEPARATOR . $baseFileName . '.md';
    file_put_contents($mdFilePath, $md);

    // 3. Actualizar resumen global del alumno
    updateStudentSummaryFile($sub['user_id'], $sub['student_name'], $pdo);

    return true;
}

/**
 * Mantiene un resumen acumulativo actualizado para cada alumno
 */
function updateStudentSummaryFile($userId, $studentName, $pdo) {
    $studentFolder = getResponsesBaseDir() . DIRECTORY_SEPARATOR . sanitizeFileName($studentName);
    if (!is_dir($studentFolder)) {
        mkdir($studentFolder, 0777, true);
    }

    $stmt = $pdo->prepare("
        SELECT sub.*, a.title, a.day_of_week, s.name as subject_name
        FROM student_submissions sub
        JOIN activities a ON sub.activity_id = a.id
        JOIN subjects s ON a.subject_id = s.id
        WHERE sub.user_id = :uid
        ORDER BY sub.submitted_at ASC
    ");
    $stmt->execute(['uid' => $userId]);
    $allSubs = $stmt->fetchAll();

    $totalActs = count($allSubs);
    $totalScore = array_sum(array_column($allSubs, 'score'));
    $totalMax = array_sum(array_column($allSubs, 'max_score'));
    $overallPct = ($totalMax > 0) ? round(($totalScore / $totalMax) * 100, 1) : 0;

    $summaryMd = "# 📊 Historial y Resumen Académico: {$studentName}\n\n";
    $summaryMd .= "- **Misiones Completadas:** {$totalActs}\n";
    $summaryMd .= "- **Puntaje Total Acumulado:** {$totalScore} / {$totalMax} pts\n";
    $summaryMd .= "- **Promedio de Rendimiento:** {$overallPct}%\n";
    $summaryMd .= "- **Última actualización:** " . date('Y-m-d H:i:s') . "\n\n";
    $summaryMd .= "## Registro Cronológico de Entregas\n\n";
    $summaryMd .= "| Fecha | Día | Materia | Misión | Puntaje | Estrellas |\n";
    $summaryMd .= "| :--- | :--- | :--- | :--- | :--- | :--- |\n";

    foreach ($allSubs as $s) {
        $stars = ($s['parent_stars'] > 0) ? str_repeat('⭐', $s['parent_stars']) : 'Pendiente';
        $summaryMd .= "| " . date('Y-m-d', strtotime($s['submitted_at'])) . " | " . ucfirst($s['day_of_week']) . " | " . $s['subject_name'] . " | " . $s['title'] . " | " . $s['score'] . "/" . $s['max_score'] . " | " . $stars . " |\n";
    }

    file_put_contents($studentFolder . DIRECTORY_SEPARATOR . 'RESUMEN_HISTORIAL.md', $summaryMd);
}
