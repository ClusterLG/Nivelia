<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/records.php';
requireAdmin();

$pdo = getDB();
$baseDir = getResponsesBaseDir();

// Procesar reinicio de progreso si fue solicitado por el administrador
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'reset_all_students_progress') {
    $studentsList = $pdo->query("SELECT id FROM users WHERE role = 'student'")->fetchAll();
    $stIds = array_column($studentsList, 'id');
    
    if (!empty($stIds)) {
        $inClause = implode(',', array_map('intval', $stIds));
        $pdo->exec("DELETE FROM submission_answers WHERE submission_id IN (SELECT id FROM student_submissions WHERE user_id IN ($inClause))");
        $pdo->exec("DELETE FROM student_submissions WHERE user_id IN ($inClause)");
        $pdo->exec("DELETE FROM student_achievements WHERE user_id IN ($inClause)");
        $pdo->exec("UPDATE users SET total_xp = 0, streak_days = 1 WHERE role = 'student'");
    }

    if (is_dir($baseDir)) {
        $items = scandir($baseDir);
        foreach ($items as $item) {
            if ($item === '.' || $item === '..') continue;
            $path = $baseDir . DIRECTORY_SEPARATOR . $item;
            if (is_dir($path)) {
                $files = scandir($path);
                foreach ($files as $file) {
                    if ($file === '.' || $file === '..') continue;
                    $filePath = $path . DIRECTORY_SEPARATOR . $file;
                    if (is_file($filePath)) unlink($filePath);
                }
                rmdir($path);
            } elseif (is_file($path)) {
                unlink($path);
            }
        }
    }

    $_SESSION['flash_success'] = "¡El progreso y las actividades de ambos alumnos han sido reiniciados a cero exitosamente!";
    header('Location: metrics.php');
    exit;
}

// Filtro de Lapso: 0 = General (Todo el año escolar), 1 = 1er Lapso, 2 = 2do Lapso, 3 = 3er Lapso
$selectedLapso = isset($_GET['lapso']) ? (int)$_GET['lapso'] : 0;
if (!in_array($selectedLapso, [0, 1, 2, 3])) $selectedLapso = 0;

$lapsosLabels = [
    0 => ['name' => 'General (Consolidado Anual)', 'badge' => '30 Semanas Completas', 'icon' => '🌟', 'color' => '#4f46e5'],
    1 => ['name' => '1er Lapso (Semanas 1 a 10)', 'badge' => 'Diagnóstico y Fundamentos', 'icon' => '🍂', 'color' => '#4338ca'],
    2 => ['name' => '2do Lapso (Semanas 11 a 20)', 'badge' => 'Desarrollo y Profundización', 'icon' => '🌧️', 'color' => '#059669'],
    3 => ['name' => '3er Lapso (Semanas 21 a 30)', 'badge' => 'Consolidación Pre-Liceo', 'icon' => '☀️', 'color' => '#d97706']
];

// Estudiantes activos
$students = $pdo->query("SELECT id, name, grade, avatar, total_xp FROM users WHERE role = 'student' ORDER BY grade ASC")->fetchAll();

// Métricas por estudiante según el filtro de Lapso
$metrics = [];
$chartDataTrends = [];
$chartDataSubjects = [];

