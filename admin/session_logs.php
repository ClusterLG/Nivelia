<?php
require_once __DIR__ . '/../config/db.php';
requireAdmin();

$pdo = getDB();

// Filtros GET
$filterUser = isset($_GET['user_id']) ? (int)$_GET['user_id'] : 0;
$filterDate = $_GET['date'] ?? '';

// Consulta de logs con datos del usuario
$sql = "
    SELECT l.*, u.name as user_name, u.role as user_role, u.grade as user_grade, u.avatar as user_avatar
    FROM user_session_logs l
    JOIN users u ON l.user_id = u.id
    WHERE 1=1
";
if ($filterUser > 0) {
    $sql .= " AND l.user_id = $filterUser";
}
if (!empty($filterDate)) {
    $sql .= " AND DATE(l.login_at) = " . $pdo->quote($filterDate);
}
$sql .= " ORDER BY l.login_at DESC LIMIT 150";

$logs = $pdo->query($sql)->fetchAll();

// Lista de usuarios para el filtro
$usersList = $pdo->query("SELECT id, name, role, grade FROM users ORDER BY role DESC, grade ASC")->fetchAll();

// Métricas de tiempo acumulado por estudiante
$timeStats = $pdo->query("
    SELECT u.id, u.name, u.grade, SUM(l.duration_seconds) as total_seconds, COUNT(l.id) as total_sessions,
           MAX(l.last_activity_at) as last_seen
    FROM users u
    LEFT JOIN user_session_logs l ON u.id = l.user_id
    WHERE u.role = 'student'
    GROUP BY u.id
")->fetchAll();

// Formateador de segundos a texto amigable
function formatDuration($seconds) {
    if (!$seconds || $seconds < 0) return '0 seg';
    $hours = floor($seconds / 3600);
    $minutes = floor(($seconds % 3600) / 60);
    $secs = $seconds % 60;
    
    $parts = [];
    if ($hours > 0) $parts[] = $hours . 'h';
    if ($minutes > 0) $parts[] = $minutes . 'm';
    if ($secs > 0 || empty($parts)) $parts[] = $secs . 's';
    return implode(' ', $parts);
}

$pageTitle = 'Registro de Sesiones y Tiempo en Línea';
require_once __DIR__ . '/../includes/header.php';
?>

<div style="margin-bottom: 2rem;">
  
  <div style="display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 1rem; margin-bottom: 1.5rem;">
    <div>
      <div style="display: inline-flex; align-items: center; gap: 0.4rem; background: #e0f2fe; color: #0284c7; padding: 0.3rem 0.75rem; border-radius: 999px; font-size: 0.8rem; font-weight: 800; text-transform: uppercase; margin-bottom: 0.4rem;">
        <span>⏱️</span>
        <span>Auditoría de Conexión y Tiempo de Estudio</span>
      </div>
      <h1 style="font-size: 1.85rem; margin: 0;">Registro de Sesiones de Usuarios</h1>
      <p style="font-size: 0.95rem; color: var(--text-secondary); margin-top: 0.25rem;">
        Supervisa los momentos exactos de ingreso, el tiempo activo en la plataforma y la desconexión de tus hijos.
      </p>
    </div>

    <div style="display: flex; gap: 0.65rem;">
      <a href="metrics.php" class="btn btn-secondary btn-sm">
        <span>📈</span><span>Ver Métricas de Desempeño</span>
      </a>
    </div>
  </div>

  <!-- Tarjetas de Resumen de Tiempo por Alumno -->
  <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(260px, 1fr)); gap: 1.25rem; margin-bottom: 2rem;">
    <?php foreach ($timeStats as $ts): 
      $isOnline = false;
      if (!empty($ts['last_seen'])) {
          $diff = time() - strtotime($ts['last_seen']);
          if ($diff < 180) { // Actividad en los últimos 3 minutos
              $isOnline = true;
          }
      }
    ?>
      <div class="card" style="border-left: 5px solid <?= $ts['grade'] == 6 ? '#4f46e5' : '#10b981' ?>;">
        <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 0.5rem;">
          <strong style="font-size: 1.05rem; color: #1e293b;">
            <?= htmlspecialchars($ts['name']) ?> (<?= $ts['grade'] ?>to Grado)
          </strong>
          <span style="font-size: 0.75rem; font-weight: 800; padding: 0.2rem 0.55rem; border-radius: 999px; background: <?= $isOnline ? '#dcfce7' : '#f1f5f9' ?>; color: <?= $isOnline ? '#15803d' : '#64748b' ?>;">
            <?= $isOnline ? '🟢 En Línea' : '⚪ Desconectado' ?>
          </span>
        </div>

        <div style="font-size: 2rem; font-weight: 800; color: #1e293b; margin: 0.25rem 0;">
          <?= formatDuration($ts['total_seconds']) ?>
        </div>
        <div style="font-size: 0.85rem; color: var(--text-secondary);">
          Tiempo total acumulado en <strong><?= (int)$ts['total_sessions'] ?> sesiones</strong>
        </div>
      </div>
    <?php endforeach; ?>
  </div>

  <!-- Filtros de Búsqueda -->
  <div style="background: #fff; border: 1.5px solid #e2e8f0; border-radius: 14px; padding: 1rem 1.25rem; margin-bottom: 1.5rem; display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 1rem; box-shadow: var(--shadow-sm);">
    <form method="GET" action="session_logs.php" style="display: flex; align-items: center; gap: 0.75rem; flex-wrap: wrap; margin: 0;">
      <div>
        <label style="font-size: 0.8rem; font-weight: 700; color: #64748b; display: block; margin-bottom: 0.2rem;">Usuario:</label>
        <select name="user_id" class="form-control" style="padding: 0.4rem 0.75rem; font-size: 0.88rem; width: auto;">
          <option value="0">Todos los usuarios</option>
          <?php foreach ($usersList as $u): ?>
            <option value="<?= $u['id'] ?>" <?= $filterUser == $u['id'] ? 'selected' : '' ?>>
              <?= htmlspecialchars($u['name']) ?> (<?= $u['role'] === 'admin' ? 'Papá' : $u['grade'] . 'to Grado' ?>)
            </option>
          <?php endforeach; ?>
        </select>
      </div>

      <div>
        <label style="font-size: 0.8rem; font-weight: 700; color: #64748b; display: block; margin-bottom: 0.2rem;">Fecha:</label>
        <input type="date" name="date" class="form-control" value="<?= htmlspecialchars($filterDate) ?>" style="padding: 0.35rem 0.75rem; font-size: 0.88rem; width: auto;">
      </div>

      <div style="margin-top: 1.2rem;">
        <button type="submit" class="btn btn-primary btn-sm">
          <span>🔍</span><span>Filtrar</span>
        </button>
        <?php if ($filterUser || !empty($filterDate)): ?>
          <a href="session_logs.php" class="btn btn-secondary btn-sm">Limpiar</a>
        <?php endif; ?>
      </div>
    </form>

    <div style="font-size: 0.85rem; color: #64748b;">
      Mostrando últimos <?= count($logs) ?> registros
    </div>
  </div>

  <!-- Tabla Detallada de Sesiones -->
  <div class="card" style="padding: 0; overflow: hidden;">
    <div style="overflow-x: auto;">
      <table class="table" style="margin: 0; width: 100%;">
        <thead>
          <tr style="background: #f8fafc; text-align: left; font-size: 0.82rem; color: #64748b; text-transform: uppercase;">
            <th style="padding: 0.85rem 1.15rem;">Usuario</th>
            <th style="padding: 0.85rem 1.15rem;">Ingreso (Login)</th>
            <th style="padding: 0.85rem 1.15rem;">Última Actividad</th>
            <th style="padding: 0.85rem 1.15rem;">Desconexión (Logout)</th>
            <th style="padding: 0.85rem 1.15rem;">Tiempo en Línea</th>
            <th style="padding: 0.85rem 1.15rem;">Estado</th>
          </tr>
        </thead>
        <tbody style="font-size: 0.88rem; color: #334155;">
          <?php if (empty($logs)): ?>
            <tr>
              <td colspan="6" style="padding: 2.5rem; text-align: center; color: #94a3b8;">
                No hay registros de sesión con los filtros aplicados.
              </td>
            </tr>
          <?php else: ?>
            <?php foreach ($logs as $log): 
              $isCurrentlyActive = ($log['status'] === 'active' && empty($log['logout_at']) && (time() - strtotime($log['last_activity_at'])) < 180);
              $durationTxt = formatDuration($log['duration_seconds']);
            ?>
              <tr style="border-bottom: 1px solid #f1f5f9;">
                <td style="padding: 0.85rem 1.15rem; font-weight: 700;">
                  <div style="display: flex; align-items: center; gap: 0.5rem;">
                    <span><?= $log['user_role'] === 'admin' ? '👨‍🏫' : ($log['user_grade'] == 6 ? '🚀' : '⚡') ?></span>
                    <div>
                      <div><?= htmlspecialchars($log['user_name']) ?></div>
                      <small style="color: #94a3b8; font-weight: 600; font-size: 0.75rem;">
                        <?= $log['user_role'] === 'admin' ? 'Administrador' : $log['user_grade'] . 'to Grado' ?>
                      </small>
                    </div>
                  </div>
                </td>
                <td style="padding: 0.85rem 1.15rem;">
                  <strong><?= date('d/m/Y', strtotime($log['login_at'])) ?></strong><br>
                  <span style="color: #64748b; font-size: 0.82rem;"><?= date('h:i:s A', strtotime($log['login_at'])) ?></span>
                </td>
                <td style="padding: 0.85rem 1.15rem;">
                  <?= date('h:i:s A', strtotime($log['last_activity_at'])) ?>
                </td>
                <td style="padding: 0.85rem 1.15rem;">
                  <?php if (!empty($log['logout_at'])): ?>
                    <span style="color: #64748b;"><?= date('h:i:s A', strtotime($log['logout_at'])) ?></span>
                  <?php elseif ($isCurrentlyActive): ?>
                    <span style="color: #10b981; font-weight: 700;">Conectado ahora</span>
                  <?php else: ?>
                    <span style="color: #94a3b8;">Sesión finalizada</span>
                  <?php endif; ?>
                </td>
                <td style="padding: 0.85rem 1.15rem; font-weight: 800; color: #4338ca;">
                  ⏱️ <?= $durationTxt ?>
                </td>
                <td style="padding: 0.85rem 1.15rem;">
                  <?php if ($isCurrentlyActive): ?>
                    <span style="display: inline-block; background: #dcfce7; color: #15803d; font-size: 0.75rem; font-weight: 800; padding: 0.2rem 0.6rem; border-radius: 999px;">
                      🟢 En Línea
                    </span>
                  <?php else: ?>
                    <span style="display: inline-block; background: #f1f5f9; color: #64748b; font-size: 0.75rem; font-weight: 700; padding: 0.2rem 0.6rem; border-radius: 999px;">
                      ⚪ Cerrada
                    </span>
                  <?php endif; ?>
                </td>
              </tr>
            <?php endforeach; ?>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>

</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
