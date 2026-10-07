<?php
require_once __DIR__ . '/../config/db.php';
requireAdmin();

$pdo = getDB();

// Procesar acciones POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'mark_all_read') {
        $pdo->exec("UPDATE admin_notifications SET is_read = 1 WHERE is_read = 0");
        $_SESSION['flash_success'] = 'Todas las notificaciones han sido marcadas como leídas.';
        header('Location: notificaciones.php');
        exit;
    }

    if ($action === 'mark_read') {
        $notifId = (int)($_POST['id'] ?? 0);
        if ($notifId) {
            $pdo->prepare("UPDATE admin_notifications SET is_read = 1 WHERE id = :id")->execute(['id' => $notifId]);
        }
        header('Location: notificaciones.php');
        exit;
    }

    if ($action === 'delete_all') {
        $pdo->exec("DELETE FROM admin_notifications");
        $_SESSION['flash_success'] = 'Historial de notificaciones vaciado.';
        header('Location: notificaciones.php');
        exit;
    }
}

// Filtro de tipo
$filterType = $_GET['type'] ?? 'all';
$validTypes = ['all', 'submission', 'alert_reinforcement', 'session', 'request'];
if (!in_array($filterType, $validTypes)) $filterType = 'all';

// Consulta de notificaciones con datos del alumno
$sql = "
    SELECT n.*, u.name as student_name, u.grade as student_grade, u.avatar as student_avatar
    FROM admin_notifications n
    LEFT JOIN users u ON n.user_id = u.id
";
if ($filterType !== 'all') {
    $sql .= " WHERE n.type = " . $pdo->quote($filterType);
}
$sql .= " ORDER BY n.created_at DESC LIMIT 100";

$notifications = $pdo->query($sql)->fetchAll();

// Conteo por categorías
$countAlerts = $pdo->query("SELECT COUNT(*) FROM admin_notifications WHERE type = 'alert_reinforcement' AND is_read = 0")->fetchColumn();
$countSubs = $pdo->query("SELECT COUNT(*) FROM admin_notifications WHERE type = 'submission' AND is_read = 0")->fetchColumn();
$countSessions = $pdo->query("SELECT COUNT(*) FROM admin_notifications WHERE type = 'session' AND is_read = 0")->fetchColumn();
$countRequests = $pdo->query("SELECT COUNT(*) FROM admin_notifications WHERE type = 'request' AND is_read = 0")->fetchColumn();
$totalUnread = $countAlerts + $countSubs + $countSessions + $countRequests;

$pageTitle = 'Centro de Notificaciones y Alertas';
require_once __DIR__ . '/../includes/header.php';
?>

