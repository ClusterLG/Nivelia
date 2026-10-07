<?php
require_once __DIR__ . '/../config/db.php';
requireAdmin();

$pdo = getDB();

// Procesar acciones POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    // 1. Actualizar objetivo del día
    if ($action === 'update_objective') {
        $planId = (int)$_POST['plan_id'];
        $newObjective = trim($_POST['objective'] ?? '');
        if ($planId && !empty($newObjective)) {
            $stmt = $pdo->prepare("UPDATE weekly_plans SET objective = :obj WHERE id = :id");
            $stmt->execute(['obj' => $newObjective, 'id' => $planId]);
            $_SESSION['flash_success'] = 'Objetivo pedagógico actualizado con éxito.';
        }
    }

    // 2. Alternar estado activo de una actividad (Pausar / Activar)
    if ($action === 'toggle_activity') {
        $activityId = (int)$_POST['activity_id'];
        $stmt = $pdo->prepare("UPDATE activities SET is_active = IF(is_active = 1, 0, 1) WHERE id = :id");
        $stmt->execute(['id' => $activityId]);
        $_SESSION['flash_success'] = 'Estado de la actividad actualizado.';
    }

    // 3. Crear nueva actividad rápida
    if ($action === 'create_activity') {
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
                VALUES (1, :grade, :day, :sub, :title, :summary, :reading, :pract, :xp, 99, 1)
            ");
            $stmt->execute([
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

            // Pregunta rápida asociada
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

            $_SESSION['flash_success'] = '¡Nueva actividad planificada y publicada para los niños!';
        }
    }

    header('Location: planner.php');
    exit;
}

// Filtro de grado
$filterGrade = isset($_GET['grade']) ? (int)$_GET['grade'] : 0; // 0 = Ambos

// Obtener todas las materias para el modal
$subjects = $pdo->query("SELECT * FROM subjects ORDER BY name ASC")->fetchAll();

// Días de la semana
$daysOfWeek = ['lunes', 'martes', 'miercoles', 'jueves', 'viernes'];

// Filtro de semana
$filterWeek = isset($_GET['week']) ? max(1, (int)$_GET['week']) : 1;

// Cargar planes diarios
$plansQuery = "SELECT * FROM weekly_plans WHERE week_number = $filterWeek";
if ($filterGrade > 0) {
    $plansQuery .= " AND grade = $filterGrade";
}
$plansQuery .= " ORDER BY grade ASC, (CASE day_of_week WHEN 'lunes' THEN 1 WHEN 'martes' THEN 2 WHEN 'miercoles' THEN 3 WHEN 'jueves' THEN 4 WHEN 'viernes' THEN 5 ELSE 6 END)";
$plans = $pdo->query($plansQuery)->fetchAll();

// Indexar planes por [grade][day]
$planMap = [];
foreach ($plans as $p) {
    $planMap[$p['grade']][$p['day_of_week']] = $p;
}

// Cargar todas las actividades
$actQuery = "
    SELECT a.*, s.name as subject_name, s.slug as subject_slug, s.icon as subject_icon, s.color as subject_color
    FROM activities a
    JOIN subjects s ON a.subject_id = s.id
    WHERE a.week_number = $filterWeek
";
if ($filterGrade > 0) {
    $actQuery .= " AND a.grade = $filterGrade";
}
$actQuery .= " ORDER BY a.grade ASC, (CASE a.day_of_week WHEN 'lunes' THEN 1 WHEN 'martes' THEN 2 WHEN 'miercoles' THEN 3 WHEN 'jueves' THEN 4 WHEN 'viernes' THEN 5 ELSE 6 END), a.order_num ASC";
$activities = $pdo->query($actQuery)->fetchAll();

// Indexar actividades por [grade][day]
$actMap = [];
foreach ($activities as $a) {
    $actMap[$a['grade']][$a['day_of_week']][] = $a;
}

$pageTitle = "Plan Semanal (Semana $filterWeek)";
require_once __DIR__ . '/../includes/header.php';
?>

