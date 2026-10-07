<?php
/**
 * Script Ejecutor de Población de Base de Datos Curricular
 * Carga 5to y 6to Grado de Primaria
 * 3 Lapsos x 10 Semanas cada uno = 30 Semanas completas (300 misiones evaluables)
 */

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/curriculum_data_5to.php';
require_once __DIR__ . '/curriculum_data_6to.php';

$pdo = getDB();

echo "========================================================\n";
echo "INICIANDO POBLACIÓN DEL BANCO DE MATERIALES DE ESTUDIO\n";
echo "========================================================\n";

// 1. Crear / Asegurar Tablas y Columnas
echo "1. Verificando y creando estructura de tablas...\n";

$pdo->exec("
CREATE TABLE IF NOT EXISTS material_bank (
  id INT AUTO_INCREMENT PRIMARY KEY,
  grade INT NOT NULL,
  lapso TINYINT NOT NULL,
  lapso_week INT NOT NULL,
  absolute_week INT NOT NULL,
  day_of_week ENUM('lunes','martes','miercoles','jueves','viernes') NOT NULL,
  subject_id INT NOT NULL,
  title VARCHAR(255) NOT NULL,
  summary VARCHAR(255) NOT NULL,
  daily_objective TEXT NOT NULL,
  reading_content LONGTEXT NOT NULL,
  practical_exercise TEXT NULL,
  xp_reward INT DEFAULT 50,
  order_num INT DEFAULT 1,
  is_deployed TINYINT(1) DEFAULT 1,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uq_bank_grade_week_day_order (grade, absolute_week, day_of_week, order_num),
  FOREIGN KEY (subject_id) REFERENCES subjects(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
");

$pdo->exec("
CREATE TABLE IF NOT EXISTS material_bank_questions (
  id INT AUTO_INCREMENT PRIMARY KEY,
  material_id INT NOT NULL,
  question_text TEXT NOT NULL,
  question_type ENUM('multiple_choice','true_false','short_answer','reflexive') NOT NULL DEFAULT 'multiple_choice',
  options_json LONGTEXT NULL,
  correct_answer TEXT NOT NULL,
  explanation TEXT NULL,
  points INT DEFAULT 10,
  FOREIGN KEY (material_id) REFERENCES material_bank(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
");

// Agregar columna lapso si no existe
try {
    $pdo->exec("ALTER TABLE activities ADD COLUMN lapso TINYINT NOT NULL DEFAULT 1 AFTER week_number");
} catch (Exception $e) {}

try {
    $pdo->exec("ALTER TABLE weekly_plans ADD COLUMN lapso TINYINT NOT NULL DEFAULT 1 AFTER week_number");
} catch (Exception $e) {}

echo "   -> Tablas listas.\n";

// 2. Cargar Datos en material_bank y material_bank_questions
echo "2. Cargando 5to y 6to Grado en material_bank...\n";

$curriculums = [
    5 => getCurriculum5to(),
    6 => getCurriculum6to()
];

$stmtInsertBank = $pdo->prepare("
    INSERT INTO material_bank 
      (grade, lapso, lapso_week, absolute_week, day_of_week, subject_id, title, summary, daily_objective, reading_content, practical_exercise, xp_reward, order_num, is_deployed)
    VALUES 
      (:grade, :lapso, :lweek, :aweek, :day, :sub, :title, :summary, :obj, :reading, :pract, :xp, 1, 1)
    ON DUPLICATE KEY UPDATE 
      lapso = VALUES(lapso),
      lapso_week = VALUES(lapso_week),
      subject_id = VALUES(subject_id),
      title = VALUES(title),
      summary = VALUES(summary),
      daily_objective = VALUES(daily_objective),
      reading_content = VALUES(reading_content),
      practical_exercise = VALUES(practical_exercise),
      xp_reward = VALUES(xp_reward)
");

$stmtInsertBankQ = $pdo->prepare("
    INSERT INTO material_bank_questions
      (material_id, question_text, question_type, options_json, correct_answer, explanation, points)
    VALUES
      (:mid, :qtext, :qtype, :opts, :corr, :expl, :pts)
");

$stmtCheckBank = $pdo->prepare("SELECT id FROM material_bank WHERE grade = :g AND absolute_week = :w AND day_of_week = :d AND order_num = 1");

$totalBankMissions = 0;
$totalBankQuestions = 0;

foreach ($curriculums as $grade => $weeks) {
    foreach ($weeks as $wNum => $wData) {
        $lapso = $wData['lapso'];
        $lapsoWeek = $wData['lapso_week'];

        foreach ($wData['days'] as $day => $dInfo) {
            $stmtInsertBank->execute([
                'grade' => $grade,
                'lapso' => $lapso,
                'lweek' => $lapsoWeek,
                'aweek' => $wNum,
                'day' => $day,
                'sub' => $dInfo['subject_id'],
                'title' => $dInfo['title'],
                'summary' => $dInfo['summary'],
                'obj' => $dInfo['objective'],
                'reading' => $dInfo['reading'],
                'pract' => $dInfo['exercise'],
                'xp' => $dInfo['xp']
            ]);

            $stmtCheckBank->execute(['g' => $grade, 'w' => $wNum, 'd' => $day]);
            $bankId = $stmtCheckBank->fetchColumn();

            // Limpiar preguntas previas de este material y reinsertar
            if ($bankId) {
                $pdo->prepare("DELETE FROM material_bank_questions WHERE material_id = :mid")->execute(['mid' => $bankId]);

                foreach ($dInfo['questions'] as $q) {
                    $optsJson = !empty($q['options']) ? json_encode($q['options'], JSON_UNESCAPED_UNICODE) : null;
                    $stmtInsertBankQ->execute([
                        'mid' => $bankId,
                        'qtext' => $q['text'],
                        'qtype' => $q['type'],
                        'opts' => $optsJson,
                        'corr' => $q['correct'],
                        'expl' => $q['explanation'],
                        'pts' => $q['points']
                    ]);
                    $totalBankQuestions++;
                }
            }
            $totalBankMissions++;
        }
    }
}

echo "   -> $totalBankMissions misiones y $totalBankQuestions preguntas cargadas en material_bank.\n";

// 3. Sincronizar hacia weekly_plans, activities y activity_questions
echo "3. Sincronizando hacia el motor operativo escolar (weekly_plans y activities)...\n";

$stmtPlan = $pdo->prepare("
    INSERT INTO weekly_plans (week_number, lapso, grade, day_of_week, objective, is_active)
    VALUES (:w, :lapso, :g, :d, :obj, 1)
    ON DUPLICATE KEY UPDATE 
      lapso = VALUES(lapso),
      objective = VALUES(objective)
");

$stmtAct = $pdo->prepare("
    SELECT id FROM activities 
    WHERE week_number = :w AND grade = :g AND day_of_week = :d AND title = :title
");

$stmtInsertAct = $pdo->prepare("
    INSERT INTO activities 
      (week_number, lapso, grade, day_of_week, subject_id, title, summary, reading_content, practical_exercise, xp_reward, order_num, is_active)
    VALUES 
      (:w, :lapso, :g, :d, :sub, :title, :summary, :reading, :pract, :xp, :ord, 1)
");

$stmtUpdateAct = $pdo->prepare("
    UPDATE activities SET
      lapso = :lapso,
      subject_id = :sub,
      summary = :summary,
      reading_content = :reading,
      practical_exercise = :pract,
      xp_reward = :xp
    WHERE id = :id
");

$stmtInsertQ = $pdo->prepare("
    INSERT INTO activity_questions
      (activity_id, question_text, question_type, options_json, correct_answer, explanation, points)
    VALUES
      (:aid, :qtext, :qtype, :opts, :corr, :expl, :pts)
");

$totalActivitiesLoaded = 0;
$totalPlansLoaded = 0;

foreach ($curriculums as $grade => $weeks) {
    foreach ($weeks as $wNum => $wData) {
        $lapso = $wData['lapso'];

        foreach ($wData['days'] as $day => $dInfo) {
            // Plan diario
            $stmtPlan->execute([
                'w' => $wNum,
                'lapso' => $lapso,
                'g' => $grade,
                'd' => $day,
                'obj' => $dInfo['objective']
            ]);
            $totalPlansLoaded++;

            // Actividad
            $stmtAct->execute([
                'w' => $wNum,
                'g' => $grade,
                'd' => $day,
                'title' => $dInfo['title']
            ]);
            $existingActId = $stmtAct->fetchColumn();

            if ($existingActId) {
                // Actualizar
                $stmtUpdateAct->execute([
                    'lapso' => $lapso,
                    'sub' => $dInfo['subject_id'],
                    'summary' => $dInfo['summary'],
                    'reading' => $dInfo['reading'],
                    'pract' => $dInfo['exercise'],
                    'xp' => $dInfo['xp'],
                    'id' => $existingActId
                ]);
                $actId = $existingActId;
            } else {
                // Insertar
                $stmtInsertAct->execute([
                    'w' => $wNum,
                    'lapso' => $lapso,
                    'g' => $grade,
                    'd' => $day,
                    'sub' => $dInfo['subject_id'],
                    'title' => $dInfo['title'],
                    'summary' => $dInfo['summary'],
                    'reading' => $dInfo['reading'],
                    'pract' => $dInfo['exercise'],
                    'xp' => $dInfo['xp'],
                    'ord' => 1
                ]);
                $actId = $pdo->lastInsertId();
            }

            // Preguntas evaluables asociadas
            if ($actId) {
                // Verificar si ya tiene preguntas para no duplicar si ya existen
                $qCount = $pdo->query("SELECT COUNT(*) FROM activity_questions WHERE activity_id = $actId")->fetchColumn();
                if ($qCount == 0) {
                    foreach ($dInfo['questions'] as $q) {
                        $optsJson = !empty($q['options']) ? json_encode($q['options'], JSON_UNESCAPED_UNICODE) : null;
                        $stmtInsertQ->execute([
                            'aid' => $actId,
                            'qtext' => $q['text'],
                            'qtype' => $q['type'],
                            'opts' => $optsJson,
                            'corr' => $q['correct'],
                            'expl' => $q['explanation'],
                            'pts' => $q['points']
                        ]);
                    }
                }
            }

            $totalActivitiesLoaded++;
        }
    }
}

// Actualizar lapsos en registros preexistentes
$pdo->exec("UPDATE activities SET lapso = 1 WHERE week_number BETWEEN 1 AND 10");
$pdo->exec("UPDATE activities SET lapso = 2 WHERE week_number BETWEEN 11 AND 20");
$pdo->exec("UPDATE activities SET lapso = 3 WHERE week_number BETWEEN 21 AND 30");

$pdo->exec("UPDATE weekly_plans SET lapso = 1 WHERE week_number BETWEEN 1 AND 10");
$pdo->exec("UPDATE weekly_plans SET lapso = 2 WHERE week_number BETWEEN 11 AND 20");
$pdo->exec("UPDATE weekly_plans SET lapso = 3 WHERE week_number BETWEEN 21 AND 30");

echo "   -> $totalPlansLoaded planes pedagógicos y $totalActivitiesLoaded actividades escolares sincronizadas.\n";
echo "========================================================\n";
echo "¡POBLACIÓN EXITOSA! EL CURRÍCULO ANUAL DE 3 LAPSOS ESTÁ DISPONIBLE EN LA BASE DE DATOS.\n";
echo "========================================================\n";
