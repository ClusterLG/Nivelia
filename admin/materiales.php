<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/personajes.php';
requireAdmin();

$pdo = getDB();

// Lapsos académicos
$lapsosInfo = [
    1 => [
        'name' => 'Octubre 2026 (Semanas 1 a 4)',
        'icon' => '🍂',
        'badge' => 'Mes Activo: Fundamentos y Diagnóstico',
        'desc' => 'Nivelación inicial, comprensión lectora, fundamentos numéricos y fauna venezolana.',
        'weeks' => range(1, 4),
        'color' => '#4f46e5'
    ]
];

// Procesar Acciones POST del Administrador
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    // 1. Desplegar / Cargar semana específica desde el Banco a las Actividades Activas
    if ($action === 'deploy_week') {
        $grade = (int)$_POST['grade'];
        $week = (int)$_POST['week'];
        $lapso = (int)ceil($week / 10);

        // Obtener materiales del banco para esta semana
        $stmtBank = $pdo->prepare("SELECT * FROM material_bank WHERE grade = :g AND absolute_week = :w ORDER BY (CASE day_of_week WHEN 'lunes' THEN 1 WHEN 'martes' THEN 2 WHEN 'miercoles' THEN 3 WHEN 'jueves' THEN 4 WHEN 'viernes' THEN 5 ELSE 6 END), order_num ASC");
        $stmtBank->execute(['g' => $grade, 'w' => $week]);
        $materials = $stmtBank->fetchAll();

        $isPg = ($pdo->getAttribute(PDO::ATTR_DRIVER_NAME) === 'pgsql');
        $planSql = $isPg 
            ? "INSERT INTO weekly_plans (week_number, lapso, grade, day_of_week, objective, is_active)
               VALUES (:w, :lapso, :g, :d, :obj, 1)
               ON CONFLICT (week_number, grade, day_of_week) DO UPDATE SET objective = EXCLUDED.objective, is_active = 1, lapso = EXCLUDED.lapso"
            : "INSERT INTO weekly_plans (week_number, lapso, grade, day_of_week, objective, is_active)
               VALUES (:w, :lapso, :g, :d, :obj, 1)
               ON DUPLICATE KEY UPDATE objective = :obj, is_active = 1, lapso = :lapso";

        $loadedCount = 0;
        foreach ($materials as $m) {
            // Sincronizar plan semanal
            $stmtPlan = $pdo->prepare($planSql);
            $stmtPlan->execute([
                'w' => $week,
                'lapso' => $lapso,
                'g' => $grade,
                'd' => $m['day_of_week'],
                'obj' => $m['daily_objective']
            ]);

            // Sincronizar o reactivar actividad
            $stmtAct = $pdo->prepare("SELECT id FROM activities WHERE week_number = :w AND grade = :g AND day_of_week = :d AND title = :title");
            $stmtAct->execute(['w' => $week, 'g' => $grade, 'd' => $m['day_of_week'], 'title' => $m['title']]);
            $actId = $stmtAct->fetchColumn();

            if (!$actId) {
                $stmtInsert = $pdo->prepare("
                    INSERT INTO activities (week_number, lapso, grade, day_of_week, subject_id, title, summary, reading_content, practical_exercise, xp_reward, order_num, is_active)
                    VALUES (:w, :lapso, :g, :d, :sub, :title, :summary, :reading, :pract, :xp, :ord, 1)
                ");
                $stmtInsert->execute([
                    'w' => $week,
                    'lapso' => $lapso,
                    'g' => $grade,
                    'd' => $m['day_of_week'],
                    'sub' => $m['subject_id'],
                    'title' => $m['title'],
                    'summary' => $m['summary'],
                    'reading' => $m['reading_content'],
                    'pract' => $m['practical_exercise'],
                    'xp' => $m['xp_reward'],
                    'ord' => $m['order_num']
                ]);
                $actId = $pdo->lastInsertId();
            } else {
                $stmtUpdate = $pdo->prepare("UPDATE activities SET is_active = 1, lapso = :lapso WHERE id = :id");
                $stmtUpdate->execute(['lapso' => $lapso, 'id' => $actId]);
            }

            // Copiar preguntas si no las tiene
            if ($actId) {
                $qCount = $pdo->query("SELECT COUNT(*) FROM activity_questions WHERE activity_id = $actId")->fetchColumn();
                if ($qCount == 0) {
                    $stmtBQ = $pdo->prepare("SELECT * FROM material_bank_questions WHERE material_id = :mid");
                    $stmtBQ->execute(['mid' => $m['id']]);
                    $bankQs = $stmtBQ->fetchAll();

                    $stmtInsertQ = $pdo->prepare("
                        INSERT INTO activity_questions (activity_id, question_text, question_type, options_json, correct_answer, explanation, points)
                        VALUES (:aid, :text, :type, :opts, :corr, :expl, :pts)
                    ");
                    foreach ($bankQs as $bq) {
                        $stmtInsertQ->execute([
                            'aid' => $actId,
                            'text' => $bq['question_text'],
                            'type' => $bq['question_type'],
                            'opts' => $bq['options_json'],
                            'corr' => $bq['correct_answer'],
                            'expl' => $bq['explanation'],
                            'pts' => $bq['points']
                        ]);
                    }
                }
            }
            $loadedCount++;
        }

        // Marcar desplegada en el banco
        $pdo->prepare("UPDATE material_bank SET is_deployed = 1 WHERE grade = :g AND absolute_week = :w")->execute(['g' => $grade, 'w' => $week]);

        $stuName = $grade == 5 ? 'Dimas (5to Grado)' : 'Alexander (6to Grado)';
        $_SESSION['flash_success'] = "¡Semana $week cargada y habilitada con éxito para $stuName! ($loadedCount misiones activadas).";
        header("Location: materiales.php?lapso=$lapso&grade=$grade&week=$week");
        exit;
    }

    // 2. Desplegar lapso completo (10 semanas)
    if ($action === 'deploy_lapso') {
        $grade = (int)$_POST['grade'];
        $lapso = (int)$_POST['lapso'];
        $weeksInLapso = $lapsosInfo[$lapso]['weeks'];

        $pdo->prepare("UPDATE activities SET is_active = 1 WHERE grade = :g AND week_number BETWEEN :w1 AND :w2")
            ->execute(['g' => $grade, 'w1' => min($weeksInLapso), 'w2' => max($weeksInLapso)]);
        $pdo->prepare("UPDATE weekly_plans SET is_active = 1 WHERE grade = :g AND week_number BETWEEN :w1 AND :w2")
            ->execute(['g' => $grade, 'w1' => min($weeksInLapso), 'w2' => max($weeksInLapso)]);
        $pdo->prepare("UPDATE material_bank SET is_deployed = 1 WHERE grade = :g AND lapso = :l")
            ->execute(['g' => $grade, 'l' => $lapso]);

        $stuName = $grade == 5 ? 'Dimas (5to)' : 'Alexander (6to)';
        $_SESSION['flash_success'] = "¡Lapso $lapso completo (10 semanas) activado y disponible para $stuName!";
        header("Location: materiales.php?lapso=$lapso&grade=$grade");
        exit;
    }

    // 3. Pausar / Desactivar semana
    if ($action === 'pause_week') {
        $grade = (int)$_POST['grade'];
        $week = (int)$_POST['week'];
        $lapso = (int)ceil($week / 10);

        $pdo->prepare("UPDATE activities SET is_active = 0 WHERE grade = :g AND week_number = :w")->execute(['g' => $grade, 'w' => $week]);
        $pdo->prepare("UPDATE weekly_plans SET is_active = 0 WHERE grade = :g AND week_number = :w")->execute(['g' => $grade, 'w' => $week]);

        $_SESSION['flash_success'] = "Semana $week pausada. Los alumnos no verán las misiones hasta que decidas reactivarla.";
        header("Location: materiales.php?lapso=$lapso&grade=$grade&week=$week");
        exit;
    }
}

// Filtros GET
$selectedGrade = isset($_GET['grade']) ? (int)$_GET['grade'] : 5;
if (!in_array($selectedGrade, [5, 6])) $selectedGrade = 5;

$selectedLapso = isset($_GET['lapso']) ? (int)$_GET['lapso'] : 1;
if (!isset($lapsosInfo[$selectedLapso])) $selectedLapso = 1;

$curLapsoWeeks = $lapsosInfo[$selectedLapso]['weeks'];
$selectedWeek = isset($_GET['week']) ? (int)$_GET['week'] : $curLapsoWeeks[0];
if (!in_array($selectedWeek, $curLapsoWeeks)) {
    $selectedWeek = $curLapsoWeeks[0];
}

// Obtener datos de la semana seleccionada desde material_bank
$stmtWeekMat = $pdo->prepare("
    SELECT mb.*, s.name as subject_name, s.slug as subject_slug, s.icon as subject_icon, s.color as subject_color,
           (SELECT is_active FROM activities act WHERE act.week_number = mb.absolute_week AND act.grade = mb.grade AND act.day_of_week = mb.day_of_week AND act.title = mb.title LIMIT 1) as act_is_active,
           (SELECT COUNT(*) FROM material_bank_questions mbq WHERE mbq.material_id = mb.id) as q_count
    FROM material_bank mb
    JOIN subjects s ON mb.subject_id = s.id
    WHERE mb.grade = :g AND mb.absolute_week = :w
    ORDER BY (CASE mb.day_of_week WHEN 'lunes' THEN 1 WHEN 'martes' THEN 2 WHEN 'miercoles' THEN 3 WHEN 'jueves' THEN 4 WHEN 'viernes' THEN 5 ELSE 6 END), mb.order_num ASC
");
$stmtWeekMat->execute(['g' => $selectedGrade, 'w' => $selectedWeek]);
$weekMaterials = $stmtWeekMat->fetchAll();

// Indexar por día de la semana
$materialsByDay = [];
$materialIds = [];
$isWeekActive = true;
foreach ($weekMaterials as $m) {
    $materialsByDay[$m['day_of_week']][] = $m;
    $materialIds[] = $m['id'];
    if ($m['act_is_active'] === '0' || $m['act_is_active'] === 0) {
        $isWeekActive = false;
    }
}

// Cargar preguntas de los materiales para inspección
$questionsByMaterial = [];
if (!empty($materialIds)) {
    $inClause = implode(',', $materialIds);
    $qList = $pdo->query("SELECT * FROM material_bank_questions WHERE material_id IN ($inClause) ORDER BY id ASC")->fetchAll();
    foreach ($qList as $q) {
        $questionsByMaterial[$q['material_id']][] = $q;
    }
}

// Contadores de cobertura global
$bankStats = $pdo->query("
    SELECT grade, lapso, COUNT(DISTINCT absolute_week) as semanas, COUNT(*) as misiones
    FROM material_bank
    GROUP BY grade, lapso
")->fetchAll();

$pageTitle = 'Banco Curricular de Materiales (3 Lapsos)';
require_once __DIR__ . '/../includes/header.php';
?>

<!-- Estilos específicos para la vista de materiales -->
<style>
.lapso-banner {
  background: linear-gradient(135deg, <?= $lapsosInfo[$selectedLapso]['color'] ?> 0%, #1e1b4b 100%);
  color: #fff;
  border-radius: 20px;
  padding: 1.75rem 2rem;
  margin-bottom: 2rem;
  box-shadow: 0 10px 30px rgba(0,0,0,0.12);
  display: flex;
  align-items: center;
  justify-content: space-between;
  flex-wrap: wrap;
  gap: 1.25rem;
}
.lapso-tabs {
  display: flex;
  gap: 0.65rem;
  margin-bottom: 1.5rem;
  flex-wrap: wrap;
}
.lapso-tab-btn {
  display: inline-flex;
  align-items: center;
  gap: 0.5rem;
  padding: 0.75rem 1.25rem;
  border-radius: 14px;
  font-weight: 700;
  text-decoration: none;
  border: 2px solid transparent;
  transition: all 0.25s ease;
  font-size: 0.95rem;
}
.lapso-tab-btn.active {
  background: #fff;
  color: #1e293b;
  border-color: #e2e8f0;
  box-shadow: 0 4px 14px rgba(0,0,0,0.08);
}
.lapso-tab-btn:not(.active) {
  background: #f1f5f9;
  color: #64748b;
}
.lapso-tab-btn:hover:not(.active) {
  background: #e2e8f0;
  color: #1e293b;
}
.weeks-nav-bar {
  display: flex;
  gap: 0.5rem;
  overflow-x: auto;
  padding-bottom: 0.5rem;
  margin-bottom: 1.75rem;
}
.week-pill {
  flex: 0 0 auto;
  padding: 0.65rem 1.15rem;
  border-radius: 12px;
  font-weight: 700;
  font-size: 0.88rem;
  text-decoration: none;
  display: flex;
  flex-direction: column;
  align-items: center;
  border: 1.5px solid #e2e8f0;
  background: #fff;
  color: #475569;
  transition: all 0.2s ease;
}
.week-pill.active {
  background: <?= $lapsosInfo[$selectedLapso]['color'] ?>;
  color: #fff;
  border-color: <?= $lapsosInfo[$selectedLapso]['color'] ?>;
  box-shadow: 0 4px 12px rgba(79, 70, 229, 0.3);
}
.week-pill:hover:not(.active) {
  background: #f8fafc;
  border-color: #cbd5e1;
}
.day-column-card {
  background: #fff;
  border-radius: 16px;
  border: 1.5px solid #e2e8f0;
  overflow: hidden;
  box-shadow: 0 4px 16px rgba(0,0,0,0.04);
  display: flex;
  flex-direction: column;
}
.day-column-header {
  padding: 0.9rem 1.15rem;
  background: #f8fafc;
  border-bottom: 1.5px solid #e2e8f0;
  display: flex;
  align-items: center;
  justify-content: space-between;
}
.mission-detail-card {
  padding: 1.15rem;
  border-bottom: 1px solid #f1f5f9;
  flex: 1;
}
.details-toggle {
  background: #f8fafc;
  border: 1px solid #e2e8f0;
  border-radius: 8px;
  padding: 0.65rem 0.85rem;
  margin-top: 0.75rem;
  font-size: 0.84rem;
  cursor: pointer;
}
</style>

<div style="margin-bottom: 2rem;">
  
  <!-- Encabezado Principal y Selector de Alumno -->
  <div style="display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 1rem; margin-bottom: 1.5rem;">
    <div>
      <div style="display: inline-flex; align-items: center; gap: 0.4rem; background: #e0e7ff; color: #4338ca; padding: 0.3rem 0.75rem; border-radius: 999px; font-size: 0.8rem; font-weight: 800; text-transform: uppercase; margin-bottom: 0.4rem;">
        <span>📚</span>
        <span>Banco Curricular Oficial de Primaria</span>
      </div>
      <h1 style="font-size: 1.9rem; margin: 0;">Currículo Maestro y Materiales de Estudio</h1>
      <p style="font-size: 0.95rem; color: var(--text-secondary); margin-top: 0.25rem;">
        3 Lapsos Consecutivos &bull; 10 Semanas cada uno &bull; 30 Semanas completas sin repetición por grado.
      </p>
    </div>

    <!-- Switch de Estudiante (5to Dimas / 6to Alexander) -->
    <div style="display: flex; gap: 0.65rem; background: #fff; padding: 0.4rem; border-radius: 14px; border: 1.5px solid #e2e8f0; box-shadow: var(--shadow-sm);">
      <a href="?grade=5&lapso=<?= $selectedLapso ?>&week=<?= $selectedWeek ?>" 
         class="btn btn-sm <?= $selectedGrade == 5 ? 'btn-primary' : 'btn-secondary' ?>" style="font-weight: 700;">
        <span>⚡</span>
        <span>5to Grado &bull; Dimas</span>
      </a>
      <a href="?grade=6&lapso=<?= $selectedLapso ?>&week=<?= $selectedWeek ?>" 
         class="btn btn-sm <?= $selectedGrade == 6 ? 'btn-primary' : 'btn-secondary' ?>" style="font-weight: 700;">
        <span>🚀</span>
        <span>6to Grado &bull; Alexander</span>
      </a>
    </div>
  </div>

  <!-- Pestañas de los 3 Lapsos Consecutivos -->
  <div class="lapso-tabs">
    <?php foreach ($lapsosInfo as $lNum => $lInfo): ?>
      <a href="?grade=<?= $selectedGrade ?>&lapso=<?= $lNum ?>&week=<?= $lInfo['weeks'][0] ?>" 
         class="lapso-tab-btn <?= $selectedLapso === $lNum ? 'active' : '' ?>">
        <span style="font-size: 1.25rem;"><?= $lInfo['icon'] ?></span>
        <div>
          <div style="line-height: 1.2;"><?= $lInfo['name'] ?></div>
          <small style="font-size: 0.72rem; opacity: 0.8; font-weight: 600;"><?= $lInfo['badge'] ?></small>
        </div>
      </a>
    <?php endforeach; ?>
  </div>

  <!-- Banner Informativo del Lapso Seleccionado -->
  <div class="lapso-banner">
    <div>
      <div style="display: inline-flex; align-items: center; gap: 0.35rem; background: rgba(255,255,255,0.2); padding: 0.25rem 0.65rem; border-radius: 999px; font-size: 0.78rem; font-weight: 800; text-transform: uppercase;">
        <span><?= $lapsosInfo[$selectedLapso]['icon'] ?></span>
        <span><?= $lapsosInfo[$selectedLapso]['badge'] ?></span>
      </div>
      <h2 style="font-size: 1.45rem; margin: 0.5rem 0 0.25rem 0;"><?= $lapsosInfo[$selectedLapso]['name'] ?> &bull; <?= $selectedGrade ?>to Grado</h2>
      <p style="font-size: 0.9rem; opacity: 0.9; margin: 0; max-width: 650px;">
        <?= $lapsosInfo[$selectedLapso]['desc'] ?>
      </p>
    </div>

    <!-- Botón para Cargar / Desplegar Lapso Completo -->
    <form method="POST" action="materiales.php" onsubmit="return confirm('¿Deseas activar las 10 semanas de este lapso para el alumno?');">
      <input type="hidden" name="action" value="deploy_lapso">
      <input type="hidden" name="grade" value="<?= $selectedGrade ?>">
      <input type="hidden" name="lapso" value="<?= $selectedLapso ?>">
      <button type="submit" class="btn" style="background: #fff; color: #1e1b4b; font-weight: 800; box-shadow: 0 4px 14px rgba(0,0,0,0.15);">
        <span>📦</span>
        <span>Cargar Lapso Completo (10 Semanas)</span>
      </button>
    </form>
  </div>

  <!-- Barra Selectora de las 10 Semanas del Lapso -->
  <div>
    <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 0.65rem;">
      <h3 style="font-size: 1.05rem; font-weight: 700; color: #334155;">
        📅 Selecciona una de las 10 Semanas del Lapso:
      </h3>
      <span style="font-size: 0.85rem; color: #64748b;">
        Semanas <?= min($curLapsoWeeks) ?> a <?= max($curLapsoWeeks) ?>
      </span>
    </div>

    <div class="weeks-nav-bar">
      <?php foreach ($curLapsoWeeks as $w): 
        $wRel = (($w - 1) % 10) + 1;
      ?>
        <a href="?grade=<?= $selectedGrade ?>&lapso=<?= $selectedLapso ?>&week=<?= $w ?>" 
           class="week-pill <?= $selectedWeek === $w ? 'active' : '' ?>">
          <span style="font-size: 0.72rem; opacity: 0.8; text-transform: uppercase;">Sem. <?= $wRel ?></span>
          <span style="font-size: 1.05rem;">Semana <?= $w ?></span>
        </a>
      <?php endforeach; ?>
    </div>
  </div>

  <!-- Barra de Control de la Semana Seleccionada -->
  <div style="background: #fff; border: 1.5px solid #e2e8f0; border-radius: 16px; padding: 1.15rem 1.5rem; margin-bottom: 1.75rem; display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 1rem; box-shadow: var(--shadow-sm);">
    <div>
      <div style="font-size: 0.82rem; font-weight: 700; color: #64748b; text-transform: uppercase;">
        Explorando Contenido de:
      </div>
      <h3 style="font-size: 1.25rem; margin: 0.2rem 0; color: #1e293b;">
        Semana <?= $selectedWeek ?> &bull; <?= $selectedGrade ?>to Grado (<?= $selectedGrade == 5 ? 'Dimas' : 'Alexander' ?>)
      </h3>
      <div style="display: flex; align-items: center; gap: 0.5rem; font-size: 0.85rem; color: #64748b;">
        <span>5 Misiones Escolares (Lunes a Viernes)</span>
        <span>&bull;</span>
        <span style="color: #059669; font-weight: 700;">✅ Preguntas Evaluables Incluidas</span>
      </div>
    </div>

    <div style="display: flex; gap: 0.65rem; flex-wrap: wrap;">
      <!-- Botón para Cargar / Activar esta semana -->
      <form method="POST" action="materiales.php">
        <input type="hidden" name="action" value="deploy_week">
        <input type="hidden" name="grade" value="<?= $selectedGrade ?>">
        <input type="hidden" name="week" value="<?= $selectedWeek ?>">
        <button type="submit" class="btn btn-primary" title="Cargar a la vista del estudiante">
          <span>🚀</span>
          <span>Cargar / Actualizar Esta Semana</span>
        </button>
      </form>

      <!-- Botón para Pausar / Ocultar la semana -->
      <form method="POST" action="materiales.php" onsubmit="return confirm('¿Deseas pausar esta semana para que el alumno no la vea?');">
        <input type="hidden" name="action" value="pause_week">
        <input type="hidden" name="grade" value="<?= $selectedGrade ?>">
        <input type="hidden" name="week" value="<?= $selectedWeek ?>">
        <button type="submit" class="btn btn-secondary" title="Pausar semana temporalmente">
          <span>⏸️</span>
          <span>Pausar Semana</span>
        </button>
      </form>

      <a href="calendario.php?week=<?= $selectedWeek ?>&kid=<?= $selectedGrade ?>" class="btn btn-secondary" title="Ver en Calendario">
        <span>🗓️</span>
        <span>Ver en Calendario</span>
      </a>
    </div>
  </div>

  <!-- Rejilla de los 5 Días de la Semana (Lunes a Viernes) -->
  <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 1.25rem;">
    <?php 
    $daysList = ['lunes', 'martes', 'miercoles', 'jueves', 'viernes'];
    foreach ($daysList as $dSlug):
      $dayMats = $materialsByDay[$dSlug] ?? [];
    ?>
      <div class="day-column-card">
        <div class="day-column-header">
          <div>
            <span style="font-size: 0.78rem; text-transform: uppercase; font-weight: 800; color: #64748b;">
              Día <?= array_search($dSlug, $daysList) + 1 ?>
            </span>
            <h4 style="font-size: 1.1rem; margin: 0.15rem 0 0 0; color: #1e293b;">
              <?= getDayNameSpanish($dSlug) ?>
            </h4>
          </div>
          <span style="font-size: 1.35rem;">
            <?= $dSlug === 'lunes' ? '🦜' : ($dSlug === 'martes' ? '🦎' : ($dSlug === 'miercoles' ? '🐬' : ($dSlug === 'jueves' ? '🦦' : '🐾'))) ?>
          </span>
        </div>

        <?php if (empty($dayMats)): ?>
          <div style="padding: 2rem 1rem; text-align: center; color: #94a3b8; font-size: 0.88rem;">
            Sin contenido cargado para este día.
          </div>
        <?php else: ?>
          <?php foreach ($dayMats as $mat): 
            $matQuestions = $questionsByMaterial[$mat['id']] ?? [];
            $tutor = getPersonajeBySubject($mat['subject_slug']);
          ?>
            <div class="mission-detail-card">
              <!-- Cabecera de la materia -->
              <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 0.65rem;">
                <span class="badge-subject badge-<?= $mat['subject_slug'] ?>" style="font-size: 0.76rem;">
                  <?= $mat['subject_icon'] ?> <?= htmlspecialchars($mat['subject_name']) ?>
                </span>
                <span style="font-size: 0.75rem; font-weight: 800; color: #d97706; background: #fef3c7; padding: 0.2rem 0.5rem; border-radius: 999px;">
                  ⚡ +<?= $mat['xp_reward'] ?> XP
                </span>
              </div>

              <!-- Título y Resumen -->
              <h5 style="font-size: 0.98rem; font-weight: 800; color: #1e293b; margin-bottom: 0.35rem; line-height: 1.35;">
                <?= htmlspecialchars($mat['title']) ?>
              </h5>
              <p style="font-size: 0.84rem; color: #64748b; line-height: 1.45; margin-bottom: 0.65rem;">
                <?= htmlspecialchars($mat['summary']) ?>
              </p>

              <!-- Objetivo Pedagógico -->
              <div style="background: #f8fafc; border-left: 3px solid #6366f1; padding: 0.45rem 0.65rem; border-radius: 4px; font-size: 0.8rem; color: #475569; margin-bottom: 0.65rem;">
                <strong>🎯 Objetivo:</strong> <?= htmlspecialchars($mat['daily_objective']) ?>
              </div>

              <!-- Tutor Fauna Vinculado -->
              <?php if ($tutor): ?>
                <div style="display: inline-flex; align-items: center; gap: 0.35rem; font-size: 0.75rem; font-weight: 700; color: <?= htmlspecialchars($tutor['color_tema']) ?>; margin-bottom: 0.65rem;">
                  <img src="<?= $baseUrl ?>assets/images/personajes/<?= htmlspecialchars($tutor['archivo_render']) ?>" 
                       alt="<?= htmlspecialchars($tutor['nombre']) ?>" 
                       style="width: 18px; height: 18px; border-radius: 50%; object-fit: contain;">
                  <span>Tutor: <?= htmlspecialchars($tutor['nombre']) ?></span>
                </div>
              <?php endif; ?>

              <!-- Desplegable de Contenido Teórico y Ejercicio Práctico -->
              <details class="details-toggle">
                <summary style="font-weight: 700; color: #4338ca; cursor: pointer; outline: none;">
                  📖 Ver Lectura Teórica y Ejercicio Práctico
                </summary>
                <div style="margin-top: 0.75rem; font-size: 0.82rem; color: #334155; line-height: 1.5; white-space: pre-line; border-top: 1px dashed #cbd5e1; padding-top: 0.65rem;">
                  <strong>Lectura Formativa:</strong>
                  <?= htmlspecialchars($mat['reading_content']) ?>
                  
                  <?php if (!empty($mat['practical_exercise'])): ?>
                    <div style="margin-top: 0.65rem; background: #e0f2fe; padding: 0.5rem 0.65rem; border-radius: 6px; color: #0369a1;">
                      <strong>✍️ Reto en el Cuaderno:</strong> <?= htmlspecialchars($mat['practical_exercise']) ?>
                    </div>
                  <?php endif; ?>
                </div>
              </details>

              <!-- Desplegable de Preguntas de Evaluación -->
              <details class="details-toggle" style="margin-top: 0.45rem; background: #f0fdf4; border-color: #bbf7d0;">
                <summary style="font-weight: 700; color: #15803d; cursor: pointer; outline: none;">
                  📝 Ver Preguntas de Evaluación (<?= count($matQuestions) ?>)
                </summary>
                <div style="margin-top: 0.65rem; font-size: 0.82rem; color: #166534; border-top: 1px dashed #bbf7d0; padding-top: 0.65rem;">
                  <?php foreach ($matQuestions as $idx => $q): 
                    $opts = !empty($q['options_json']) ? json_decode($q['options_json'], true) : [];
                  ?>
                    <div style="margin-bottom: 0.65rem; background: #fff; padding: 0.5rem; border-radius: 6px; border: 1px solid #dcfce7;">
                      <div style="font-weight: 700; margin-bottom: 0.25rem;">
                        <?= $idx + 1 ?>. <?= htmlspecialchars($q['question_text']) ?>
                      </div>
                      <?php if (!empty($opts)): ?>
                        <div style="font-size: 0.76rem; color: #475569; margin-bottom: 0.25rem;">
                          Opciones: <?= implode(' &bull; ', array_map('htmlspecialchars', $opts)) ?>
                        </div>
                      <?php endif; ?>
                      <div style="font-size: 0.76rem; font-weight: 700; color: #15803d;">
                        ✓ Respuesta Correcta: <code><?= htmlspecialchars($q['correct_answer']) ?></code>
                      </div>
                      <?php if (!empty($q['explanation'])): ?>
                        <div style="font-size: 0.72rem; color: #64748b; margin-top: 0.15rem;">
                          <em>Explicación: <?= htmlspecialchars($q['explanation']) ?></em>
                        </div>
                      <?php endif; ?>
                    </div>
                  <?php endforeach; ?>
                </div>
              </details>

            </div>
          <?php endforeach; ?>
        <?php endif; ?>
      </div>
    <?php endforeach; ?>
  </div>

</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