<div style="margin-bottom: 2rem;">
  
  <div style="display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 1rem; margin-bottom: 1.5rem;">
    <div>
      <div style="display: flex; align-items: center; gap: 0.5rem; margin-bottom: 0.25rem;">
        <span style="font-size: 1.8rem;">📅</span>
        <h1 style="font-size: 1.85rem; margin: 0;">Plan Semanal de Actividades — Semana <?= $filterWeek ?></h1>
      </div>
      <p style="color: var(--text-secondary); margin: 0;">
        Organiza día a día los objetivos didácticos y las misiones que verán Alexander y Dimas en sus teléfonos.
      </p>
    </div>

    <div style="display: flex; gap: 0.5rem; flex-wrap: wrap;">
      <a href="calendario.php?week=<?= $filterWeek ?>" class="btn btn-secondary btn-sm" title="Ver en el Calendario Mensual">
        <span>🗓️ Ver Calendario</span>
      </a>
      <a href="?week=<?= $filterWeek ?>&grade=0" class="btn btn-sm <?= $filterGrade === 0 ? 'btn-primary' : 'btn-secondary' ?>">Todos</a>
      <a href="?week=<?= $filterWeek ?>&grade=5" class="btn btn-sm <?= $filterGrade === 5 ? 'btn-primary' : 'btn-secondary' ?>">⚡ Dimas (5to)</a>
      <a href="?week=<?= $filterWeek ?>&grade=6" class="btn btn-sm <?= $filterGrade === 6 ? 'btn-primary' : 'btn-secondary' ?>">🚀 Alex (6to)</a>
      <button class="btn btn-success btn-sm" onclick="openModal('modalNewActivity')">
        ➕ Nueva Actividad
      </button>
    </div>
  </div>

  <!-- Selector rápido de semanas -->
  <div style="display: flex; gap: 0.5rem; margin-bottom: 1.5rem; overflow-x: auto; padding-bottom: 0.35rem;">
    <?php for ($sw = 1; $sw <= 4; $sw++): ?>
      <a href="?week=<?= $sw ?>&grade=<?= $filterGrade ?>" 
         class="btn btn-sm <?= $filterWeek === $sw ? 'btn-primary' : 'btn-secondary' ?>" 
         style="font-size: 0.85rem;">
        Semana <?= $sw ?>
      </a>
    <?php endfor; ?>
  </div>

  <?php 
    $gradesToRender = ($filterGrade > 0) ? [$filterGrade] : [5, 6];
    foreach ($gradesToRender as $gr):
      $kidName = ($gr === 5) ? 'Dimas (10 años)' : 'Alexander (11 años)';
      $kidBadgeClass = ($gr === 5) ? 'badge-matematicas' : 'badge-lectura';
      $kidIcon = ($gr === 5) ? '⚡' : '🚀';
  ?>

    <div style="margin-bottom: 2.5rem;">
      <div style="display: flex; align-items: center; gap: 0.75rem; margin-bottom: 1rem;">
        <span style="font-size: 2rem;"><?= $kidIcon ?></span>
        <div>
          <h2 style="font-size: 1.4rem; color: var(--text-primary);">
            <?= $kidName ?> — <?= $gr ?>to Grado de Primaria
          </h2>
          <span class="badge-subject <?= $kidBadgeClass ?>">Currículo Activo: Semana 1</span>
        </div>
      </div>

      <!-- Gran Tabla Semanal -->
      <div class="planner-table-wrapper">
        <table class="planner-table">
          <thead>
            <tr>
              <th style="width: 130px;">Día</th>
              <th style="width: 320px;">Objetivo Pedagógico del Día</th>
              <th>Actividades y Misiones Programadas</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($daysOfWeek as $day): 
              $currentPlan = $planMap[$gr][$day] ?? null;
              $dayActivities = $actMap[$gr][$day] ?? [];
            ?>
              <tr>
                <td class="planner-day-col">
                  <div style="font-size: 1.1rem;"><?= getDayNameSpanish($day) ?></div>
                  <span style="font-size: 0.75rem; color: var(--text-muted); font-weight: 500;">
                    <?= count($dayActivities) ?> misiones
                  </span>
                </td>

                <td>
                  <div class="planner-objective-box">
                    <?= htmlspecialchars($currentPlan['objective'] ?? 'Sin objetivo definido') ?>
                  </div>
                  <?php if ($currentPlan): ?>
                    <button class="btn btn-secondary btn-sm" style="font-size: 0.75rem; padding: 0.25rem 0.6rem;" 
                            onclick="editObjective(<?= $currentPlan['id'] ?>, '<?= getDayNameSpanish($day) ?>', <?= $gr ?>, '<?= htmlspecialchars(addslashes($currentPlan['objective']), ENT_QUOTES) ?>')">
                      ✏️ Editar Objetivo
                    </button>
                  <?php endif; ?>
                </td>

                <td>
                  <?php if (empty($dayActivities)): ?>
                    <p style="font-size: 0.85rem; color: var(--text-muted); font-style: italic;">
                      No hay actividades asignadas.
                    </p>
                  <?php else: ?>
                    <div class="planner-activities-list">
                      <?php foreach ($dayActivities as $act): ?>
                        <div class="planner-activity-item" style="opacity: <?= $act['is_active'] ? '1' : '0.5' ?>; border-left: 4px solid <?= $act['subject_color'] ?>;">
                          <div style="display: flex; align-items: center; gap: 0.6rem; flex-wrap: wrap;">
                            <span class="badge-subject badge-<?= $act['subject_slug'] ?>" style="font-size: 0.75rem; padding: 0.2rem 0.5rem;">
                              <?= $act['subject_icon'] ?> <?= htmlspecialchars($act['subject_name']) ?>
                            </span>
                            <strong style="color: var(--text-primary); font-size: 0.9rem;">
                              <?= htmlspecialchars($act['title']) ?>
                            </strong>
                            <span style="color: #d97706; font-size: 0.8rem; font-weight: 700;">
                              ⚡ +<?= $act['xp_reward'] ?> XP
                            </span>
                          </div>

                          <div style="display: flex; align-items: center; gap: 0.5rem;">
                            <form method="POST" action="planner.php" style="margin: 0;">
                              <input type="hidden" name="action" value="toggle_activity">
                              <input type="hidden" name="activity_id" value="<?= $act['id'] ?>">
                              <button type="submit" class="btn btn-sm <?= $act['is_active'] ? 'btn-success' : 'btn-secondary' ?>" 
                                      style="font-size: 0.75rem; padding: 0.25rem 0.6rem;"
                                      title="<?= $act['is_active'] ? 'Clic para pausar (ocultar al niño)' : 'Clic para activar (mostrar al niño)' ?>">
                                <?= $act['is_active'] ? '🟢 Activa' : '⚪ Pausada' ?>
                              </button>
                            </form>

                            <a href="../student/activity.php?id=<?= $act['id'] ?>" class="btn btn-secondary btn-sm" 
                               style="font-size: 0.75rem; padding: 0.25rem 0.5rem;" target="_blank" title="Ver cómo la ve el niño">
                              👁️
                            </a>
                          </div>
                        </div>
                      <?php endforeach; ?>
                    </div>
                  <?php endif; ?>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>

  <?php endforeach; ?>

