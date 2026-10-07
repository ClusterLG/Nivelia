<?php
require_once __DIR__ . '/../config/db.php';
requireLogin();

$user = currentUser();
$pdo = getDB();

// Todos los logros
$allAchievements = $pdo->query("SELECT * FROM achievements ORDER BY id ASC")->fetchAll();

// Logros desbloqueados por el usuario
$stmtUserAch = $pdo->prepare("
    SELECT achievement_id, unlocked_at 
    FROM student_achievements 
    WHERE user_id = :uid
");
$stmtUserAch->execute(['uid' => $user['id']]);
$unlockedMap = [];
foreach ($stmtUserAch->fetchAll() as $row) {
    $unlockedMap[$row['achievement_id']] = $row['unlocked_at'];
}

$pageTitle = 'Sala de Trofeos y Medallas';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="container-narrow">
  <div style="text-align: center; margin-bottom: 2rem;">
    <div style="font-size: 3.5rem; margin-bottom: 0.5rem;">🏆</div>
    <h1>Sala de Medallas y Trofeos</h1>
    <p>¡Colecciona insignias demostrando tu constancia, inteligencia y valentía!</p>
    
    <div style="margin-top: 1rem; display: inline-flex; align-items: center; gap: 0.5rem; background: #fef3c7; border: 1px solid #fde68a; padding: 0.5rem 1.25rem; border-radius: var(--radius-full); font-weight: 700; color: #b45309;">
      <span>⚡ Total Acumulado:</span>
      <span><?= (int)($user['total_xp'] ?? 0) ?> Puntos de Experiencia (XP)</span>
    </div>
  </div>

  <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 1.25rem;">
    <?php foreach ($allAchievements as $ach): 
      $isUnlocked = isset($unlockedMap[$ach['id']]);
    ?>
      <div class="card" style="display: flex; align-items: center; gap: 1rem; border: 2px solid <?= $isUnlocked ? '#fde68a' : 'var(--border)' ?>; background: <?= $isUnlocked ? '#fffbeb' : 'var(--bg-card)' ?>; opacity: <?= $isUnlocked ? '1' : '0.6' ?>;">
        <div style="font-size: 2.8rem; min-width: 60px; height: 60px; display: flex; align-items: center; justify-content: center; background: <?= $isUnlocked ? '#fef3c7' : '#f1f5f9' ?>; border-radius: 50%;">
          <?= $ach['icon'] ?>
        </div>
        <div>
          <h3 style="font-size: 1.05rem; margin-bottom: 0.2rem; color: <?= $isUnlocked ? '#92400e' : 'var(--text-primary)' ?>;">
            <?= htmlspecialchars($ach['name']) ?>
          </h3>
          <p style="font-size: 0.85rem; color: var(--text-secondary); margin-bottom: 0.4rem;">
            <?= htmlspecialchars($ach['description']) ?>
          </p>
          <div style="display: flex; align-items: center; gap: 0.5rem; font-size: 0.78rem; font-weight: 700;">
            <?php if ($isUnlocked): ?>
              <span style="color: #059669;">✅ Desbloqueado</span>
              <span style="color: var(--text-muted);">(+<?= $ach['xp_bonus'] ?> XP)</span>
            <?php else: ?>
              <span style="color: var(--text-muted);">🔒 Por desbloquear</span>
            <?php endif; ?>
          </div>
        </div>
      </div>
    <?php endforeach; ?>
  </div>

  <div style="margin-top: 2.5rem; text-align: center;">
    <a href="dashboard.php" class="btn btn-primary">
      🚀 Volver a mis Misiones Diarias
    </a>
  </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
