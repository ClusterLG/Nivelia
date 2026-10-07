<?php
require_once __DIR__ . '/../config/db.php';
requireAdmin();

$pdo = getDB();

// Estadísticas de los estudiantes
$stmtKids = $pdo->query("SELECT id, name, grade, avatar, total_xp FROM users WHERE role = 'student' ORDER BY grade ASC");
$students = $stmtKids->fetchAll();

// Total de entregas realizadas
$totalSubmissions = $pdo->query("SELECT COUNT(*) FROM student_submissions")->fetchColumn();

// Entregas pendientes de calificación manual (estrellas = 0)
$pendingGrading = $pdo->query("SELECT COUNT(*) FROM student_submissions WHERE parent_stars = 0")->fetchColumn();

// Total de actividades activas
$totalActivities = $pdo->query("SELECT COUNT(*) FROM activities WHERE is_active = 1")->fetchColumn();

// Últimas 8 entregas de los niños
$stmtRecent = $pdo->query("
    SELECT sub.*, u.name as student_name, u.grade as student_grade, u.avatar as student_avatar,
           a.title as activity_title, a.day_of_week, s.name as subject_name, s.slug as subject_slug, s.icon as subject_icon
    FROM student_submissions sub
    JOIN users u ON sub.user_id = u.id
    JOIN activities a ON sub.activity_id = a.id
    JOIN subjects s ON a.subject_id = s.id
    ORDER BY sub.submitted_at DESC
    LIMIT 8
");
$recentSubmissions = $stmtRecent->fetchAll();

// Procesar cambio de semana activa para los alumnos
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_active_weeks') {
    $w5 = max(1, (int)($_POST['active_week_5to'] ?? 1));
    $w6 = max(1, (int)($_POST['active_week_6to'] ?? 1));
    setActiveWeekForGrade(5, $w5);
    setActiveWeekForGrade(6, $w6);
    $_SESSION['flash_success'] = "¡Semanas escolares activas actualizadas con éxito! (5to Grado: Semana $w5, 6to Grado: Semana $w6).";
    header('Location: index.php');
    exit;
}

$activeWeek5 = getActiveWeekForGrade(5);
$activeWeek6 = getActiveWeekForGrade(6);
$unreadAlerts = $pdo->query("SELECT * FROM admin_notifications WHERE type = 'alert_reinforcement' AND is_read = 0 ORDER BY created_at DESC LIMIT 5")->fetchAll();
$unreadNotifsCount = getUnreadAdminNotificationsCount();

$pageTitle = 'Panel de Administración - Papá';
require_once __DIR__ . '/../includes/header.php';
?>