</div>

<!-- MODAL: Editar Objetivo del Día -->
<div class="modal-overlay" id="modalEditObjective">
  <div class="modal-content">
    <div class="modal-header">
      <h3 id="editObjTitle">Editar Objetivo Diario</h3>
      <button class="close-modal" onclick="closeModal('modalEditObjective')">&times;</button>
    </div>

    <form method="POST" action="planner.php">
      <input type="hidden" name="action" value="update_objective">
      <input type="hidden" name="plan_id" id="editPlanId" value="">

      <div class="form-group">
        <label class="form-label" for="editPlanObjective">Objetivo Didáctico que leerá el estudiante:</label>
        <textarea name="objective" id="editPlanObjective" class="input-textarea" style="min-height: 120px;" required></textarea>
      </div>

      <div style="display: flex; justify-content: flex-end; gap: 0.75rem;">
        <button type="button" class="btn btn-secondary" onclick="closeModal('modalEditObjective')">Cancelar</button>
        <button type="submit" class="btn btn-primary">Guardar Objetivo</button>
      </div>
    </form>
  </div>
</div>

<!-- MODAL: Crear Nueva Actividad -->
<div class="modal-overlay" id="modalNewActivity">
  <div class="modal-content" style="max-width: 650px;">
    <div class="modal-header">
      <h3>➕ Planificar Nueva Actividad</h3>
      <button class="close-modal" onclick="closeModal('modalNewActivity')">&times;</button>
    </div>

    <form method="POST" action="planner.php">
      <input type="hidden" name="action" value="create_activity">

      <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
        <div class="form-group">
          <label class="form-label">Estudiante / Grado</label>
          <select name="grade" class="form-control" required>
            <option value="5">Dimas (5to Grado)</option>
            <option value="6">Alexander (6to Grado)</option>
          </select>
        </div>

        <div class="form-group">
          <label class="form-label">Día de la Semana</label>
          <select name="day_of_week" class="form-control" required>
            <option value="lunes">Lunes</option>
            <option value="martes">Martes</option>
            <option value="miercoles">Miércoles</option>
            <option value="jueves">Jueves</option>
            <option value="viernes">Viernes</option>
          </select>
        </div>
      </div>

      <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 1rem;">
        <div class="form-group">
          <label class="form-label">Materia</label>
          <select name="subject_id" class="form-control" required>
            <?php foreach ($subjects as $s): ?>
              <option value="<?= $s['id'] ?>"><?= $s['icon'] ?> <?= htmlspecialchars($s['name']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>

        <div class="form-group">
          <label class="form-label">Recompensa XP</label>
          <input type="number" name="xp_reward" class="form-control" value="50" min="10" max="200" required>
        </div>
      </div>

      <div class="form-group">
        <label class="form-label">Título de la Actividad</label>
        <input type="text" name="title" class="form-control" placeholder="Ej: Suma de fracciones en la cocina" required>
      </div>

      <div class="form-group">
        <label class="form-label">Resumen Breve (1 o 2 líneas)</label>
        <input type="text" name="summary" class="form-control" placeholder="Ej: Aprende a calcular ingredientes usando mitades y cuartos" required>
      </div>

      <div class="form-group">
        <label class="form-label">Contenido Didáctico / Lectura / Explicación</label>
        <textarea name="reading_content" class="input-textarea" style="min-height: 140px;" placeholder="Escribe la explicación paso a paso o la lectura didáctica que leerán los niños..." required></textarea>
      </div>

      <div class="form-group">
        <label class="form-label">Reto Práctico (Opcional)</label>
        <input type="text" name="practical_exercise" class="form-control" placeholder="Ej: Mide con una taza en la cocina...">
      </div>

      <div style="background: #f8fafc; padding: 1rem; border-radius: var(--radius-md); border: 1px dashed var(--border); margin-bottom: 1.25rem;">
        <h4 style="font-size: 0.95rem; margin-bottom: 0.5rem; color: var(--text-primary);">✍️ Pregunta de Evaluación</h4>
        <div class="form-group">
          <input type="text" name="question_text" class="form-control" placeholder="Escribe la pregunta que deberán responder..." required>
        </div>
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
          <div class="form-group">
            <select name="question_type" class="form-control" id="newQType" onchange="toggleOptionsField(this.value)">
              <option value="multiple_choice">Selección Múltiple</option>
              <option value="true_false">Verdadero o Falso</option>
              <option value="reflexive">Pregunta Abierta / Redacción</option>
            </select>
          </div>
          <div class="form-group">
            <input type="text" name="correct_answer" class="form-control" placeholder="Respuesta correcta exacta">
          </div>
        </div>
        <div class="form-group" id="optionsGroup">
          <label class="form-label" style="font-size: 0.8rem;">Opciones separadas por coma:</label>
          <input type="text" name="options_raw" class="form-control" placeholder="Opción A, Opción B, Opción C">
        </div>
      </div>

      <div style="display: flex; justify-content: flex-end; gap: 0.75rem;">
        <button type="button" class="btn btn-secondary" onclick="closeModal('modalNewActivity')">Cancelar</button>
        <button type="submit" class="btn btn-success">Publicar Actividad en el Plan</button>
      </div>
    </form>
  </div>
</div>

<script>
function editObjective(planId, dayName, grade, objectiveText) {
  document.getElementById('editPlanId').value = planId;
  document.getElementById('editObjTitle').innerText = 'Editar Objetivo de ' + dayName + ' (' + grade + 'to Grado)';
  document.getElementById('editPlanObjective').value = objectiveText;
  openModal('modalEditObjective');
}

function toggleOptionsField(val) {
  const optsGroup = document.getElementById('optionsGroup');
  if (val === 'multiple_choice') {
    optsGroup.style.display = 'block';
  } else {
    optsGroup.style.display = 'none';
  }
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
