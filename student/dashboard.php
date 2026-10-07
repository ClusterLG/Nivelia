<?php
require_once __DIR__ . '/../config/db.php';
requireLogin();

$user = currentUser();
$pdo = getDB();

// Si un admin entra aquí, le permitimos simular a Alex o Dimas para probar la vista de sus hijos
$grade = $user['grade'] ?? 6;
if (isAdmin()) {
    $grade = isset($_GET['simulate_grade']) ? (int)$_GET['simulate_grade'] : 6;
}

// Día seleccionado (por defecto el día actual o lunes si es fin de semana)
$currentDaySlug = getCurrentDaySlug();
if ($currentDaySlug === 'sabado' || $currentDaySlug === 'domingo') {
    $currentDaySlug = 'lunes';
}
$selectedDay = $_GET['day'] ?? $currentDaySlug;
$validDays = ['lunes', 'martes', 'miercoles', 'jueves', 'viernes'];
if (!in_array($selectedDay, $validDays)) {
    $selectedDay = 'lunes';
}

// Determinar la semana activa asignada al grado (limitado al mes de Octubre 2026: Semanas 1 a 4)
$activeWeek = min(4, max(1, getActiveWeekForGrade($grade)));
if (!isAdmin()) {
    $requestedWeek = isset($_GET['week']) ? (int)$_GET['week'] : $activeWeek;
    if ($requestedWeek > $activeWeek) {
        $_SESSION['flash_error'] = "🔒 La Semana $requestedWeek aún no ha comenzado. Tu semana activa de misiones es la Semana $activeWeek.";
        $selectedWeek = $activeWeek;
    } else {
        $selectedWeek = min(4, max(1, $requestedWeek));
    }
} else {
    $selectedWeek = isset($_GET['week']) ? min(4, max(1, (int)$_GET['week'])) : $activeWeek;
}

// Bloqueo de días futuros: Los alumnos solo pueden ver días transcurridos y el actual (medianoche)
if (!isDayUnlockedForStudents($selectedDay, $selectedWeek, $grade)) {
    $_SESSION['flash_error'] = "🔒 Las misiones de " . getDayNameSpanish($selectedDay) . " aún no están disponibles. Solo puedes ver las actividades de hoy y de los días que ya pasaron en tu semana activa. Los días se actualizan automáticamente a medianoche.";
    $selectedDay = $currentDaySlug;
}

// Obtener el objetivo pedagógico del día para este grado
$stmtPlan = $pdo->prepare("SELECT objective FROM weekly_plans WHERE week_number = :week AND grade = :grade AND day_of_week = :day AND is_active = 1");
$stmtPlan->execute(['week' => $selectedWeek, 'grade' => $grade, 'day' => $selectedDay]);
$dailyObjective = $stmtPlan->fetchColumn() ?: '¡Día de aprendizaje, retos y descubrimientos!';