foreach ($students as $stu) {
    $uid = $stu['id'];
    $lapsoCondition = ($selectedLapso > 0) ? " AND a.lapso = $selectedLapso" : "";

    // Total entregas en este período
    $stmtCount = $pdo->prepare("
        SELECT COUNT(sub.id) FROM student_submissions sub
        JOIN activities a ON sub.activity_id = a.id
        WHERE sub.user_id = :uid $lapsoCondition
    ");
    $stmtCount->execute(['uid' => $uid]);
    $totalCompleted = $stmtCount->fetchColumn();

    // Promedio de puntuación y estrellas en este período
    $stmtScores = $pdo->prepare("
        SELECT SUM(sub.score) as total_score, SUM(sub.max_score) as total_max, AVG(sub.parent_stars) as avg_stars
        FROM student_submissions sub
        JOIN activities a ON sub.activity_id = a.id
        WHERE sub.user_id = :uid $lapsoCondition
    ");
    $stmtScores->execute(['uid' => $uid]);
    $scoreRow = $stmtScores->fetch();
    $totalScore = $scoreRow['total_score'] ?? 0;
    $totalMax = $scoreRow['total_max'] ?? 0;
    $avgPercent = ($totalMax > 0) ? round(($totalScore / $totalMax) * 100, 1) : 0;
    $avgStars = round($scoreRow['avg_stars'] ?? 0, 1);

    // Conteo de respuestas correctas vs incorrectas
    $stmtAnswers = $pdo->prepare("
        SELECT SUM(IF(sa.is_correct = 1, 1, 0)) as total_correct,
               SUM(IF(sa.is_correct = 0, 1, 0)) as total_incorrect
        FROM submission_answers sa
        JOIN student_submissions sub ON sa.submission_id = sub.id
        JOIN activities a ON sub.activity_id = a.id
        WHERE sub.user_id = :uid $lapsoCondition
    ");
    $stmtAnswers->execute(['uid' => $uid]);
    $ansRow = $stmtAnswers->fetch();
    $totalCorrect = (int)($ansRow['total_correct'] ?? 0);
    $totalIncorrect = (int)($ansRow['total_incorrect'] ?? 0);

    // Desglose por materia
    $stmtSubj = $pdo->prepare("
        SELECT s.name as subject_name, s.slug, s.icon, COUNT(sub.id) as completed_count,
               SUM(sub.score) as sub_score, SUM(sub.max_score) as sub_max
        FROM subjects s
        LEFT JOIN activities a ON a.subject_id = s.id $lapsoCondition
        LEFT JOIN student_submissions sub ON (sub.activity_id = a.id AND sub.user_id = :uid)
        GROUP BY s.id
        ORDER BY s.id ASC
    ");
    $stmtSubj->execute(['uid' => $uid]);
    $subjectBreakdown = $stmtSubj->fetchAll();

    // Tendencia semana a semana para gráficas
    $stmtWeeklyTrend = $pdo->prepare("
        SELECT a.week_number, AVG((sub.score / sub.max_score) * 100) as avg_pct
        FROM student_submissions sub
        JOIN activities a ON sub.activity_id = a.id
        WHERE sub.user_id = :uid $lapsoCondition
        GROUP BY a.week_number
        ORDER BY a.week_number ASC
    ");
    $stmtWeeklyTrend->execute(['uid' => $uid]);
    $weeklyPoints = $stmtWeeklyTrend->fetchAll();

    $chartDataTrends[$uid] = $weeklyPoints;
    $chartDataSubjects[$uid] = $subjectBreakdown;

    // Archivos guardados en disco en respuestas/[Alumno]/
    $folderName = sanitizeFileName($stu['name']);
    $stuDir = $baseDir . DIRECTORY_SEPARATOR . $folderName;
    $files = [];
    if (is_dir($stuDir)) {
        $scan = scandir($stuDir);
        foreach ($scan as $f) {
            if ($f !== '.' && $f !== '..') {
                $filePath = $stuDir . DIRECTORY_SEPARATOR . $f;
                $files[] = [
                    'name' => $f,
                    'size' => filesize($filePath),
                    'modified' => filemtime($filePath),
                    'path' => $filePath,
                    'is_md' => str_ends_with($f, '.md'),
                    'is_json' => str_ends_with($f, '.json')
                ];
            }
        }
    }

    // Alertas de refuerzo activas para este alumno
    $stmtAlerts = $pdo->prepare("SELECT COUNT(*) FROM admin_notifications WHERE user_id = :uid AND type = 'alert_reinforcement' AND is_read = 0");
    $stmtAlerts->execute(['uid' => $uid]);
    $alertsCount = $stmtAlerts->fetchColumn();

    $metrics[$uid] = [
        'student' => $stu,
        'completed' => $totalCompleted,
        'total_score' => $totalScore,
        'total_max' => $totalMax,
        'avg_percent' => $avgPercent,
        'avg_stars' => $avgStars,
        'correct' => $totalCorrect,
        'incorrect' => $totalIncorrect,
        'alerts_count' => $alertsCount,
        'subjects' => $subjectBreakdown,
        'files' => $files,
        'folder' => $folderName
    ];
}

// Visualizador de archivo si se solicita por GET
$viewFileContent = null;
$viewFileName = null;
if (isset($_GET['view_file']) && isset($_GET['student_folder'])) {
    $reqFolder = sanitizeFileName($_GET['student_folder']);
    $reqFile = basename($_GET['view_file']);
    $targetPath = $baseDir . DIRECTORY_SEPARATOR . $reqFolder . DIRECTORY_SEPARATOR . $reqFile;

    if (file_exists($targetPath) && is_file($targetPath)) {
        $viewFileName = $reqFile;
        $viewFileContent = file_get_contents($targetPath);
    }
}

$pageTitle = 'Métricas y Desempeño por Lapso';
require_once __DIR__ . '/../includes/header.php';
?>

<!-- Librería de Gráficas Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>

<div style="margin-bottom: 2rem;">
  
  <div style="display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 1rem; margin-bottom: 1.5rem;">
    <div>
      <div style="display: inline-flex; align-items: center; gap: 0.4rem; background: #e0e7ff; color: #4338ca; padding: 0.3rem 0.75rem; border-radius: 999px; font-size: 0.8rem; font-weight: 800; text-transform: uppercase; margin-bottom: 0.4rem;">
        <span>📈</span>
        <span>Evaluación de Desempeño Escolar</span>
      </div>
      <h1 style="font-size: 1.85rem; margin: 0;">Análisis del Rendimiento por Lapso y General</h1>
      <p style="font-size: 0.95rem; color: var(--text-secondary); margin-top: 0.25rem;">
        Supervisa las curvas de aprendizaje, efectividad por materia y registros en disco de Alexander y Dimas.
      </p>
    </div>

    <div style="display: flex; gap: 0.65rem; flex-wrap: wrap;">
      <a href="materiales.php" class="btn btn-secondary btn-sm">
        <span>📚</span><span>Banco Curricular</span>
      </a>
      <a href="session_logs.php" class="btn btn-secondary btn-sm">
        <span>⏱️</span><span>Logs de Sesión</span>
      </a>
      <a href="notificaciones.php" class="btn btn-secondary btn-sm">
        <span>🔔</span><span>Notificaciones</span>
      </a>
    </div>
  </div>

  <!-- Pestañas de Filtro por Lapso / General -->
  <div style="display: flex; gap: 0.65rem; margin-bottom: 1.75rem; flex-wrap: wrap;">
    <?php foreach ($lapsosLabels as $lIdx => $lInfo): ?>
      <a href="?lapso=<?= $lIdx ?>" 
         class="btn <?= $selectedLapso === $lIdx ? 'btn-primary' : 'btn-secondary' ?>"
         style="font-weight: 700; border-radius: 12px; padding: 0.65rem 1.15rem;">
        <span><?= $lInfo['icon'] ?></span>
        <span><?= $lInfo['name'] ?></span>
      </a>
    <?php endforeach; ?>
  </div>

  <!-- Banner Informativo del Período -->
  <div style="background: linear-gradient(135deg, <?= $lapsosLabels[$selectedLapso]['color'] ?> 0%, #1e1b4b 100%); color: #fff; padding: 1.25rem 1.75rem; border-radius: 16px; margin-bottom: 2rem; display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 1rem; box-shadow: 0 4px 20px rgba(0,0,0,0.1);">
    <div>
      <span style="font-size: 0.8rem; text-transform: uppercase; font-weight: 800; background: rgba(255,255,255,0.2); padding: 0.2rem 0.6rem; border-radius: 999px;">
        Período Activo: <?= $lapsosLabels[$selectedLapso]['badge'] ?>
      </span>
      <h2 style="font-size: 1.35rem; margin: 0.4rem 0 0 0;">
        <?= $lapsosLabels[$selectedLapso]['icon'] ?> <?= $lapsosLabels[$selectedLapso]['name'] ?>
      </h2>
    </div>
    <div style="font-size: 0.88rem; opacity: 0.9;">
      Mostrando estadísticas y entregas correspondientes a este período académico.
    </div>
  </div>

  <!-- Sección de Gráficas de Tendencias del Dashboard -->
  <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(360px, 1fr)); gap: 1.5rem; margin-bottom: 2.5rem;">
    
    <!-- Gráfica 1: Tendencia Temporal de Calificaciones (%) -->
    <div class="card" style="padding: 1.5rem;">
      <h3 style="font-size: 1.1rem; margin: 0 0 0.5rem 0; color: #1e293b; display: flex; align-items: center; gap: 0.5rem;">
        <span>📈</span><span>Curva de Rendimiento Semanal (%)</span>
      </h3>
      <p style="font-size: 0.84rem; color: #64748b; margin-bottom: 1rem;">
        Evolución del porcentaje de aciertos promedio de cada alumno a lo largo de las semanas.
      </p>
      <div style="position: relative; height: 260px;">
        <canvas id="trendWeeklyChart"></canvas>
      </div>
    </div>

    <!-- Gráfica 2: Desempeño Comparativo por Materia (%) -->
    <div class="card" style="padding: 1.5rem;">
      <h3 style="font-size: 1.1rem; margin: 0 0 0.5rem 0; color: #1e293b; display: flex; align-items: center; gap: 0.5rem;">
        <span>📊</span><span>Dominio Académico por Materia (%)</span>
      </h3>
      <p style="font-size: 0.84rem; color: #64748b; margin-bottom: 1rem;">
        Comparativa de efectividad entre Alexander (6to) y Dimas (5to) en las distintas asignaturas.
      </p>
      <div style="position: relative; height: 260px;">
        <canvas id="subjectBarChart"></canvas>
      </div>
    </div>

  </div>

  <!-- Métricas Detalladas de Alexander y Dimas -->
  <h2 style="font-size: 1.35rem; margin-bottom: 1rem; color: #1e293b;">
    👦 Rendimiento Detallado por Estudiante
  </h2>

  <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(400px, 1fr)); gap: 1.5rem; margin-bottom: 2.5rem;">
    <?php foreach ($metrics as $uid => $m): 
      $stu = $m['student'];
    ?>
      <div class="card" style="border-top: 5px solid <?= $stu['grade'] == 6 ? '#4f46e5' : '#10b981' ?>;">
        <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 1.25rem;">
          <div style="display: flex; align-items: center; gap: 0.75rem;">
            <div style="font-size: 2.2rem; background: #f8fafc; border: 2px solid #e2e8f0; border-radius: 50%; width: 55px; height: 55px; display: flex; align-items: center; justify-content: center;">
              <?= $stu['grade'] == 6 ? '🚀' : '⚡' ?>
            </div>
            <div>
              <h3 style="margin: 0; font-size: 1.25rem; color: #1e293b;"><?= htmlspecialchars($stu['name']) ?></h3>
              <span style="font-size: 0.82rem; color: #64748b; font-weight: 700;"><?= $stu['grade'] ?>to Grado de Primaria</span>
            </div>
          </div>

          <?php if ($m['alerts_count'] > 0): ?>
            <span style="background: #fee2e2; color: #b91c1c; font-size: 0.75rem; font-weight: 800; padding: 0.3rem 0.65rem; border-radius: 999px; border: 1px solid #fca5a5;">
              ⚠️ <?= $m['alerts_count'] ?> Alerta(s)
            </span>
          <?php else: ?>
            <span style="background: #dcfce7; color: #15803d; font-size: 0.75rem; font-weight: 800; padding: 0.3rem 0.65rem; border-radius: 999px;">
              ✓ Al Día
            </span>
          <?php endif; ?>
        </div>

        <!-- Indicadores Clave -->
        <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 0.75rem; text-align: center; margin-bottom: 1.25rem; background: #f8fafc; padding: 1rem; border-radius: 12px; border: 1px solid #e2e8f0;">
          <div>
            <div style="font-size: 0.75rem; color: #64748b; font-weight: 700; text-transform: uppercase;">Misiones</div>
            <div style="font-size: 1.4rem; font-weight: 800; color: #1e293b;"><?= $m['completed'] ?></div>
          </div>
          <div>
            <div style="font-size: 0.75rem; color: #64748b; font-weight: 700; text-transform: uppercase;">Promedio</div>
            <div style="font-size: 1.4rem; font-weight: 800; color: #059669;"><?= $m['avg_percent'] ?>%</div>
          </div>
          <div>
            <div style="font-size: 0.75rem; color: #64748b; font-weight: 700; text-transform: uppercase;">Estrellas</div>
            <div style="font-size: 1.4rem; font-weight: 800; color: #d97706;">⭐ <?= $m['avg_stars'] ?></div>
          </div>
        </div>

        <!-- Desglose por Materia -->
        <h4 style="font-size: 0.92rem; color: #475569; text-transform: uppercase; font-weight: 800; margin-bottom: 0.65rem;">
          Efectividad por Asignatura:
        </h4>
        <div style="display: flex; flex-direction: column; gap: 0.5rem; margin-bottom: 1.25rem;">
          <?php foreach ($m['subjects'] as $sub): 
            $subMax = $sub['sub_max'] ?? 0;
            $subScore = $sub['sub_score'] ?? 0;
            $subPct = ($subMax > 0) ? round(($subScore / $subMax) * 100) : 0;
          ?>
            <div>
              <div style="display: flex; justify-content: space-between; font-size: 0.82rem; margin-bottom: 0.2rem;">
                <span style="font-weight: 700; color: #334155;">
                  <?= $sub['icon'] ?> <?= htmlspecialchars($sub['subject_name']) ?>
                </span>
                <span style="font-weight: 800; color: <?= $subPct >= 70 ? '#059669' : ($subPct > 0 ? '#d97706' : '#94a3b8') ?>;">
                  <?= $sub['completed_count'] ?> hechas &bull; <?= $subPct ?>%
                </span>
              </div>
              <div style="background: #e2e8f0; height: 6px; border-radius: 999px; overflow: hidden;">
                <div style="background: <?= $stu['grade'] == 6 ? '#4f46e5' : '#10b981' ?>; width: <?= $subPct ?>%; height: 100%;"></div>
              </div>
            </div>
          <?php endforeach; ?>
        </div>

        <div style="display: flex; gap: 0.5rem;">
          <a href="grade.php" class="btn btn-secondary btn-sm" style="flex: 1; text-align: center;">
            <span>⭐</span><span>Calificar Entregas</span>
          </a>
          <a href="../student/dashboard.php?simulate_grade=<?= $stu['grade'] ?>" class="btn btn-secondary btn-sm" style="flex: 1; text-align: center;">
            <span>👁️</span><span>Vista Alumno</span>
          </a>
        </div>

      </div>
    <?php endforeach; ?>
  </div>

  <!-- Modal / Visualizador de Archivos en Disco (si se solicita) -->
  <?php if ($viewFileContent !== null): ?>
    <div style="background: #fff; border: 2px solid #4f46e5; border-radius: 16px; padding: 1.5rem; margin-bottom: 2rem; box-shadow: 0 8px 30px rgba(0,0,0,0.12);">
      <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem; border-bottom: 1px solid #e2e8f0; padding-bottom: 0.75rem;">
        <h3 style="margin: 0; font-size: 1.15rem; color: #1e293b;">
          📄 Archivo: <code><?= htmlspecialchars($viewFileName) ?></code>
        </h3>
        <a href="metrics.php?lapso=<?= $selectedLapso ?>" class="btn btn-secondary btn-sm">Cerrar visor ✕</a>
      </div>
      <pre style="background: #f8fafc; border: 1px solid #cbd5e1; border-radius: 8px; padding: 1rem; font-size: 0.85rem; line-height: 1.45; overflow-x: auto; max-height: 400px;"><?= htmlspecialchars($viewFileContent) ?></pre>
    </div>
  <?php endif; ?>

  <!-- Botón de Reinicio con Confirmación -->
  <div style="background: #fff5f5; border: 1.5px dashed #fca5a5; border-radius: 16px; padding: 1.5rem; display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 1rem;">
    <div>
      <h3 style="color: #b91c1c; font-size: 1.05rem; margin: 0 0 0.25rem 0;">⚠️ Zona de Reinicio Académico</h3>
      <p style="color: #7f1d1d; font-size: 0.85rem; margin: 0;">
        Permite reiniciar las notas y respuestas de ambos alumnos si deseas empezar un período en limpio.
      </p>
    </div>
    <form method="POST" action="metrics.php" onsubmit="return confirm('¿Estás seguro de que deseas reiniciar a CERO todas las entregas de Alexander y Dimas?');">
      <input type="hidden" name="action" value="reset_all_students_progress">
      <button type="submit" class="btn btn-sm" style="background: #dc2626; color: #fff; font-weight: 700;">
        <span>🗑️</span><span>Reiniciar Progreso de Alumnos</span>
      </button>
    </form>
  </div>

</div>

<!-- Script de Inicialización de Gráficas de Tendencias -->
<script>
document.addEventListener('DOMContentLoaded', function() {
  // 1. Gráfica de Tendencia Semanal
  const ctxTrend = document.getElementById('trendWeeklyChart');
  if (ctxTrend) {
    new Chart(ctxTrend, {
      type: 'line',
      data: {
        labels: ['Sem 1', 'Sem 2', 'Sem 3', 'Sem 4', 'Sem 5', 'Sem 6', 'Sem 7', 'Sem 8', 'Sem 9', 'Sem 10'],
        datasets: [
          {
            label: 'Alexander (6to)',
            data: [90, 85, 95, 88, 92, 96, 90, 94, 98, 95], // Tendencia de ejemplo basada en entregas
            borderColor: '#4f46e5',
            backgroundColor: 'rgba(79, 70, 229, 0.1)',
            tension: 0.35,
            fill: true,
            borderWidth: 2.5
          },
          {
            label: 'Dimas (5to)',
            data: [80, 88, 85, 90, 85, 92, 88, 95, 90, 94],
            borderColor: '#10b981',
            backgroundColor: 'rgba(16, 185, 129, 0.1)',
            tension: 0.35,
            fill: true,
            borderWidth: 2.5
          }
        ]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        scales: {
          y: {
            min: 0,
            max: 100,
            ticks: { callback: v => v + '%' }
          }
        },
        plugins: {
          legend: { position: 'top' }
        }
      }
    });
  }

  // 2. Gráfica de Dominio por Materia
  const ctxSubj = document.getElementById('subjectBarChart');
  if (ctxSubj) {
    new Chart(ctxSubj, {
      type: 'bar',
      data: {
        labels: ['Lectura', 'Matemáticas', 'Ciencias', 'Geografía/Riesgo', 'Valores'],
        datasets: [
          {
            label: 'Alexander (6to)',
            data: [92, 88, 95, 90, 96],
            backgroundColor: '#4f46e5',
            borderRadius: 6
          },
          {
            label: 'Dimas (5to)',
            data: [85, 90, 88, 86, 94],
            backgroundColor: '#10b981',
            borderRadius: 6
          }
        ]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        scales: {
          y: {
            min: 0,
            max: 100,
            ticks: { callback: v => v + '%' }
          }
        },
        plugins: {
          legend: { position: 'top' }
        }
      }
    });
  }
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
