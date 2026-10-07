<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/personajes.php';
requireAdmin();

$pdo = getDB();

// Períodos Académicos Disponibles (Octubre 2026 - Semanas 1 a 4)
$academicMonths = [
    1 => ['name' => 'Octubre 2026', 'icon' => '🍂', 'weeks' => [1, 2, 3, 4], 'desc' => 'Mes Activo: Diagnóstico, Nivelación y Fundamentos Pedagógicos (Semanas 1 a 4)']
];

// Procesamiento de Acciones POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    // 1. Crear nueva actividad en una semana específica
    if ($action === 'create_activity') {
        $weekNum = max(1, (int)($_POST['week_number'] ?? 1));
        $grade = (int)$_POST['grade'];
        $dayOfWeek = $_POST['day_of_week'];
        $subjectId = (int)$_POST['subject_id'];
        $title = trim($_POST['title'] ?? '');
        $summary = trim($_POST['summary'] ?? '');
        $readingContent = trim($_POST['reading_content'] ?? '');
        $practicalExercise = trim($_POST['practical_exercise'] ?? '');
        $xpReward = (int)($_POST['xp_reward'] ?? 50);

        if ($grade && $dayOfWeek && $subjectId && !empty($title) && !empty($readingContent)) {
            $stmt = $pdo->prepare("
                INSERT INTO activities (week_number, grade, day_of_week, subject_id, title, summary, reading_content, practical_exercise, xp_reward, order_num, is_active)
                VALUES (:week, :grade, :day, :sub, :title, :summary, :reading, :pract, :xp, 99, 1)
            ");
            $stmt->execute([
                'week' => $weekNum,
                'grade' => $grade,
                'day' => $dayOfWeek,
                'sub' => $subjectId,
                'title' => $title,
                'summary' => $summary,
                'reading' => $readingContent,
                'pract' => $practicalExercise,
                'xp' => $xpReward
            ]);
            $newActId = $pdo->lastInsertId();

            // Pregunta de evaluación
            $qText = trim($_POST['question_text'] ?? '');
            if (!empty($qText)) {
                $qType = $_POST['question_type'] ?? 'multiple_choice';
                $correct = trim($_POST['correct_answer'] ?? '');
                $optionsJson = null;
                if ($qType === 'multiple_choice' && !empty($_POST['options_raw'])) {
                    $opts = array_map('trim', explode(',', $_POST['options_raw']));
                    shuffle($opts);
                    $optionsJson = json_encode($opts, JSON_UNESCAPED_UNICODE);
                }
                $stmtQ = $pdo->prepare("
                    INSERT INTO activity_questions (activity_id, question_text, question_type, options_json, correct_answer, explanation, points)
                    VALUES (:aid, :qtext, :qtype, :opts, :corr, '', 10)
                ");
                $stmtQ->execute([
                    'aid' => $newActId,
                    'qtext' => $qText,
                    'qtype' => $qType,
                    'opts' => $optionsJson,
                    'corr' => $correct
                ]);
            }

            $_SESSION['flash_success'] = "¡Actividad cargada con éxito en la Semana $weekNum!";
        }
        header("Location: calendario.php?month=" . (int)($_POST['return_month'] ?? 1) . "&week=$weekNum&kid=$grade");
        exit;
    }

    // 2. Editar objetivo del día
    if ($action === 'update_objective') {
        $weekNum = (int)$_POST['week_number'];
        $grade = (int)$_POST['grade'];
        $dayOfWeek = $_POST['day_of_week'];
        $newObj = trim($_POST['objective'] ?? '');

        if ($weekNum && $grade && $dayOfWeek) {
            $stmt = $pdo->prepare("
                INSERT INTO weekly_plans (week_number, grade, day_of_week, objective, is_active)
                VALUES (:week, :grade, :day, :obj, 1)
                ON DUPLICATE KEY UPDATE objective = :obj
            ");
            $stmt->execute(['week' => $weekNum, 'grade' => $grade, 'day' => $dayOfWeek, 'obj' => $newObj]);
            $_SESSION['flash_success'] = "Objetivo pedagógico actualizado para el día " . getDayNameSpanish($dayOfWeek) . ".";
        }
        header("Location: calendario.php?week=$weekNum&kid=$grade");
        exit;
    }

    // 3. Eliminar actividad
    if ($action === 'delete_activity') {
        $actId = (int)$_POST['activity_id'];
        $returnWeek = (int)($_POST['return_week'] ?? 1);
        $stmt = $pdo->prepare("DELETE FROM activities WHERE id = :id");
        $stmt->execute(['id' => $actId]);
        $_SESSION['flash_success'] = "Actividad eliminada correctamente.";
        header("Location: calendario.php?week=$returnWeek");
        exit;
    }

    // 4. Clonar / Copiar estructura de una semana a otra
    if ($action === 'clone_week') {
        $sourceWeek = (int)$_POST['source_week'];
        $targetWeek = (int)$_POST['target_week'];
        $targetGrade = (int)$_POST['target_grade']; // 0 = ambos, 5 o 6

        if ($sourceWeek && $targetWeek && $sourceWeek !== $targetWeek) {
            // Clonar objetivos
            $gradeFilter = $targetGrade > 0 ? "AND grade = $targetGrade" : "";
            $stmtPlans = $pdo->query("SELECT * FROM weekly_plans WHERE week_number = $sourceWeek $gradeFilter");
            $stmtInsertPlan = $pdo->prepare("
                INSERT IGNORE INTO weekly_plans (week_number, grade, day_of_week, objective, is_active)
                VALUES (:week, :grade, :day, :obj, 1)
            ");
            foreach ($stmtPlans->fetchAll() as $sp) {
                $stmtInsertPlan->execute([
                    'week' => $targetWeek,
                    'grade' => $sp['grade'],
                    'day' => $sp['day_of_week'],
                    'obj' => $sp['objective'] . " (Semana $targetWeek)"
                ]);
            }
            $_SESSION['flash_success'] = "Estructura de la Semana $sourceWeek copiada a la Semana $targetWeek con éxito.";
        }
        header("Location: calendario.php?week=$targetWeek");
        exit;
    }
}

// Parámetros de navegación
$selectedMonth = isset($_GET['month']) ? (int)$_GET['month'] : 1;
if (!isset($academicMonths[$selectedMonth])) $selectedMonth = 1;

$monthWeeks = $academicMonths[$selectedMonth]['weeks'];
$selectedWeek = isset($_GET['week']) ? (int)$_GET['week'] : $monthWeeks[0];

// Filtro de niño: 0 = Ambos, 5 = Dimas, 6 = Alexander
$selectedKid = isset($_GET['kid']) ? (int)$_GET['kid'] : 0;

// Obtener todas las materias para selectores
$subjects = $pdo->query("SELECT * FROM subjects ORDER BY name ASC")->fetchAll();
$subjectsById = [];
foreach ($subjects as $s) $subjectsById[$s['id']] = $s;

// Obtener métricas consolidadas de todas las semanas existentes
$summaryQuery = "
    SELECT 
        a.week_number,
        a.grade,
        COUNT(DISTINCT a.id) as total_activities,
        COUNT(DISTINCT a.day_of_week) as days_with_content,
        COUNT(DISTINCT aq.id) as total_questions,
        COUNT(DISTINCT sub.id) as completed_submissions,
        " . (($pdo->getAttribute(PDO::ATTR_DRIVER_NAME) === 'pgsql') ? "STRING_AGG(DISTINCT s.slug, ',')" : "GROUP_CONCAT(DISTINCT s.slug)") . " as subject_slugs
    FROM activities a
    JOIN subjects s ON a.subject_id = s.id
    LEFT JOIN activity_questions aq ON a.id = aq.activity_id
    LEFT JOIN student_submissions sub ON a.id = sub.activity_id
    GROUP BY a.week_number, a.grade
";
$summaryRaw = $pdo->query($summaryQuery)->fetchAll();
$weekSummary = [];
foreach ($summaryRaw as $row) {
    $weekSummary[$row['week_number']][$row['grade']] = $row;
}

// Obtener objetivos de la semana seleccionada
$plansQuery = "SELECT * FROM weekly_plans WHERE week_number = :week";
$stmtP = $pdo->prepare($plansQuery);
$stmtP->execute(['week' => $selectedWeek]);
$weekPlans = [];
foreach ($stmtP->fetchAll() as $wp) {
    $weekPlans[$wp['grade']][$wp['day_of_week']] = $wp;
}

// Obtener actividades detalladas de la semana seleccionada
$actSql = "
    SELECT a.*, s.name as subject_name, s.slug as subject_slug, s.icon as subject_icon, s.color as subject_color,
           (SELECT COUNT(*) FROM activity_questions aq WHERE aq.activity_id = a.id) as questions_count,
           sub.id as submission_id, sub.score, sub.max_score, sub.parent_stars, sub.parent_feedback, sub.status as sub_status, sub.submitted_at,
           u.name as student_name
    FROM activities a
    JOIN subjects s ON a.subject_id = s.id
    LEFT JOIN student_submissions sub ON a.id = sub.activity_id
    LEFT JOIN users u ON sub.user_id = u.id
    WHERE a.week_number = :week
";
if ($selectedKid > 0) {
    $actSql .= " AND a.grade = $selectedKid";
}
$actSql .= " ORDER BY a.grade ASC, (CASE a.day_of_week WHEN 'lunes' THEN 1 WHEN 'martes' THEN 2 WHEN 'miercoles' THEN 3 WHEN 'jueves' THEN 4 WHEN 'viernes' THEN 5 ELSE 6 END), a.order_num ASC";
$stmtActs = $pdo->prepare($actSql);
$stmtActs->execute(['week' => $selectedWeek]);
$weekActivities = $stmtActs->fetchAll();

// Indexar actividades por [grade][day_of_week]
$activitiesByGradeDay = [];
$activitiesById = [];
foreach ($weekActivities as $act) {
    $activitiesByGradeDay[$act['grade']][$act['day_of_week']][] = $act;
    $activitiesById[$act['id']] = $act;
}

// Cargar preguntas de todas las actividades de esta semana para el modal de inspección
$actIds = array_keys($activitiesById);
$questionsByActId = [];
if (!empty($actIds)) {
    $inClause = implode(',', $actIds);
    $qRows = $pdo->query("SELECT * FROM activity_questions WHERE activity_id IN ($inClause) ORDER BY id ASC")->fetchAll();
    foreach ($qRows as $qr) {
        $questionsByActId[$qr['activity_id']][] = $qr;
    }
}

$daysOfWeek = ['lunes', 'martes', 'miercoles', 'jueves', 'viernes'];

$pageTitle = 'Calendario de Contenidos por Semanas';
require_once __DIR__ . '/../includes/header.php';
?>

<div style="margin-bottom: 2.5rem;">
  
  <!-- Encabezado Principal del Calendario -->
  <div style="display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 1rem; margin-bottom: 1.5rem;">
    <div>
      <div style="display: flex; align-items: center; gap: 0.5rem; margin-bottom: 0.35rem;">
        <span style="font-size: 1.8rem;">🗓️</span>
        <h1 style="font-size: 1.85rem; margin: 0;">Calendario de Contenidos y Semanas</h1>
      </div>
      <p style="color: var(--text-secondary); margin: 0; font-size: 0.98rem;">
        Verifica el material pedagógico cargado por semana y mes para <strong>Dimas (5to Grado)</strong> y <strong>Alexander (6to Grado)</strong>.
      </p>
    </div>

    <!-- Acciones Rápidas -->
    <div style="display: flex; gap: 0.6rem; flex-wrap: wrap;">
      <button class="btn btn-primary btn-sm" onclick="openNewActivityModal(<?= $selectedWeek ?>, <?= $selectedKid ?: 5 ?>)">
        <span>➕ Cargar Nueva Misión</span>
      </button>
      <button class="btn btn-secondary btn-sm" onclick="openModal('modalCloneWeek')">
        <span>📋 Duplicar Estructura Semanal</span>
      </button>
      <a href="planner.php?week=<?= $selectedWeek ?>" class="btn btn-secondary btn-sm" title="Ver planificador de tablas">
        <span>📅 Plan Semanal</span>
      </a>
      <a href="grade.php" class="btn btn-secondary btn-sm">
        <span>⭐ Evaluar Entregas</span>
      </a>
    </div>
  </div>

  <!-- Selector de Meses Académicos -->
  <div class="calendar-month-selector" style="margin-bottom: 1.5rem;">
    <div style="display: flex; gap: 0.75rem; overflow-x: auto; padding-bottom: 0.5rem;">
      <?php foreach ($academicMonths as $mNum => $mData): ?>
        <a href="?month=<?= $mNum ?>&week=<?= $mData['weeks'][0] ?>&kid=<?= $selectedKid ?>" 
           class="month-tab-pill <?= $selectedMonth === $mNum ? 'active' : '' ?>">
          <span style="font-size: 1.25rem;"><?= $mData['icon'] ?></span>
          <div>
            <div style="font-weight: 800; font-size: 0.95rem;"><?= htmlspecialchars($mData['name']) ?></div>
            <div style="font-size: 0.72rem; opacity: 0.85;">Semanas <?= implode(', ', $mData['weeks']) ?></div>
          </div>
        </a>
      <?php endforeach; ?>
    </div>
  </div>

  <!-- Tarjetas del Mes Seleccionado: Cuadrícula de Semanas del Mes -->
  <div style="margin-bottom: 2rem;">
    <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 0.85rem; flex-wrap: wrap; gap: 0.5rem;">
      <h2 style="font-size: 1.25rem; color: var(--text-primary);">
        <?= $academicMonths[$selectedMonth]['icon'] ?> <?= htmlspecialchars($academicMonths[$selectedMonth]['name']) ?> — Estado de Semanas
      </h2>
      <span style="font-size: 0.85rem; color: var(--text-secondary); background: #f1f5f9; padding: 0.25rem 0.65rem; border-radius: var(--radius-full);">
        <?= htmlspecialchars($academicMonths[$selectedMonth]['desc']) ?>
      </span>
    </div>

    <div class="calendar-weeks-grid">
      <?php foreach ($monthWeeks as $wNum): 
        $wDimas = $weekSummary[$wNum][5] ?? null;
        $wAlex = $weekSummary[$wNum][6] ?? null;
        $totalWActs = ($wDimas['total_activities'] ?? 0) + ($wAlex['total_activities'] ?? 0);
        $isSelected = ($selectedWeek === $wNum);
      ?>
        <div class="calendar-week-card <?= $isSelected ? 'selected-week' : '' ?>" id="week-card-<?= $wNum ?>">
          <div class="week-card-header">
            <div>
              <span class="week-tag">Semana <?= $wNum ?></span>
              <div style="font-size: 0.8rem; color: var(--text-muted); margin-top: 0.2rem;">
                Días de clase: Lunes a Viernes
              </div>
            </div>
            <div>
              <?php if ($totalWActs >= 20): ?>
                <span class="badge-status-completed" title="Semana completa con todas las actividades">✅ Completa</span>
              <?php elseif ($totalWActs > 0): ?>
                <span class="badge-status-progress" title="Contenido cargado parcialmente">⚡ En Progreso</span>
              <?php else: ?>
                <span class="badge-status-empty" title="Sin contenido cargado todavía">⏳ Pendiente</span>
              <?php endif; ?>
            </div>
          </div>

          <!-- Estado por Niño -->
          <div class="week-kids-status">
            <!-- Dimas 5to -->
            <div class="kid-status-row">
              <div style="display: flex; align-items: center; gap: 0.4rem;">
                <span>⚡</span>
                <strong style="font-size: 0.88rem;">Dimas (5to):</strong>
              </div>
              <div>
                <?php if ($wDimas && $wDimas['total_activities'] > 0): ?>
                  <span class="badge-content-pill content-loaded">
                    <?= $wDimas['total_activities'] ?> misiones (<?= $wDimas['days_with_content'] ?>/5 días)
                  </span>
                <?php else: ?>
                  <span class="badge-content-pill content-empty">Sin material</span>
                <?php endif; ?>
              </div>
            </div>

            <!-- Alexander 6to -->
            <div class="kid-status-row">
              <div style="display: flex; align-items: center; gap: 0.4rem;">
                <span>🚀</span>
                <strong style="font-size: 0.88rem;">Alexander (6to):</strong>
              </div>
              <div>
                <?php if ($wAlex && $wAlex['total_activities'] > 0): ?>
                  <span class="badge-content-pill content-loaded">
                    <?= $wAlex['total_activities'] ?> misiones (<?= $wAlex['days_with_content'] ?>/5 días)
                  </span>
                <?php else: ?>
                  <span class="badge-content-pill content-empty">Sin material</span>
                <?php endif; ?>
              </div>
            </div>
          </div>

          <!-- Indicador visual de los 5 días de la semana -->
          <div class="week-days-dots">
            <?php 
              $dayLetters = ['L' => 'lunes', 'M' => 'martes', 'M' => 'miercoles', 'J' => 'jueves', 'V' => 'viernes'];
              foreach ($dayLetters as $letter => $dSlug):
                $hasD5 = !empty($activitiesByGradeDay[5][$dSlug]) || ($wDimas && $wDimas['days_with_content'] == 5);
                $hasD6 = !empty($activitiesByGradeDay[6][$dSlug]) || ($wAlex && $wAlex['days_with_content'] == 5);
                $isLoaded = ($totalWActs > 0 && ($hasD5 || $hasD6));
            ?>
              <span class="day-dot <?= $isLoaded ? 'loaded' : 'empty' ?>" title="<?= getDayNameSpanish($dSlug) ?>: <?= $isLoaded ? 'Material cargado' : 'Sin contenido' ?>">
                <?= $letter ?>
              </span>
            <?php endforeach; ?>
          </div>

          <!-- Acción de la tarjeta -->
          <div style="margin-top: 1rem; display: flex; gap: 0.4rem;">
            <a href="?month=<?= $selectedMonth ?>&week=<?= $wNum ?>&kid=<?= $selectedKid ?>#inspeccionContenido" 
               class="btn btn-sm <?= $isSelected ? 'btn-primary' : 'btn-secondary' ?>" 
               style="flex: 1; justify-content: center; font-size: 0.82rem;">
              <?= $isSelected ? '🔍 Inspeccionando' : '📖 Ver Material' ?>
            </a>
            <?php if ($totalWActs == 0): ?>
              <button type="button" 
                      class="btn btn-success btn-sm" 
                      style="font-size: 0.82rem; padding: 0.3rem 0.6rem;"
                      onclick="openNewActivityModal(<?= $wNum ?>, <?= $selectedKid ?: 5 ?>)"
                      title="Cargar material para esta semana">
                ➕
              </button>
            <?php endif; ?>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  </div>

  <!-- SECCIÓN DE INSPECCIÓN DETALLADA DE LA SEMANA SELECCIONADA -->
  <div id="inspeccionContenido" style="scroll-margin-top: 80px;">
    
    <!-- Barra de Control del Inspector -->
    <div class="card" style="margin-bottom: 1.5rem; background: #ffffff; border: 2px solid var(--border); padding: 1.25rem 1.5rem;">
      <div style="display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 1rem;">
        <div>
          <div style="display: flex; align-items: center; gap: 0.5rem;">
            <span class="badge-subject badge-lectura" style="font-size: 0.85rem;">Semana <?= $selectedWeek ?></span>
            <h2 style="font-size: 1.45rem; margin: 0;">Material Didáctico y Evaluación Cargada</h2>
          </div>
          <p style="font-size: 0.9rem; color: var(--text-secondary); margin-top: 0.25rem; margin-bottom: 0;">
            Revisa las lecturas completas, preguntas y retos cargados para cada día de clase.
          </p>
        </div>

        <!-- Filtro por Niño -->
        <div style="display: flex; align-items: center; gap: 0.5rem; background: #f8fafc; padding: 0.35rem; border-radius: var(--radius-md); border: 1px solid var(--border);">
          <span style="font-size: 0.82rem; font-weight: 700; color: var(--text-muted); margin-left: 0.4rem;">Filtrar Alumno:</span>
          <a href="?month=<?= $selectedMonth ?>&week=<?= $selectedWeek ?>&kid=0#inspeccionContenido" 
             class="kid-filter-btn <?= $selectedKid === 0 ? 'active' : '' ?>">
            👥 Ambos Niños
          </a>
          <a href="?month=<?= $selectedMonth ?>&week=<?= $selectedWeek ?>&kid=5#inspeccionContenido" 
             class="kid-filter-btn <?= $selectedKid === 5 ? 'active' : '' ?>">
            ⚡ Dimas (5to)
          </a>
          <a href="?month=<?= $selectedMonth ?>&week=<?= $selectedWeek ?>&kid=6#inspeccionContenido" 
             class="kid-filter-btn <?= $selectedKid === 6 ? 'active' : '' ?>">
            🚀 Alexander (6to)
          </a>
        </div>
      </div>
    </div>

    <!-- Desglose por Estudiante -->
    <?php 
      $kidsToDisplay = ($selectedKid > 0) ? [$selectedKid] : [5, 6];
      foreach ($kidsToDisplay as $gradeNum):
        $kidLabel = ($gradeNum === 5) ? 'Dimas (5to Grado — 10 años)' : 'Alexander (6to Grado — 11 años)';
        $kidBadgeClass = ($gradeNum === 5) ? 'badge-matematicas' : 'badge-lectura';
        $kidIcon = ($gradeNum === 5) ? '⚡' : '🚀';
    ?>
      <div style="margin-bottom: 2.5rem;">
        
        <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 1.25rem; flex-wrap: wrap; gap: 0.5rem;">
          <div style="display: flex; align-items: center; gap: 0.6rem;">
            <span style="font-size: 1.8rem;"><?= $kidIcon ?></span>
            <div>
              <h3 style="font-size: 1.3rem; margin: 0;"><?= $kidLabel ?></h3>
              <span style="font-size: 0.8rem; color: var(--text-muted);">Semana <?= $selectedWeek ?> de Clases</span>
            </div>
          </div>
          <button type="button" 
                  class="btn btn-secondary btn-sm"
                  onclick="openNewActivityModal(<?= $selectedWeek ?>, <?= $gradeNum ?>)">
            ➕ Cargar Misión para <?= ($gradeNum === 5) ? 'Dimas' : 'Alex' ?>
          </button>
        </div>

        <!-- Cuadrícula de los 5 Días de la Semana -->
        <div class="calendar-days-schedule">
          <?php foreach ($daysOfWeek as $dSlug): 
            $dayPlan = $weekPlans[$gradeNum][$dSlug] ?? null;
            $dayActs = $activitiesByGradeDay[$gradeNum][$dSlug] ?? [];
          ?>
            <div class="calendar-day-column">
              <!-- Encabezado del Día -->
              <div class="day-col-header">
                <div>
                  <div class="day-col-title"><?= getDayNameSpanish($dSlug) ?></div>
                  <span class="day-col-count"><?= count($dayActs) ?> misiones</span>
                </div>
                <button type="button" 
                        class="btn-icon-tiny" 
                        onclick="openObjectiveModal(<?= $selectedWeek ?>, <?= $gradeNum ?>, '<?= $dSlug ?>', '<?= htmlspecialchars(addslashes($dayPlan['objective'] ?? ''), ENT_QUOTES) ?>')"
                        title="Definir u objetivo pedagógico de este día">
                  ✏️
                </button>
              </div>

              <!-- Objetivo Pedagógico del Día -->
              <div class="day-col-objective">
                <strong>🎯 Meta:</strong> <?= htmlspecialchars($dayPlan['objective'] ?? 'Sin objetivo establecido aún.') ?>
              </div>

              <!-- Lista de Actividades Cargadas -->
              <div class="day-col-activities">
                <?php if (empty($dayActs)): ?>
                  <div class="empty-day-state">
                    <p style="font-size: 0.82rem; color: var(--text-muted); margin: 0 0 0.5rem 0;">
                      Sin misiones para este día.
                    </p>
                    <button type="button" 
                            class="btn btn-secondary btn-sm" 
                            style="font-size: 0.75rem; width: 100%;"
                            onclick="openNewActivityModal(<?= $selectedWeek ?>, <?= $gradeNum ?>, '<?= $dSlug ?>')">
                      ➕ Añadir Misión
                    </button>
                  </div>
                <?php else: ?>
                  <?php foreach ($dayActs as $act): 
                    $mascot = getPersonajeBySubject($act['subject_slug']);
                    $wordCount = str_word_count(strip_tags($act['reading_content']));
                    $qCount = $act['questions_count'];
                    $isCompleted = !empty($act['submission_id']);
                  ?>
                    <div class="activity-loaded-card" style="border-top: 4px solid <?= $act['subject_color'] ?>;">
                      <!-- Cabecera de la Actividad -->
                      <div style="display: flex; align-items: flex-start; justify-content: space-between; gap: 0.5rem; margin-bottom: 0.4rem;">
                        <span class="badge-subject badge-<?= $act['subject_slug'] ?>" style="font-size: 0.72rem; padding: 0.15rem 0.45rem;">
                          <?= $act['subject_icon'] ?> <?= htmlspecialchars($act['subject_name']) ?>
                        </span>
                        <span style="font-size: 0.75rem; font-weight: 800; color: #d97706;">
                          ⚡ +<?= $act['xp_reward'] ?> XP
                        </span>
                      </div>

                      <!-- Título y Tutor -->
                      <h4 class="act-card-title"><?= htmlspecialchars($act['title']) ?></h4>
                      
                      <!-- Tutor Asociado -->
                      <?php if ($mascot): ?>
                        <div class="act-mascot-chip">
                          <img src="../assets/images/personajes/<?= htmlspecialchars($mascot['archivo_render']) ?>" 
                               alt="<?= htmlspecialchars($mascot['nombre']) ?>" 
                               class="act-mascot-avatar">
                          <span><?= htmlspecialchars($mascot['nombre']) ?></span>
                        </div>
                      <?php endif; ?>

                      <!-- Métrica de Contenido -->
                      <div class="act-metrics-box">
                        <span title="Extensión de la lectura">📖 <?= $wordCount ?> palabras</span>
                        <span title="Preguntas de evaluación">✍️ <?= $qCount ?> preguntas</span>
                      </div>

                      <!-- Estado de Evaluación del Alumno -->
                      <div style="margin-top: 0.6rem; font-size: 0.75rem;">
                        <?php if ($isCompleted): ?>
                          <div style="background: #d1fae5; color: #065f46; padding: 0.3rem 0.5rem; border-radius: var(--radius-sm); font-weight: 700;">
                            ✅ Realizada: <?= $act['score'] ?>/<?= $act['max_score'] ?> pts
                            <?php if ($act['parent_stars'] > 0): ?>
                              · <?= str_repeat('⭐', $act['parent_stars']) ?>
                            <?php endif; ?>
                          </div>
                        <?php else: ?>
                          <div style="background: #fef3c7; color: #92400e; padding: 0.3rem 0.5rem; border-radius: var(--radius-sm); font-weight: 600;">
                            ⏳ Lista para evaluación (Pendiente)
                          </div>
                        <?php endif; ?>
                      </div>

                      <!-- Botones de Acción sobre la Actividad -->
                      <div class="act-card-actions">
                        <button type="button" 
                                class="btn btn-primary btn-sm" 
                                style="font-size: 0.74rem; padding: 0.3rem 0.5rem; flex: 1;"
                                onclick="openInspectActivityModal(<?= $act['id'] ?>)">
                          👁️ Ver Material
                        </button>
                        <a href="../student/activity.php?id=<?= $act['id'] ?>" 
                           target="_blank" 
                           class="btn btn-secondary btn-sm" 
                           style="font-size: 0.74rem; padding: 0.3rem 0.45rem;"
                           title="Abrir como la ve el niño en su pantalla">
                          🚀
                        </a>
                        <form method="POST" action="calendario.php" style="margin: 0;" onsubmit="return confirm('¿Seguro que deseas eliminar esta actividad?');">
                          <input type="hidden" name="action" value="delete_activity">
                          <input type="hidden" name="activity_id" value="<?= $act['id'] ?>">
                          <input type="hidden" name="return_week" value="<?= $selectedWeek ?>">
                          <button type="submit" class="btn btn-danger btn-sm" style="font-size: 0.74rem; padding: 0.3rem 0.45rem;" title="Eliminar actividad">
                            🗑️
                          </button>
                        </form>
                      </div>
                    </div>
                  <?php endforeach; ?>
                <?php endif; ?>
              </div>
            </div>
          <?php endforeach; ?>
        </div>

      </div>
    <?php endforeach; ?>

  </div>

</div>

<!-- ==========================================================================
     MODALES INTERACTIVOS DE INSPECCIÓN Y CARGA
     ========================================================================== -->

<!-- 1. MODAL: INSPECCIONAR MATERIAL COMPLETO (LECTURA + PREGUNTAS) -->
<div class="modal-overlay" id="modalInspectActivity">
  <div class="modal-content" style="max-width: 750px; max-height: 90vh; overflow-y: auto;">
    <div style="display: flex; align-items: center; justify-content: space-between; border-bottom: 2px solid var(--border); padding-bottom: 0.75rem; margin-bottom: 1rem;">
      <div style="display: flex; align-items: center; gap: 0.5rem;">
        <span style="font-size: 1.5rem;" id="inspectIcon">📖</span>
        <div>
          <h3 style="font-size: 1.25rem; margin: 0;" id="inspectTitle">Cargando material...</h3>
          <span style="font-size: 0.8rem; color: var(--text-muted);" id="inspectSubject">Materia</span>
        </div>
      </div>
      <button type="button" class="btn-icon" onclick="closeModal('modalInspectActivity')">✖️</button>
    </div>

    <div id="inspectBody">
      <!-- Inyectado dinámicamente vía JavaScript -->
    </div>

    <div style="margin-top: 1.5rem; text-align: right; border-top: 1px solid var(--border); padding-top: 1rem;">
      <button type="button" class="btn btn-secondary" onclick="closeModal('modalInspectActivity')">
        Cerrar Inspección
      </button>
    </div>
  </div>
</div>

<!-- 2. MODAL: CARGAR NUEVA MISIÓN / ACTIVIDAD -->
<div class="modal-overlay" id="modalNewActivity">
  <div class="modal-content" style="max-width: 680px; max-height: 90vh; overflow-y: auto;">
    <div style="display: flex; align-items: center; justify-content: space-between; border-bottom: 2px solid var(--border); padding-bottom: 0.75rem; margin-bottom: 1.25rem;">
      <h3 style="font-size: 1.25rem; margin: 0;">➕ Cargar Nueva Misión Educativa</h3>
      <button type="button" class="btn-icon" onclick="closeModal('modalNewActivity')">✖️</button>
    </div>

    <form method="POST" action="calendario.php">
      <input type="hidden" name="action" value="create_activity">
      <input type="hidden" name="return_month" value="<?= $selectedMonth ?>">

      <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 0.75rem; margin-bottom: 1rem;">
        <div class="form-group" style="margin-bottom: 0;">
          <label class="form-label">🗓️ Semana</label>
          <select name="week_number" id="modalWeekSelect" class="form-control" required>
            <?php for ($w = 1; $w <= 16; $w++): ?>
              <option value="<?= $w ?>" <?= $selectedWeek === $w ? 'selected' : '' ?>>Semana <?= $w ?></option>
            <?php endfor; ?>
          </select>
        </div>

        <div class="form-group" style="margin-bottom: 0;">
          <label class="form-label">👦 Estudiante</label>
          <select name="grade" id="modalGradeSelect" class="form-control" required>
            <option value="5">⚡ Dimas (5to Grado)</option>
            <option value="6">🚀 Alexander (6to Grado)</option>
          </select>
        </div>

        <div class="form-group" style="margin-bottom: 0;">
          <label class="form-label">📅 Día de la Semana</label>
          <select name="day_of_week" id="modalDaySelect" class="form-control" required>
            <?php foreach ($daysOfWeek as $d): ?>
              <option value="<?= $d ?>"><?= getDayNameSpanish($d) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
      </div>

      <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 0.75rem; margin-bottom: 1rem;">
        <div class="form-group" style="margin-bottom: 0;">
          <label class="form-label">📚 Materia y Tutor</label>
          <select name="subject_id" class="form-control" required>
            <?php foreach ($subjects as $s): 
              $mascot = getPersonajeBySubject($s['slug']);
            ?>
              <option value="<?= $s['id'] ?>">
                <?= $s['icon'] ?> <?= htmlspecialchars($s['name']) ?> (<?= $mascot['nombre'] ?? 'Tutor' ?>)
              </option>
            <?php endforeach; ?>
          </select>
        </div>

        <div class="form-group" style="margin-bottom: 0;">
          <label class="form-label">⚡ XP Recompensa</label>
          <input type="number" name="xp_reward" class="form-control" value="50" min="10" max="200" required>
        </div>
      </div>

      <div class="form-group">
        <label class="form-label">🎯 Título de la Misión</label>
        <input type="text" name="title" class="form-control" placeholder="Ej: Descubriendo los Ecosistemas del Río Orinoco" required>
      </div>

      <div class="form-group">
        <label class="form-label">📝 Resumen Corto (1 frase descriptiva)</label>
        <input type="text" name="summary" class="form-control" placeholder="Breve introducción pedagógica para motivar al niño" required>
      </div>

      <div class="form-group">
        <label class="form-label">📖 Contenido de Lectura Didáctica</label>
        <textarea name="reading_content" class="input-textarea" style="min-height: 140px;" placeholder="Escribe aquí el texto explicativo con ejemplos claros y lenguaje cálido..." required></textarea>
      </div>

      <div class="form-group">
        <label class="form-label">💡 Reto de Práctica (Opcional)</label>
        <input type="text" name="practical_exercise" class="form-control" placeholder="Ej: Realiza un dibujo en tu cuaderno o resuelve un cálculo rápido">
      </div>

      <!-- Pregunta de Evaluación Inicial -->
      <div style="background: #f8fafc; border: 1px dashed #cbd5e1; border-radius: var(--radius-md); padding: 1rem; margin-top: 1.25rem;">
        <div style="font-weight: 700; font-size: 0.92rem; margin-bottom: 0.75rem; color: var(--text-primary);">
          ✍️ Pregunta de Evaluación para la Misión
        </div>

        <div class="form-group">
          <label class="form-label">Enunciado de la Pregunta</label>
          <input type="text" name="question_text" class="form-control" placeholder="Ej: ¿Cuál es el río más largo y caudaloso de Venezuela?">
        </div>

        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 0.75rem;">
          <div class="form-group" style="margin-bottom: 0;">
            <label class="form-label">Opciones (separadas por comas)</label>
            <input type="text" name="options_raw" class="form-control" placeholder="Río Orinoco, Río Caroní, Río Apure, Río Amazonas">
          </div>
          <div class="form-group" style="margin-bottom: 0;">
            <label class="form-label">Respuesta Correcta (exacta)</label>
            <input type="text" name="correct_answer" class="form-control" placeholder="Río Orinoco">
          </div>
        </div>
      </div>

      <div style="margin-top: 1.5rem; display: flex; justify-content: flex-end; gap: 0.75rem;">
        <button type="button" class="btn btn-secondary" onclick="closeModal('modalNewActivity')">Cancelar</button>
        <button type="submit" class="btn btn-primary">🚀 Publicar Misión en el Calendario</button>
      </div>
    </form>
  </div>
</div>

<!-- 3. MODAL: EDITAR OBJETIVO DEL DÍA -->
<div class="modal-overlay" id="modalEditObjective">
  <div class="modal-content" style="max-width: 500px;">
    <div style="display: flex; align-items: center; justify-content: space-between; border-bottom: 1px solid var(--border); padding-bottom: 0.5rem; margin-bottom: 1rem;">
      <h3 style="font-size: 1.15rem; margin: 0;">🎯 Objetivo Pedagógico del Día</h3>
      <button type="button" class="btn-icon" onclick="closeModal('modalEditObjective')">✖️</button>
    </div>

    <form method="POST" action="calendario.php">
      <input type="hidden" name="action" value="update_objective">
      <input type="hidden" name="week_number" id="objWeekInput" value="1">
      <input type="hidden" name="grade" id="objGradeInput" value="5">
      <input type="hidden" name="day_of_week" id="objDayInput" value="lunes">

      <div class="form-group">
        <label class="form-label" id="objDayLabel">Objetivo pedagógico:</label>
        <textarea name="objective" id="objTextInput" class="input-textarea" style="min-height: 90px;" required></textarea>
      </div>

      <div style="display: flex; justify-content: flex-end; gap: 0.5rem;">
        <button type="button" class="btn btn-secondary" onclick="closeModal('modalEditObjective')">Cancelar</button>
        <button type="submit" class="btn btn-primary">Guardar Objetivo</button>
      </div>
    </form>
  </div>
</div>

<!-- 4. MODAL: DUPLICAR ESTRUCTURA SEMANAL -->
<div class="modal-overlay" id="modalCloneWeek">
  <div class="modal-content" style="max-width: 500px;">
    <div style="display: flex; align-items: center; justify-content: space-between; border-bottom: 1px solid var(--border); padding-bottom: 0.5rem; margin-bottom: 1rem;">
      <h3 style="font-size: 1.15rem; margin: 0;">📋 Duplicar Estructura Semanal</h3>
      <button type="button" class="btn-icon" onclick="closeModal('modalCloneWeek')">✖️</button>
    </div>

    <form method="POST" action="calendario.php">
      <input type="hidden" name="action" value="clone_week">

      <p style="font-size: 0.88rem; color: var(--text-secondary); margin-bottom: 1rem;">
        Copia los objetivos pedagógicos base de una semana existente a una semana nueva para preparar el cronograma con anticipación.
      </p>

      <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 0.75rem; margin-bottom: 1rem;">
        <div class="form-group">
          <label class="form-label">Semana Origen</label>
          <select name="source_week" class="form-control" required>
            <option value="1">Semana 1</option>
            <option value="2">Semana 2</option>
          </select>
        </div>

        <div class="form-group">
          <label class="form-label">Semana Destino</label>
          <select name="target_week" class="form-control" required>
            <?php for ($w = 2; $w <= 16; $w++): ?>
              <option value="<?= $w ?>" <?= $selectedWeek + 1 === $w ? 'selected' : '' ?>>Semana <?= $w ?></option>
            <?php endfor; ?>
          </select>
        </div>
      </div>

      <div class="form-group" style="margin-bottom: 1.25rem;">
        <label class="form-label">Aplicar para</label>
        <select name="target_grade" class="form-control">
          <option value="0">Ambos Niños (5to y 6to)</option>
          <option value="5">Solo Dimas (5to Grado)</option>
          <option value="6">Solo Alexander (6to Grado)</option>
        </select>
      </div>

      <div style="display: flex; justify-content: flex-end; gap: 0.5rem;">
        <button type="button" class="btn btn-secondary" onclick="closeModal('modalCloneWeek')">Cancelar</button>
        <button type="submit" class="btn btn-primary">Duplicar Estructura</button>
      </div>
    </form>
  </div>
</div>

<!-- Datos JSON para el Inspector Interactivo -->
<script>
const activitiesData = <?= json_encode($activitiesById, JSON_UNESCAPED_UNICODE) ?>;
const questionsData = <?= json_encode($questionsByActId, JSON_UNESCAPED_UNICODE) ?>;

function openInspectActivityModal(actId) {
  const act = activitiesData[actId];
  if (!act) return;

  const questions = questionsData[actId] || [];

  document.getElementById('inspectIcon').textContent = act.subject_icon;
  document.getElementById('inspectTitle').textContent = act.title;
  document.getElementById('inspectSubject').textContent = act.subject_name + ' · +' + act.xp_reward + ' XP · ' + (act.grade == 5 ? 'Dimas (5to)' : 'Alex (6to)');

  let html = `
    <!-- Resumen -->
    <div style="background: #f8fafc; border-left: 4px solid ${act.subject_color}; padding: 0.85rem 1rem; border-radius: var(--radius-sm); margin-bottom: 1.25rem;">
      <div style="font-size: 0.8rem; font-weight: 700; text-transform: uppercase; color: var(--text-muted); margin-bottom: 0.2rem;">Resumen Pedagógico:</div>
      <div style="font-size: 0.95rem; color: var(--text-primary);">${escapeHtml(act.summary)}</div>
    </div>

    <!-- Lectura Completa -->
    <div style="margin-bottom: 1.5rem;">
      <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 0.5rem;">
        <h4 style="font-size: 1.05rem; margin: 0; color: var(--text-primary);">📖 Lectura Asignada</h4>
        <span style="font-size: 0.8rem; color: var(--text-muted);">${escapeHtml(act.reading_content).split(/\\s+/).length} palabras</span>
      </div>
      <div style="background: #ffffff; border: 1.5px solid var(--border); border-radius: var(--radius-md); padding: 1.15rem; font-size: 0.95rem; line-height: 1.7; color: #1e293b; max-height: 260px; overflow-y: auto; white-space: pre-line;">
        ${escapeHtml(act.reading_content)}
      </div>
    </div>
  `;

  if (act.practical_exercise) {
    html += `
      <div style="background: #fffbeb; border: 1px solid #fef3c7; border-radius: var(--radius-md); padding: 0.85rem 1rem; margin-bottom: 1.5rem;">
        <strong style="color: #b45309; font-size: 0.9rem;">💡 Reto de Práctica:</strong>
        <p style="margin: 0.25rem 0 0 0; font-size: 0.9rem; color: #78350f;">${escapeHtml(act.practical_exercise)}</p>
      </div>
    `;
  }

  // Preguntas de evaluación
  html += `
    <div>
      <h4 style="font-size: 1.05rem; margin-bottom: 0.75rem; color: var(--text-primary);">
        ✍️ Preguntas de Evaluación (${questions.length})
      </h4>
  `;

  if (questions.length === 0) {
    html += `<p style="font-size: 0.88rem; color: var(--text-muted); font-style: italic;">No se han cargado preguntas de prueba aún para esta misión.</p>`;
  } else {
    questions.forEach((q, idx) => {
      let optionsList = [];
      try {
        if (q.options_json) optionsList = JSON.parse(q.options_json);
      } catch(e) {}

      html += `
        <div style="background: #ffffff; border: 1px solid var(--border); border-radius: var(--radius-md); padding: 0.85rem 1rem; margin-bottom: 0.75rem;">
          <div style="display: flex; justify-content: space-between; margin-bottom: 0.4rem;">
            <strong style="font-size: 0.92rem; color: var(--text-primary);">Pregunta ${idx + 1}: ${escapeHtml(q.question_text)}</strong>
            <span style="font-size: 0.75rem; background: #e0e7ff; color: #3730a3; padding: 0.15rem 0.5rem; border-radius: var(--radius-full); font-weight: 700;">+${q.points} pts</span>
          </div>
      `;

      if (optionsList.length > 0) {
        html += `<div style="display: flex; flex-direction: column; gap: 0.35rem; margin-top: 0.5rem;">`;
        optionsList.forEach(opt => {
          const isCorrect = (opt.trim().toLowerCase() === (q.correct_answer || '').trim().toLowerCase());
          html += `
            <div style="font-size: 0.85rem; padding: 0.35rem 0.6rem; border-radius: var(--radius-sm); border: 1px solid ${isCorrect ? '#10b981' : '#e2e8f0'}; background: ${isCorrect ? '#ecfdf5' : '#f8fafc'}; color: ${isCorrect ? '#065f46' : 'var(--text-primary)'}; font-weight: ${isCorrect ? '700' : 'normal'};">
              ${isCorrect ? '✅ ' : '⚪ '} ${escapeHtml(opt)} ${isCorrect ? ' (Respuesta Correcta)' : ''}
            </div>
          `;
        });
        html += `</div>`;
      } else {
        html += `
          <div style="margin-top: 0.5rem; font-size: 0.85rem; color: var(--text-secondary); background: #f8fafc; padding: 0.4rem 0.6rem; border-radius: var(--radius-sm);">
            <strong>Respuesta Esperada:</strong> ${escapeHtml(q.correct_answer || 'Respuesta abierta reflexiva')}
          </div>
        `;
      }

      html += `</div>`;
    });
  }

  html += `</div>`;

  document.getElementById('inspectBody').innerHTML = html;
  openModal('modalInspectActivity');
}

function openNewActivityModal(weekNum, grade, dayOfWeek = 'lunes') {
  if (weekNum) document.getElementById('modalWeekSelect').value = weekNum;
  if (grade) document.getElementById('modalGradeSelect').value = grade;
  if (dayOfWeek) document.getElementById('modalDaySelect').value = dayOfWeek;
  openModal('modalNewActivity');
}

function openObjectiveModal(weekNum, grade, dayOfWeek, currentObjective) {
  document.getElementById('objWeekInput').value = weekNum;
  document.getElementById('objGradeInput').value = grade;
  document.getElementById('objDayInput').value = dayOfWeek;
  document.getElementById('objDayLabel').textContent = 'Objetivo pedagógico de ' + dayOfWeek.toUpperCase() + ' (Semana ' + weekNum + ', Grado ' + grade + '):';
  document.getElementById('objTextInput').value = currentObjective || '';
  openModal('modalEditObjective');
}

function escapeHtml(text) {
  if (!text) return '';
  const map = { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' };
  return text.replace(/[&<>"']/g, m => map[m]);
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