// Obtener las actividades del día para este grado
$stmtAct = $pdo->prepare("
    SELECT a.*, s.name as subject_name, s.slug as subject_slug, s.icon as subject_icon, s.color as subject_color,
           sub.id as submission_id, sub.score, sub.max_score, sub.parent_stars, sub.parent_feedback, sub.status as sub_status
    FROM activities a
    JOIN subjects s ON a.subject_id = s.id
    LEFT JOIN student_submissions sub ON (sub.activity_id = a.id AND sub.user_id = :user_id)
    WHERE a.week_number = :week AND a.grade = :grade AND a.day_of_week = :day AND a.is_active = 1
    ORDER BY a.order_num ASC
");
$stmtAct->execute([
    'week' => $selectedWeek,
    'grade' => $grade,
    'day' => $selectedDay,
    'user_id' => $user['id']
]);
$activities = $stmtAct->fetchAll();

// Total de actividades completadas por el alumno
$stmtStats = $pdo->prepare("SELECT COUNT(*) FROM student_submissions WHERE user_id = :uid AND status IN ('completed', 'reviewed')");
$stmtStats->execute(['uid' => $user['id']]);
$totalCompleted = $stmtStats->fetchColumn();

require_once __DIR__ . '/../includes/personajes.php';

$dayMascots = [
    'lunes' => ['slug' => 'guacamaya', 'intro' => '¡Comenzamos la semana con toda la energía! Lee cada misión con calma y abre tus alas hacia el conocimiento.'],
    'martes' => ['slug' => 'iguana', 'intro' => '¡Día de observación y deducción! Mantente alerta y analiza cada problema paso a paso.'],
    'miercoles' => ['slug' => 'tonina', 'intro' => '¡Mitad de semana exploradora! Sumérgete en los misterios de la biología, los ríos y los números.'],
    'jueves' => ['slug' => 'chiguire', 'intro' => '¡Día de valores y civismo! Recuerda que el respeto, la empatía y la honestidad nos hacen mejores ciudadanos.'],
    'viernes' => ['slug' => 'periquito', 'intro' => '¡Llegó el viernes de campeones! Conquista tus últimos desafíos y celebra tus nuevos conocimientos.']
];
$curMascot = $dayMascots[$selectedDay] ?? $dayMascots['lunes'];
$pageTitle = 'Panel de ' . htmlspecialchars($user['name']);
require_once __DIR__ . '/../includes/header.php';
?>

<!-- Fondo Temático Suave del Hábitat Venezolano con Parallax Scroll-Driven -->
<?= renderHabitatParallaxBackground($curMascot['slug']) ?>

<!-- Banner de Bienvenida Gamificado -->
<div class="welcome-banner">
  <div style="display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 1rem;">
    <div>
      <span style="background: rgba(255,255,255,0.2); padding: 0.25rem 0.75rem; border-radius: var(--radius-full); font-size: 0.82rem; font-weight: 700; text-transform: uppercase;">
        <?= $grade ?>to Grado de Primaria
      </span>
      <h1 style="margin-top: 0.5rem;">¡Hola, <?= htmlspecialchars($user['name']) ?>! 🌟</h1>
      <p>
        Bienvenido a tu estación de aprendizaje. ¡Cada misión que completas te hace más sabio y más fuerte!
      </p>
    </div>

    <div style="background: rgba(255,255,255,0.15); backdrop-filter: blur(8px); padding: 1rem 1.5rem; border-radius: var(--radius-md); text-align: center; border: 1px solid rgba(255,255,255,0.25);">
      <div style="font-size: 0.8rem; text-transform: uppercase; letter-spacing: 0.05em; font-weight: 700;">Misiones Logradas</div>
      <div style="font-size: 2rem; font-weight: 800;"><?= $totalCompleted ?> 🎯</div>
    </div>
  </div>
</div>

<!-- Cuadro de Diálogo Interactivo con la Mascota del Día (Estilo Education.com) -->
<?= renderMascotDialogue($curMascot['slug'], "¡Hola " . htmlspecialchars($user['name']) . "! " . $curMascot['intro']) ?>

<?php if (isAdmin()): ?>
  <div style="background: #eef2ff; border: 1px solid #c7d2fe; border-radius: var(--radius-md); padding: 0.75rem 1rem; margin-bottom: 1.5rem; display: flex; align-items: center; justify-content: space-between; font-size: 0.9rem;">
    <div>
      <strong>Modo Vista Previa de Papá:</strong> Estás visualizando la pantalla de <strong><?= $grade ?>to Grado</strong>.
    </div>
    <div style="display: flex; gap: 0.5rem;">
      <a href="?day=<?= $selectedDay ?>&simulate_grade=5" class="btn btn-sm <?= $grade == 5 ? 'btn-primary' : 'btn-secondary' ?>">Ver 5to (Dimas)</a>
      <a href="?day=<?= $selectedDay ?>&simulate_grade=6" class="btn btn-sm <?= $grade == 6 ? 'btn-primary' : 'btn-secondary' ?>">Ver 6to (Alex)</a>
    </div>
  </div>
<?php endif; ?>

<!-- Barra Selectora de Días de la Semana -->
<!-- Barra Selectora de Días de la Semana -->
<div style="margin-bottom: 1.5rem;">
  <div style="display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 0.5rem; margin-bottom: 0.65rem;">
    <div>
      <h2 style="font-size: 1.25rem; margin: 0;">📅 Cronograma Semanal</h2>
      <span style="font-size: 0.85rem; color: var(--text-muted);">
        Semana <?= $selectedWeek ?> de 4 &bull; <strong>Octubre 2026</strong>
      </span>
    </div>

    <!-- Navegación entre semanas -->
    <div style="display: flex; align-items: center; gap: 0.5rem;">
      <?php if ($selectedWeek > 1): ?>
        <a href="?day=lunes&week=<?= $selectedWeek - 1 ?><?= isAdmin() ? '&simulate_grade=' . $grade : '' ?>" class="btn btn-sm btn-secondary" title="Semana anterior">
          <span>◀</span><span>Semana <?= $selectedWeek - 1 ?></span>
        </a>
      <?php endif; ?>
      <?php if ($selectedWeek < 4): ?>
        <a href="?day=lunes&week=<?= $selectedWeek + 1 ?><?= isAdmin() ? '&simulate_grade=' . $grade : '' ?>" class="btn btn-sm btn-secondary" title="Semana siguiente">
          <span>Semana <?= $selectedWeek + 1 ?></span><span>▶</span>
        </a>
      <?php endif; ?>
    </div>
  </div>

  <div class="days-bar">
    <?php foreach ($validDays as $d): 
      $isUnlocked = isDayUnlockedForStudents($d);
    ?>
      <?php if ($isUnlocked): ?>
        <a href="?day=<?= $d ?>&week=<?= $selectedWeek ?><?= isAdmin() ? '&simulate_grade=' . $grade : '' ?>" 
           class="day-tab <?= $selectedDay === $d ? 'active' : '' ?>">
          <span><?= getDayNameSpanish($d) ?></span>
          <span style="font-size: 0.75rem; opacity: 0.85;">Día <?= array_search($d, $validDays) + 1 ?></span>
        </a>
      <?php else: ?>
        <div class="day-tab locked" title="Se desbloqueará el <?= getDayNameSpanish($d) ?>">
          <span>🔒 <?= getDayNameSpanish($d) ?></span>
          <span style="font-size: 0.72rem; color: var(--text-muted);">Bloqueado</span>
        </div>
      <?php endif; ?>
    <?php endforeach; ?>
  </div>
</div>

<!-- Objetivo Pedagógico del Día -->
<div class="card" style="margin-bottom: 1.75rem; border-left: 5px solid var(--secondary); background: #f8fafc;">
  <div style="display: flex; align-items: flex-start; gap: 0.85rem;">
    <span style="font-size: 1.8rem;">🎯</span>
    <div>
      <h3 style="font-size: 1.05rem; color: var(--text-primary); margin-bottom: 0.25rem;">
        Objetivo de <?= getDayNameSpanish($selectedDay) ?>
      </h3>
      <p style="font-size: 0.95rem; color: var(--text-secondary); line-height: 1.5;">
        <?= htmlspecialchars($dailyObjective) ?>
      </p>
    </div>
  </div>
</div>

<!-- Listado de Misiones / Actividades del Día -->
<div>
  <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 0.5rem;">
    <h2 style="font-size: 1.25rem;">Misiones de <?= getDayNameSpanish($selectedDay) ?> (<?= count($activities) ?>)</h2>
  </div>

  <?php if (empty($activities)): ?>
    <div class="card" style="text-align: center; padding: 3rem 1.5rem;">
      <div style="font-size: 3rem; margin-bottom: 1rem;">🏖️</div>
      <h3>¡No hay misiones programadas para este día!</h3>
      <p style="margin-top: 0.5rem;">Tu papá aún no ha asignado actividades para este día o están en descanso.</p>
    </div>
  <?php else: ?>
    <div class="activities-grid">
      <?php foreach ($activities as $act): 
        $isCompleted = !empty($act['submission_id']);
        $hasFeedback = !empty($act['parent_feedback']) || $act['parent_stars'] > 0;
      ?>
        <div class="activity-card <?= $isCompleted ? 'completed' : '' ?>">
          <div>
            <div class="activity-card-header">
              <span class="badge-subject badge-<?= $act['subject_slug'] ?>">
                <?= $act['subject_icon'] ?> <?= htmlspecialchars($act['subject_name']) ?>
              </span>
              <span class="xp-tag">
                ⚡ +<?= $act['xp_reward'] ?> XP
              </span>
            </div>

            <h3 class="activity-card-title"><?= htmlspecialchars($act['title']) ?></h3>
            <p class="activity-card-summary"><?= htmlspecialchars($act['summary']) ?></p>
            
            <?php 
              $tutor = getPersonajeBySubject($act['subject_slug']); 
              if ($tutor):
            ?>
              <div style="display: inline-flex; align-items: center; gap: 0.35rem; font-size: 0.78rem; font-weight: 700; color: <?= htmlspecialchars($tutor['color_tema']) ?>; background: #f8fafc; border: 1px solid #e2e8f0; padding: 0.2rem 0.55rem; border-radius: var(--radius-full); margin-top: 0.5rem;">
                <img src="<?= $baseUrl ?>assets/images/personajes/<?= htmlspecialchars($tutor['archivo_render']) ?>" 
                     alt="<?= htmlspecialchars($tutor['nombre']) ?>" 
                     style="width: 20px; height: 20px; border-radius: 50%; object-fit: contain;">
                <span>Tutor: <?= htmlspecialchars($tutor['nombre']) ?></span>
              </div>
            <?php endif; ?>
          </div>

          <div>
            <?php if ($isCompleted): ?>
              <div style="background: #ffffff; padding: 0.75rem; border-radius: var(--radius-sm); border: 1px solid #a7f3d0; margin-bottom: 0.85rem;">
                <div style="display: flex; align-items: center; justify-content: space-between; font-size: 0.85rem; font-weight: 700; color: #065f46;">
                  <span>✅ ¡Misión Completada!</span>
                  <span><?= $act['score'] ?> / <?= $act['max_score'] ?> pts</span>
                </div>

                <?php if ($act['parent_stars'] > 0): ?>
                  <div style="margin-top: 0.4rem; display: flex; align-items: center; gap: 0.35rem;">
                    <span style="font-size: 0.8rem; font-weight: 600; color: var(--text-secondary);">Nota de Papá:</span>
                    <span class="star-rating" style="font-size: 1rem;">
                      <?= str_repeat('⭐', $act['parent_stars']) ?>
                    </span>
                  </div>
                <?php endif; ?>

                <?php if (!empty($act['parent_feedback'])): ?>
                  <div style="margin-top: 0.4rem; font-size: 0.82rem; color: #047857; font-style: italic;">
                    💬 "<?= htmlspecialchars($act['parent_feedback']) ?>"
                  </div>
                <?php endif; ?>
              </div>
            <?php endif; ?>

            <div class="activity-card-footer">
              <span style="font-size: 0.82rem; font-weight: 600; color: var(--text-muted);">
                <?= $isCompleted ? 'Estado: Listo' : 'Pendiente' ?>
              </span>
              <a href="activity.php?id=<?= $act['id'] ?>" class="btn <?= $isCompleted ? 'btn-secondary' : 'btn-primary' ?> btn-sm">
                <?= $isCompleted ? '🔍 Repasar Misión' : '🚀 Iniciar Misión' ?>
              </a>
            </div>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
