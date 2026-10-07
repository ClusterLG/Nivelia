<?php
require_once __DIR__ . '/../config/db.php';
$user = currentUser();
$baseUrl = getBaseUrl();
$pageTitle = $pageTitle ?? 'Plataforma de Aprendizaje';
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= htmlspecialchars($pageTitle) ?> | <?= APP_NAME ?></title>
  
  <!-- Google Fonts: Plus Jakarta Sans -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  
  <!-- CSS Stylesheet -->
  <link rel="stylesheet" href="<?= $baseUrl ?>assets/css/style.css">
  <link rel="icon" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'><text y='.9em' font-size='90'>✨</text></svg>">
  <script>window.APP_BASE_URL = '<?= $baseUrl ?>';</script>
</head>
<body>

<header class="navbar">
  <div class="container navbar-inner">
    <a href="<?= $user ? ($user['role'] === 'admin' ? $baseUrl . 'admin/index.php' : $baseUrl . 'student/dashboard.php') : $baseUrl . 'index.php' ?>" class="brand">
      <?php if (file_exists(__DIR__ . '/../assets/images/logo.png')): ?>
        <img src="<?= $baseUrl ?>assets/images/logo.png" alt="<?= APP_NAME ?>" style="height: 36px; max-width: 150px; object-fit: contain;">
      <?php elseif (file_exists(__DIR__ . '/../assets/images/logo.svg')): ?>
        <img src="<?= $baseUrl ?>assets/images/logo.svg" alt="<?= APP_NAME ?>" style="height: 36px; max-width: 150px; object-fit: contain;">
      <?php else: ?>
        <span class="brand-icon">✨</span>
        <span class="brand-text">Nivel<span class="brand-tag">ia</span></span>
      <?php endif; ?>
    </a>

    <div class="nav-user">
      <?php if ($user): ?>
        <?php if ($user['role'] === 'admin'): 
          $unreadNotifs = getUnreadAdminNotificationsCount();
        ?>
          <a href="<?= $baseUrl ?>admin/index.php" class="nav-btn" title="Resumen">
            <span>📊</span><span class="nav-label">Resumen</span>
          </a>
          <a href="<?= $baseUrl ?>admin/materiales.php" class="nav-btn" title="Banco Curricular de Materiales (3 Lapsos)">
            <span>📚</span><span class="nav-label">Materiales</span>
          </a>
          <a href="<?= $baseUrl ?>admin/calendario.php" class="nav-btn nav-btn-primary" title="Calendario de Contenidos por Semanas">
            <span>🗓️</span><span class="nav-label">Calendario</span>
          </a>
          <a href="<?= $baseUrl ?>admin/planner.php" class="nav-btn" title="Plan Semanal Diario">
            <span>📅</span><span class="nav-label">Plan</span>
          </a>
          <a href="<?= $baseUrl ?>admin/grade.php" class="nav-btn" title="Evaluar">
            <span>⭐</span><span class="nav-label">Evaluar</span>
          </a>
          <a href="<?= $baseUrl ?>admin/notificaciones.php" class="nav-btn" title="Centro de Notificaciones y Alertas" style="position: relative;">
            <span>🔔</span><span class="nav-label">Alertas</span>
            <?php if ($unreadNotifs > 0): ?>
              <span style="position: absolute; top: -3px; right: -3px; background: #ef4444; color: #fff; font-size: 0.68rem; font-weight: 800; border-radius: 999px; min-width: 18px; height: 18px; display: inline-flex; align-items: center; justify-content: center; padding: 0 4px; box-shadow: 0 2px 6px rgba(239,68,68,0.5);">
                <?= $unreadNotifs ?>
              </span>
            <?php endif; ?>
          </a>
          <a href="<?= $baseUrl ?>admin/session_logs.php" class="nav-btn" title="Auditoría de Sesiones y Tiempo en Línea">
            <span>⏱️</span><span class="nav-label">Sesiones</span>
          </a>
          <a href="<?= $baseUrl ?>logout.php" class="nav-btn-logout" title="Cerrar sesión">
            <span>🚪</span>
          </a>
        <?php else: ?>
          <a href="<?= $baseUrl ?>student/dashboard.php" class="nav-btn" title="Misiones">
            <span>🎯</span><span class="nav-label">Misiones</span>
          </a>
          <a href="<?= $baseUrl ?>student/personajes.php" class="nav-btn" title="Amigos de la Fauna Venezolana">
            <span>🐾</span><span class="nav-label">Amigos</span>
          </a>
          <a href="<?= $baseUrl ?>student/achievements.php" class="nav-btn" title="Medallas">
            <span>🏆</span><span class="nav-label">Medallas</span>
          </a>
          <div class="xp-pill" title="Puntos de Experiencia">
            <span>⚡</span>
            <span class="xp-val"><?= (int)($user['total_xp'] ?? 0) ?> XP</span>
          </div>
          <a href="<?= $baseUrl ?>logout.php" class="nav-btn-logout" title="Cerrar sesión">
            <span>🚪</span>
          </a>
        <?php endif; ?>
      <?php else: ?>
        <?php if (basename($_SERVER['PHP_SELF']) !== 'login.php'): ?>
          <a href="<?= $baseUrl ?>login.php" class="btn btn-primary btn-sm">Iniciar Sesión</a>
        <?php else: ?>
          <span style="font-size: 0.82rem; font-weight: 700; color: var(--primary); background: rgba(79, 70, 229, 0.08); padding: 0.4rem 0.8rem; border-radius: 999px; border: 1px solid rgba(79, 70, 229, 0.15);">✨ Portal de Nivelación</span>
        <?php endif; ?>
      <?php endif; ?>
    </div>
  </div>
</header>

<main class="container" style="flex: 1; padding-top: 1.25rem; padding-bottom: 2.5rem;">
<?php if (isset($_SESSION['flash_success'])): ?>
  <div style="background: #d1fae5; color: #065f46; border: 1px solid #a7f3d0; padding: 0.75rem 1rem; border-radius: var(--radius-md); margin-bottom: 1.25rem; font-weight: 600; display: flex; align-items: center; gap: 0.5rem; font-size: 0.92rem;">
    <span>✅</span>
    <span><?= htmlspecialchars($_SESSION['flash_success']) ?></span>
  </div>
  <?php unset($_SESSION['flash_success']); ?>
<?php endif; ?>

<?php if (isset($_SESSION['flash_error'])): ?>
  <div style="background: #fee2e2; color: #991b1b; border: 1px solid #fecaca; padding: 0.75rem 1rem; border-radius: var(--radius-md); margin-bottom: 1.25rem; font-weight: 600; display: flex; align-items: center; gap: 0.5rem; font-size: 0.92rem;">
    <span>⚠️</span>
    <span><?= htmlspecialchars($_SESSION['flash_error']) ?></span>
  </div>
  <?php unset($_SESSION['flash_error']); ?>
<?php endif; ?>