<div style="margin-bottom: 2rem;">
  
  <div style="display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 1rem; margin-bottom: 1.5rem;">
    <div>
      <div style="display: inline-flex; align-items: center; gap: 0.4rem; background: #fee2e2; color: #dc2626; padding: 0.3rem 0.75rem; border-radius: 999px; font-size: 0.8rem; font-weight: 800; text-transform: uppercase; margin-bottom: 0.4rem;">
        <span>🔔</span>
        <span>Centro de Notificaciones y Alertas Pedagógicas</span>
      </div>
      <h1 style="font-size: 1.85rem; margin: 0;">Actividad en Tiempo Real de los Alumnos</h1>
      <p style="font-size: 0.95rem; color: var(--text-secondary); margin-top: 0.25rem;">
        Supervisa cada entrega realizada, ingresos al sistema y alertas tempranas de refuerzo pedagógico.
      </p>
    </div>

    <div style="display: flex; gap: 0.65rem; flex-wrap: wrap;">
      <?php if ($totalUnread > 0): ?>
        <form method="POST" action="notificaciones.php">
          <input type="hidden" name="action" value="mark_all_read">
          <button type="submit" class="btn btn-primary btn-sm">
            <span>✓</span><span>Marcar Todas como Leídas</span>
          </button>
        </form>
      <?php endif; ?>
      <form method="POST" action="notificaciones.php" onsubmit="return confirm('¿Deseas vaciar todo el historial de notificaciones?');">
        <input type="hidden" name="action" value="delete_all">
        <button type="submit" class="btn btn-secondary btn-sm" style="color: #dc2626;">
          <span>🗑️</span><span>Vaciar Historial</span>
        </button>
      </form>
    </div>
  </div>

  <!-- Alerta Destacada si hay alumnos con dificultad recurrente -->
  <?php if ($countAlerts > 0): ?>
    <div style="background: #fef2f2; border: 2px solid #ef4444; border-radius: 16px; padding: 1.25rem 1.5rem; margin-bottom: 1.75rem; display: flex; align-items: flex-start; gap: 1rem; box-shadow: 0 4px 14px rgba(239, 68, 68, 0.15);">
      <span style="font-size: 2.2rem; line-height: 1;">⚠️</span>
      <div>
        <h3 style="font-size: 1.15rem; color: #b91c1c; margin: 0 0 0.35rem 0;">
          ¡Atención Papá! Tienes <?= $countAlerts ?> alerta(s) pedagógica(s) pendiente(s) de atención
        </h3>
        <p style="font-size: 0.92rem; color: #7f1d1d; margin: 0; line-height: 1.45;">
          Uno o varios alumnos han respondido incorrectamente 3 veces en preguntas del mismo objetivo pedagógico. Te recomendamos revisar el tema en conjunto y ofrecer orientación personalizada.
        </p>
      </div>
    </div>
  <?php endif; ?>

  <!-- Pestañas de Filtro -->
  <div style="display: flex; gap: 0.65rem; margin-bottom: 1.5rem; flex-wrap: wrap;">
    <a href="?type=all" class="btn btn-sm <?= $filterType === 'all' ? 'btn-primary' : 'btn-secondary' ?>">
      <span>Todos</span> (<?= count($notifications) ?>)
    </a>
    <a href="?type=request" class="btn btn-sm <?= $filterType === 'request' ? 'btn-primary' : 'btn-secondary' ?>" style="<?= $countRequests > 0 ? 'border: 2px solid #f59e0b;' : '' ?>">
      <span>📋 Solicitudes de Acceso</span> <?= $countRequests > 0 ? "<span style='background:#f59e0b;color:#fff;padding:0.15rem 0.45rem;border-radius:999px;font-size:0.75rem;font-weight:800;'>$countRequests</span>" : '' ?>
    </a>
    <a href="?type=alert_reinforcement" class="btn btn-sm <?= $filterType === 'alert_reinforcement' ? 'btn-primary' : 'btn-secondary' ?>" style="<?= $countAlerts > 0 ? 'border: 2px solid #ef4444;' : '' ?>">
      <span>⚠️ Alertas de Refuerzo</span> <?= $countAlerts > 0 ? "<span style='background:#ef4444;color:#fff;padding:0.15rem 0.45rem;border-radius:999px;font-size:0.75rem;font-weight:800;'>$countAlerts</span>" : '' ?>
    </a>
    <a href="?type=submission" class="btn btn-sm <?= $filterType === 'submission' ? 'btn-primary' : 'btn-secondary' ?>">
      <span>📝 Entregas de Tareas</span> <?= $countSubs > 0 ? "<span style='background:#4f46e5;color:#fff;padding:0.15rem 0.45rem;border-radius:999px;font-size:0.75rem;font-weight:800;'>$countSubs</span>" : '' ?>
    </a>
    <a href="?type=session" class="btn btn-sm <?= $filterType === 'session' ? 'btn-primary' : 'btn-secondary' ?>">
      <span>🟢 Ingresos y Sesiones</span>
    </a>
  </div>

  <!-- Listado de Notificaciones -->
  <?php if (empty($notifications)): ?>
    <div class="card" style="text-align: center; padding: 3rem 1.5rem;">
      <div style="font-size: 3rem; margin-bottom: 0.5rem;">📭</div>
      <h3 style="margin: 0; color: #475569;">No hay notificaciones en este filtro</h3>
      <p style="color: #94a3b8; font-size: 0.9rem; margin-top: 0.35rem;">
        Aquí aparecerán las actualizaciones automáticas conforme los alumnos completen sus misiones o soliciten acceso.
      </p>
    </div>
  <?php else: ?>
    <div style="display: flex; flex-direction: column; gap: 0.85rem;">
      <?php foreach ($notifications as $n): 
        $isReq = ($n['type'] === 'request');
        $isAlert = ($n['type'] === 'alert_reinforcement');
        $isSub = ($n['type'] === 'submission');
        $isSession = ($n['type'] === 'session');
        $bgColor = $n['is_read'] ? '#fff' : ($isAlert ? '#fff5f5' : ($isReq ? '#fffbeb' : '#f8faff'));
        $borderColor = $isAlert ? '#fca5a5' : ($isReq ? '#fcd34d' : ($n['is_read'] ? '#e2e8f0' : '#c7d2fe'));
      ?>
        <div style="background: <?= $bgColor ?>; border: 1.5px solid <?= $borderColor ?>; border-radius: 14px; padding: 1.15rem 1.35rem; display: flex; align-items: flex-start; justify-content: space-between; gap: 1rem; box-shadow: var(--shadow-sm); transition: transform 0.2s ease;">
          <div style="display: flex; align-items: flex-start; gap: 0.85rem; flex: 1;">
            <div style="font-size: 1.8rem; line-height: 1;">
              <?= $isReq ? '📋' : ($isAlert ? '⚠️' : ($isSub ? '📝' : '🟢')) ?>
            </div>
            <div>
              <div style="display: flex; align-items: center; gap: 0.5rem; flex-wrap: wrap; margin-bottom: 0.25rem;">
                <strong style="font-size: 1.05rem; color: <?= $isAlert ? '#b91c1c' : ($isReq ? '#b45309' : '#1e293b') ?>;">
                  <?= htmlspecialchars($n['title']) ?>
                </strong>
                <?php if (!empty($n['student_name'])): ?>
                  <span style="font-size: 0.75rem; background: #e2e8f0; color: #475569; padding: 0.2rem 0.5rem; border-radius: 999px; font-weight: 700;">
                    <?= htmlspecialchars($n['student_name']) ?> <?= $n['student_grade'] ? '(' . $n['student_grade'] . 'to)' : '' ?>
                  </span>
                <?php endif; ?>
                <?php if (!$n['is_read']): ?>
                  <span style="font-size: 0.7rem; background: #ef4444; color: #fff; padding: 0.15rem 0.45rem; border-radius: 999px; font-weight: 800; text-transform: uppercase;">
                    Nueva
                  </span>
                <?php endif; ?>
              </div>

              <p style="font-size: 0.92rem; color: #475569; margin: 0.2rem 0 0.5rem 0; line-height: 1.45;">
                <?= htmlspecialchars($n['message']) ?>
              </p>

              <div style="display: flex; align-items: center; gap: 1rem; font-size: 0.8rem; color: #94a3b8;">
                <span>🕒 <?= date('d/m/Y - h:i A', strtotime($n['created_at'])) ?></span>
                <?php if (!empty($n['link'])): ?>
                  <a href="<?= htmlspecialchars($n['link']) ?>" style="color: #4f46e5; font-weight: 700; text-decoration: underline;">
                    Ver detalle &rarr;
                  </a>
                <?php endif; ?>
              </div>
            </div>
          </div>

          <?php if (!$n['is_read']): ?>
            <form method="POST" action="notificaciones.php">
              <input type="hidden" name="action" value="mark_read">
              <input type="hidden" name="id" value="<?= $n['id'] ?>">
              <button type="submit" class="btn btn-secondary btn-sm" title="Marcar como leída" style="padding: 0.35rem 0.65rem; font-size: 0.8rem;">
                <span>✓ Leída</span>
              </button>
            </form>
          <?php endif; ?>
        </div>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>

</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