<div style="margin-bottom: 2rem;">
  <div style="display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 1rem; margin-bottom: 1.5rem;">
    <div>
      <h1 style="font-size: 1.85rem;">👨‍🏫 Panel de Control de Papá</h1>
      <p style="font-size: 1rem; color: var(--text-secondary);">
        Supervisa el progreso académico, planifica las misiones semanales y califica el desempeño de tus hijos.
      </p>
    </div>

    <div style="display: flex; gap: 0.75rem; flex-wrap: wrap;">
      <a href="notificaciones.php" class="btn btn-secondary" title="Alertas y Notificaciones" style="position: relative;">
        <span>🔔</span>
        <span>Notificaciones</span>
        <?php if ($unreadNotifsCount > 0): ?>
          <span style="background: #ef4444; color: #fff; font-size: 0.72rem; font-weight: 800; border-radius: 999px; padding: 0.15rem 0.45rem; margin-left: 0.35rem;">
            <?= $unreadNotifsCount ?>
          </span>
        <?php endif; ?>
      </a>
      <a href="session_logs.php" class="btn btn-secondary" title="Auditoría de Tiempo y Conexiones">
        <span>⏱️</span>
        <span>Logs de Sesión</span>
      </a>
      <a href="materiales.php" class="btn btn-primary" title="Banco Curricular de Materiales (3 Lapsos)">
        <span>📚</span>
        <span>Banco de Materiales (3 Lapsos)</span>
      </a>
      <a href="calendario.php" class="btn btn-secondary" title="Calendario de Contenidos por Semanas">
        <span>🗓️</span>
        <span>Calendario Escolar</span>
      </a>
      <a href="planner.php" class="btn btn-secondary">
        <span>📅</span>
        <span>Plan Semanal</span>
      </a>
      <a href="grade.php" class="btn btn-secondary">
        <span>⭐</span>
        <span>Evaluar Entregas (<?= $pendingGrading ?>)</span>
      </a>
      <a href="metrics.php" class="btn btn-secondary" title="Ver métricas y archivos en respuestas/">
        <span>📈</span>
        <span>Métricas e Historial</span>
      </a>
      <a href="passwords.php" class="btn btn-secondary" title="Cambiar contraseñas">
        <span>🔑</span>
        <span>Contraseñas</span>
      </a>
    </div>
  </div>

  <!-- Alerta Pedagógica Destacada si hay alumnos con 3 errores consecutivos en el mismo objetivo -->
  <?php if (!empty($unreadAlerts)): ?>
    <div style="background: #fef2f2; border: 2px solid #ef4444; border-radius: 16px; padding: 1.25rem 1.5rem; margin-bottom: 2rem; display: flex; align-items: flex-start; justify-content: space-between; gap: 1rem; box-shadow: 0 4px 16px rgba(239,68,68,0.12);">
      <div style="display: flex; align-items: flex-start; gap: 1rem;">
        <span style="font-size: 2.2rem; line-height: 1;">⚠️</span>
        <div>
          <h3 style="margin: 0 0 0.35rem 0; font-size: 1.15rem; color: #b91c1c;">
            ¡Alerta Pedagógica de Refuerzo Requerido!
          </h3>
          <p style="margin: 0 0 0.5rem 0; font-size: 0.92rem; color: #7f1d1d; line-height: 1.45;">
            Se ha detectado que uno de tus hijos respondió incorrectamente 3 veces en preguntas del mismo objetivo escolar.
          </p>
          <div style="display: flex; flex-direction: column; gap: 0.35rem;">
            <?php foreach ($unreadAlerts as $ua): ?>
              <div style="font-size: 0.85rem; color: #991b1b; background: rgba(255,255,255,0.7); padding: 0.35rem 0.65rem; border-radius: 6px;">
                <strong><?= htmlspecialchars($ua['title']) ?>:</strong> <?= htmlspecialchars($ua['message']) ?>
              </div>
            <?php endforeach; ?>
          </div>
        </div>
      </div>
      <a href="notificaciones.php?type=alert_reinforcement" class="btn btn-sm" style="background: #dc2626; color: #fff; font-weight: 800; white-space: nowrap;">
        Ver Alertas &rarr;
      </a>
    </div>
  <?php endif; ?>

  <!-- Tarjeta de Control: Semana Activa Escolar para Alumnos -->
  <div style="background: #fff; border: 1.5px solid #cbd5e1; border-radius: 16px; padding: 1.25rem 1.5rem; margin-bottom: 2rem; box-shadow: var(--shadow-sm); display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 1.25rem;">
    <div>
      <div style="display: flex; align-items: center; gap: 0.45rem; margin-bottom: 0.25rem;">
        <span style="font-size: 1.2rem;">🔒</span>
        <h3 style="font-size: 1.1rem; margin: 0; color: #1e293b;">
          Control de Semana Activa Escolar (Acceso de Alumnos)
        </h3>
      </div>
      <p style="font-size: 0.88rem; color: #64748b; margin: 0; max-width: 650px;">
        Los alumnos solo pueden ver las misiones de su semana activa y únicamente los días transcurridos y el actual (los días se desbloquean automáticamente a medianoche).
      </p>
    </div>

    <form method="POST" action="index.php" style="display: flex; align-items: center; gap: 1rem; flex-wrap: wrap; margin: 0;">
      <input type="hidden" name="action" value="update_active_weeks">
      
      <div style="display: flex; align-items: center; gap: 0.45rem;">
        <label style="font-size: 0.85rem; font-weight: 700; color: #334155;">⚡ 5to (Dimas):</label>
        <select name="active_week_5to" class="form-control" style="padding: 0.35rem 0.65rem; font-size: 0.88rem; width: auto; font-weight: 700;">
          <?php for ($i=1; $i<=30; $i++): ?>
            <option value="<?= $i ?>" <?= $activeWeek5 == $i ? 'selected' : '' ?>>Semana <?= $i ?> (Lapso <?= ceil($i/10) ?>)</option>
          <?php endfor; ?>
        </select>
      </div>

      <div style="display: flex; align-items: center; gap: 0.45rem;">
        <label style="font-size: 0.85rem; font-weight: 700; color: #334155;">🚀 6to (Alex):</label>
        <select name="active_week_6to" class="form-control" style="padding: 0.35rem 0.65rem; font-size: 0.88rem; width: auto; font-weight: 700;">
          <?php for ($i=1; $i<=30; $i++): ?>
            <option value="<?= $i ?>" <?= $activeWeek6 == $i ? 'selected' : '' ?>>Semana <?= $i ?> (Lapso <?= ceil($i/10) ?>)</option>
          <?php endfor; ?>
        </select>
      </div>

      <button type="submit" class="btn btn-primary btn-sm" style="font-weight: 800;">
        <span>💾</span><span>Guardar Semana Activa</span>
      </button>
    </form>
  </div>

  <!-- Métricas Rápidas -->
  <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 1.25rem; margin-bottom: 2rem;">
    
    <div class="card" style="border-left: 5px solid var(--primary);">
      <div style="font-size: 0.85rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">Misiones Activas</div>
      <div style="font-size: 2.2rem; font-weight: 800; color: var(--text-primary); margin: 0.25rem 0;"><?= $totalActivities ?></div>
      <div style="font-size: 0.85rem; color: var(--text-secondary);">Semana 1 precargada</div>
    </div>

    <div class="card" style="border-left: 5px solid var(--success);">
      <div style="font-size: 0.85rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">Entregas Realizadas</div>
      <div style="font-size: 2.2rem; font-weight: 800; color: #059669; margin: 0.25rem 0;"><?= $totalSubmissions ?></div>
      <div style="font-size: 0.85rem; color: var(--text-secondary);">Por Alexander y Dimas</div>
    </div>

    <div class="card" style="border-left: 5px solid <?= $pendingGrading > 0 ? '#ef4444' : '#10b981' ?>;">
      <div style="font-size: 0.85rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">Por Calificar</div>
      <div style="font-size: 2.2rem; font-weight: 800; color: <?= $pendingGrading > 0 ? '#dc2626' : '#059669' ?>; margin: 0.25rem 0;"><?= $pendingGrading ?></div>
      <div style="font-size: 0.85rem; color: var(--text-secondary);">Respuestas pendientes de estrellas</div>
    </div>

  </div>

  <!-- Tarjetas de los Niños -->
  <h2 style="font-size: 1.3rem; margin-bottom: 1rem;">👦 Estado de Alexander y Dimas</h2>
  <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)); gap: 1.5rem; margin-bottom: 2.5rem;">
    <?php foreach ($students as $stu): 
      $stmtSubCount = $pdo->prepare("SELECT COUNT(*) FROM student_submissions WHERE user_id = :uid");
      $stmtSubCount->execute(['uid' => $stu['id']]);
      $completedCount = $stmtSubCount->fetchColumn();
    ?>
      <div class="card" style="border: 2px solid var(--border);">
        <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 1rem;">
          <div style="display: flex; align-items: center; gap: 0.75rem;">
            <div style="font-size: 2.5rem; background: #e0e7ff; width: 55px; height: 55px; display: flex; align-items: center; justify-content: center; border-radius: 50%;">
              <?= $stu['avatar'] ?>
            </div>
            <div>
              <h3 style="font-size: 1.2rem;"><?= htmlspecialchars($stu['name']) ?></h3>
              <span class="badge-subject badge-lectura"><?= $stu['grade'] ?>to Grado de Primaria</span>
            </div>
          </div>
          <div class="xp-badge">
            ⚡ <?= $stu['total_xp'] ?> XP
          </div>
        </div>

        <div style="background: #f8fafc; padding: 1rem; border-radius: var(--radius-md); margin-bottom: 1rem;">
          <div style="display: flex; justify-content: space-between; font-size: 0.9rem; margin-bottom: 0.35rem;">
            <span>Misiones completadas:</span>
            <strong><?= $completedCount ?> actividades</strong>
          </div>
        </div>

        <div style="display: flex; gap: 0.5rem;">
          <a href="../student/dashboard.php?simulate_grade=<?= $stu['grade'] ?>" class="btn btn-secondary btn-sm" style="flex: 1;" target="_blank">
            👁️ Ver como <?= htmlspecialchars($stu['name']) ?>
          </a>
          <a href="grade.php?student_id=<?= $stu['id'] ?>" class="btn btn-primary btn-sm" style="flex: 1;">
            ⭐ Ver Calificaciones
          </a>
        </div>
      </div>
    <?php endforeach; ?>
  </div>

  <!-- Últimas Entregas -->
  <div>
    <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 1rem;">
      <h2 style="font-size: 1.3rem;">📋 Últimas Actividades Respondidas</h2>
      <a href="grade.php" style="font-size: 0.9rem; font-weight: 600;">Ver todas las entregas ➔</a>
    </div>

    <?php if (empty($recentSubmissions)): ?>
      <div class="card" style="text-align: center; padding: 2.5rem 1rem;">
        <p style="color: var(--text-muted); font-size: 1rem;">
          Aún no hay actividades respondidas por los niños. ¡Pronto comenzarán sus misiones!
        </p>
      </div>
    <?php else: ?>
      <div style="display: flex; flex-direction: column; gap: 0.75rem;">
        <?php foreach ($recentSubmissions as $sub): ?>
          <div class="card" style="display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 1rem; padding: 1rem 1.25rem;">
            <div style="display: flex; align-items: center; gap: 1rem;">
              <span style="font-size: 1.8rem;"><?= $sub['student_avatar'] ?></span>
              <div>
                <div style="font-weight: 700; color: var(--text-primary);">
                  <?= htmlspecialchars($sub['student_name']) ?> (<?= $sub['student_grade'] ?>to)
                </div>
                <div style="font-size: 0.88rem; color: var(--text-secondary);">
                  <?= $sub['subject_icon'] ?> <?= htmlspecialchars($sub['activity_title']) ?> • <span style="text-transform: capitalize;"><?= getDayNameSpanish($sub['day_of_week']) ?></span>
                </div>
              </div>
            </div>

            <div style="display: flex; align-items: center; gap: 1rem;">
              <div style="text-align: right;">
                <div style="font-weight: 700; font-size: 0.95rem; color: #059669;">
                  <?= $sub['score'] ?> / <?= $sub['max_score'] ?> pts
                </div>
                <div style="font-size: 0.8rem; color: var(--text-muted);">
                  <?= $sub['parent_stars'] > 0 ? str_repeat('⭐', $sub['parent_stars']) : 'Pendiente de ⭐' ?>
                </div>
              </div>

              <a href="grade.php?submission_id=<?= $sub['id'] ?>" class="btn btn-sm <?= $sub['parent_stars'] > 0 ? 'btn-secondary' : 'btn-primary' ?>">
                <?= $sub['parent_stars'] > 0 ? 'Editar Nota' : '⭐ Calificar' ?>
              </a>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </div>

</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
