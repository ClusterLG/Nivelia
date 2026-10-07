<?php
require_once __DIR__ . '/../config/db.php';
requireLogin();

$user = currentUser();
$pdo = getDB();
$activityId = (int)($_GET['id'] ?? 0);

if (!$activityId) {
    header('Location: dashboard.php');
    exit;
}

// Obtener datos de la actividad y materia
$stmt = $pdo->prepare("
    SELECT a.*, s.name as subject_name, s.slug as subject_slug, s.icon as subject_icon, s.color as subject_color
    FROM activities a
    JOIN subjects s ON a.subject_id = s.id
    WHERE a.id = :id AND a.is_active = 1
");
$stmt->execute(['id' => $activityId]);
$activity = $stmt->fetch();

if (!$activity) {
    die("Actividad no encontrada o desactivada.");
}

// Bloqueo estricto: Los alumnos solo pueden ver los días de la semana activa y solo días transcurridos y el actual (medianoche)
if (!isDayUnlockedForStudents($activity['day_of_week'], $activity['week_number'], $user['grade'])) {
    $_SESSION['flash_error'] = "🔒 Esta misión pertenece a la Semana " . $activity['week_number'] . " (" . getDayNameSpanish($activity['day_of_week']) . ") y aún no está disponible. Solo puedes responder las misiones de los días transcurridos y de hoy en tu semana activa. Los días se actualizan automáticamente a medianoche.";
    header('Location: dashboard.php');
    exit;
}

// Obtener preguntas
$stmtQ = $pdo->prepare("SELECT * FROM activity_questions WHERE activity_id = :aid ORDER BY id ASC");
$stmtQ->execute(['aid' => $activityId]);
$questions = $stmtQ->fetchAll();

// Verificar si ya fue entregada por este usuario
$stmtSub = $pdo->prepare("SELECT * FROM student_submissions WHERE activity_id = :aid AND user_id = :uid");
$stmtSub->execute(['aid' => $activityId, 'uid' => $user['id']]);
$submission = $stmtSub->fetch();

$answersMap = [];
if ($submission) {
    $stmtAns = $pdo->prepare("SELECT * FROM submission_answers WHERE submission_id = :sid");
    $stmtAns->execute(['sid' => $submission['id']]);
    foreach ($stmtAns->fetchAll() as $ans) {
        $answersMap[$ans['question_id']] = $ans;
    }
}

$justCompleted = false;

// PROCESAR RESPUESTAS
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$submission) {
    $submittedAnswers = $_POST['answers'] ?? [];
    $totalScore = 0;
    $maxScore = 0;

    // Calcular puntajes automáticos
    $processedAnswers = [];
    foreach ($questions as $q) {
        $qid = $q['id'];
        $userAns = trim($submittedAnswers[$qid] ?? '');
        $isCorrect = null;
        $awarded = 0;
        $maxScore += $q['points'];

        if ($q['question_type'] === 'multiple_choice' || $q['question_type'] === 'true_false') {
            if (mb_strtolower($userAns) === mb_strtolower(trim($q['correct_answer']))) {
                $isCorrect = 1;
                $awarded = $q['points'];
                $totalScore += $awarded;
            } else {
                $isCorrect = 0;
                $awarded = 0;
            }
        } else {
            // Preguntas abiertas/reflexivas: se les da un puntaje base por responder y el padre las evalúa
            if (!empty($userAns)) {
                $awarded = (int)($q['points'] * 0.5); // 50% provisional al responder
                $totalScore += $awarded;
            }
        }

        $processedAnswers[] = [
            'question_id' => $qid,
            'answer_text' => $userAns,
            'is_correct' => $isCorrect,
            'points_awarded' => $awarded
        ];
    }

    // Insertar submission
    $stmtInsertSub = $pdo->prepare("
        INSERT INTO student_submissions (user_id, activity_id, score, max_score, status, submitted_at)
        VALUES (:uid, :aid, :score, :max_score, 'completed', NOW())
    ");
    $stmtInsertSub->execute([
        'uid' => $user['id'],
        'aid' => $activityId,
        'score' => $totalScore,
        'max_score' => $maxScore
    ]);
    $subId = $pdo->lastInsertId();

    // Insertar detalle de respuestas
    $stmtInsertAns = $pdo->prepare("
        INSERT INTO submission_answers (submission_id, question_id, answer_text, is_correct, points_awarded)
        VALUES (:sid, :qid, :txt, :corr, :pts)
    ");
    foreach ($processedAnswers as $pa) {
        $stmtInsertAns->execute([
            'sid' => $subId,
            'qid' => $pa['question_id'],
            'txt' => $pa['answer_text'],
            'corr' => $pa['is_correct'],
            'pts' => $pa['points_awarded']
        ]);
    }

    // Contar respuestas incorrectas en esta entrega
    $incorrectInSubmission = 0;
    foreach ($processedAnswers as $pa) {
        if ($pa['is_correct'] === 0) {
            $incorrectInSubmission++;
        }
    }

    // Comprobar si hay 3 fallas acumuladas en el mismo objetivo
    $stmtFailedStreak = $pdo->prepare("
        SELECT COUNT(*) FROM submission_answers sa
        JOIN student_submissions ss ON sa.submission_id = ss.id
        JOIN activities a ON ss.activity_id = a.id
        WHERE ss.user_id = :uid AND a.title = :title AND sa.is_correct = 0
    ");
    $stmtFailedStreak->execute([
        'uid' => $user['id'],
        'title' => $activity['title']
    ]);
    $totalFailedInObjective = (int)$stmtFailedStreak->fetchColumn();

    // 1. Notificación al Admin por la nueva entrega
    $pctScore = ($maxScore > 0) ? round(($totalScore / $maxScore) * 100) : 0;
    createAdminNotification(
        $user['id'],
        'submission',
        '📝 Nueva entrega: ' . $activity['title'],
        $user['name'] . ' (' . $user['grade'] . 'to Grado) completó la misión de ' . $activity['subject_name'] . ' obteniendo ' . $totalScore . '/' . $maxScore . ' pts (' . $pctScore . '%).',
        'grade.php'
    );

    // 2. Alerta Pedagógica de Refuerzo si falló 3 veces en el mismo objetivo
    if ($incorrectInSubmission >= 3 || $totalFailedInObjective >= 3) {
        createAdminNotification(
            $user['id'],
            'alert_reinforcement',
            '⚠️ Alerta Pedagógica: ' . $user['name'] . ' (' . $activity['subject_name'] . ')',
            $user['name'] . ' ha respondido de forma incorrecta 3 veces en preguntas del objetivo: "' . $activity['title'] . '". Requiere repaso guiado con papá.',
            'grade.php'
        );
    }

    // Sumar XP al estudiante
    $xpEarned = $activity['xp_reward'];
    $stmtXP = $pdo->prepare("UPDATE users SET total_xp = total_xp + :xp WHERE id = :uid");
    $stmtXP->execute(['xp' => $xpEarned, 'uid' => $user['id']]);
    $_SESSION['user']['total_xp'] += $xpEarned;

    // Desbloquear logro 'primer_paso' si es su primera actividad
    $stmtFirst = $pdo->prepare("SELECT COUNT(*) FROM student_submissions WHERE user_id = :uid");
    $stmtFirst->execute(['uid' => $user['id']]);
    if ($stmtFirst->fetchColumn() == 1) {
        $achId = $pdo->query("SELECT id FROM achievements WHERE slug = 'primer_paso'")->fetchColumn();
        if ($achId) {
            $pdo->prepare("INSERT IGNORE INTO student_achievements (user_id, achievement_id) VALUES (:uid, :aid)")
                ->execute(['uid' => $user['id'], 'aid' => $achId]);
        }
    }

    // Desbloquear logro 'guardian_guaira' si completó actividad de riesgo_laguaira
    if ($activity['subject_slug'] === 'riesgo_laguaira') {
        $achId = $pdo->query("SELECT id FROM achievements WHERE slug = 'guardian_guaira'")->fetchColumn();
        if ($achId) {
            $pdo->prepare("INSERT IGNORE INTO student_achievements (user_id, achievement_id) VALUES (:uid, :aid)")
                ->execute(['uid' => $user['id'], 'aid' => $achId]);
        }
    }

    // Desbloquear logro 'guardian_biodiversidad' si completó actividad de biologia
    if ($activity['subject_slug'] === 'biologia') {
        $achId = $pdo->query("SELECT id FROM achievements WHERE slug = 'guardian_biodiversidad'")->fetchColumn();
        if ($achId) {
            $pdo->prepare("INSERT IGNORE INTO student_achievements (user_id, achievement_id) VALUES (:uid, :aid)")
                ->execute(['uid' => $user['id'], 'aid' => $achId]);
        }
    }

    // Desbloquear logro 'ciudadano_ejemplar' si completó actividad de ciudadania
    if ($activity['subject_slug'] === 'ciudadania') {
        $achId = $pdo->query("SELECT id FROM achievements WHERE slug = 'ciudadano_ejemplar'")->fetchColumn();
        if ($achId) {
            $pdo->prepare("INSERT IGNORE INTO student_achievements (user_id, achievement_id) VALUES (:uid, :aid)")
                ->execute(['uid' => $user['id'], 'aid' => $achId]);
        }
    }

    // Registrar y archivar respuestas en disco: respuestas/[Alumno]/[Fecha]_[Materia]...
    require_once __DIR__ . '/../config/records.php';
    recordSubmissionToDisk($subId, $pdo);

    $justCompleted = true;

    // Recargar submission y respuestas
    $stmtSub->execute(['aid' => $activityId, 'uid' => $user['id']]);
    $submission = $stmtSub->fetch();
    $stmtAns = $pdo->prepare("SELECT * FROM submission_answers WHERE submission_id = :sid");
    $stmtAns->execute(['sid' => $submission['id']]);
    foreach ($stmtAns->fetchAll() as $ans) {
        $answersMap[$ans['question_id']] = $ans;
    }
}

require_once __DIR__ . '/../includes/personajes.php';
$mascot = getPersonajeBySubject($activity['subject_slug']);
$pageTitle = $activity['title'];
require_once __DIR__ . '/../includes/header.php';
?>

<!-- Fondo Temático Suave del Hábitat Venezolano con Parallax Scroll-Driven -->
<?= renderHabitatParallaxBackground($mascot['id'] ?? 'chiguire') ?>

<div class="container" style="max-width: 1060px;">
  
  <!-- Navegación de retorno -->
  <div style="margin-bottom: 1.25rem;">
    <a href="dashboard.php?day=<?= $activity['day_of_week'] ?>" class="btn btn-secondary btn-sm">
      ⬅️ Volver a las Misiones de <?= getDayNameSpanish($activity['day_of_week']) ?>
    </a>
  </div>

  <!-- Encabezado de la Misión -->
  <div class="card" style="margin-bottom: 1.5rem; border-top: 5px solid <?= $activity['subject_color'] ?>; background: rgba(255,255,255,0.96); backdrop-filter: blur(10px);">
    <div style="display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 0.5rem; margin-bottom: 0.75rem;">
      <span class="badge-subject badge-<?= $activity['subject_slug'] ?>">
        <?= $activity['subject_icon'] ?> <?= htmlspecialchars($activity['subject_name']) ?>
      </span>
      <span class="xp-tag" style="font-size: 1rem;">
        ⚡ Recompensa: +<?= $activity['xp_reward'] ?> XP
      </span>
    </div>

    <h1 style="font-size: 1.6rem; margin-bottom: 0.5rem;"><?= htmlspecialchars($activity['title']) ?></h1>
    <p style="font-size: 1rem; color: var(--text-secondary);"><?= htmlspecialchars($activity['summary']) ?></p>
  </div>

  <!-- Rejilla Interactiva: Diálogos/Lectura/Preguntas al centro, Mascota Tutora al lado -->
  <div class="activity-layout-grid">
    
    <!-- Columna Principal -->
    <div class="activity-main-col">

      <!-- Cuadro de Diálogo Superior con la Mascota Tutora -->
      <?php if ($mascot && !$submission): ?>
        <?= renderMascotDialogue($mascot['id'], $mascot['dialogos']['lectura'] ?? $mascot['dialogos']['bienvenida'], $mascot['nombre'] . ' te guía:') ?>
      <?php elseif ($mascot && $submission): ?>
        <?= renderMascotDialogue($mascot['id'], $mascot['dialogos']['felicitacion'], '¡' . $mascot['nombre'] . ' celebra tu trabajo!') ?>
      <?php endif; ?>

      <?php if ($justCompleted): ?>
        <!-- Alerta de Misión Superada con animación -->
        <div style="background: linear-gradient(135deg, #10b981, #059669); color: #ffffff; padding: 1.75rem; border-radius: var(--radius-lg); text-align: center; box-shadow: var(--shadow-lg);">
          <div style="font-size: 3.5rem; margin-bottom: 0.5rem;">🎉🏆</div>
          <h2 style="color: #ffffff; font-size: 1.7rem; margin-bottom: 0.5rem;">¡MISIÓN CUMPLIDA CON ÉXITO!</h2>
          <p style="color: #d1fae5; font-size: 1.1rem; max-width: 550px; margin: 0 auto 1rem auto;">
            ¡Excelente trabajo, <?= htmlspecialchars($user['name']) ?>! Has ganado <strong>+<?= $activity['xp_reward'] ?> XP</strong> de experiencia.
          </p>
          <div style="display: inline-block; background: rgba(255,255,255,0.2); padding: 0.5rem 1.25rem; border-radius: var(--radius-full); font-weight: 700; font-size: 1rem;">
            Tu puntuación inicial: <?= $submission['score'] ?> / <?= $submission['max_score'] ?> pts
          </div>
        </div>
        <script>
          document.addEventListener('DOMContentLoaded', () => {
            triggerConfetti();
            SoundFx.success();
          });
        </script>
      <?php endif; ?>

      <!-- SECCIÓN 1: APRENDE Y DESCUBRE (LECTURA DIDÁCTICA) -->
      <div class="learning-section reading-main-card" id="readingSection">
        <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 1rem; border-bottom: 2px solid var(--border); padding-bottom: 0.75rem;">
          <div style="display: flex; align-items: center; gap: 0.5rem;">
            <span style="font-size: 1.75rem;">📖</span>
            <h2 style="font-size: 1.35rem; color: var(--text-primary);">Paso 1: Lee y Aprende</h2>
          </div>
          <?php if (!$submission): ?>
            <span style="font-size: 0.8rem; background: #e0e7ff; color: #3730a3; padding: 0.25rem 0.6rem; border-radius: var(--radius-full); font-weight: 700;">
              Lectura Requerida
            </span>
          <?php endif; ?>
        </div>

        <div class="reading-body">
          <?= nl2br(htmlspecialchars($activity['reading_content'])) ?>
        </div>

        <?php if (!empty($activity['practical_exercise'])): ?>
          <div class="practical-box">
            <h4>💡 Reto de Práctica</h4>
            <p style="font-size: 0.95rem; margin: 0;"><?= htmlspecialchars($activity['practical_exercise']) ?></p>
          </div>
        <?php endif; ?>

        <?php if (!$submission): ?>
          <div style="margin-top: 2rem; padding-top: 1.25rem; border-top: 2px dashed var(--border); text-align: center;">
            <button type="button" class="btn btn-primary btn-lg btn-block" id="btnGoQuestions" onclick="unlockQuestions()" style="padding: 1rem 1.5rem; font-size: 1.1rem; box-shadow: 0 4px 15px rgba(79, 70, 229, 0.35);">
              <span>✍️ Ya terminé de leer ➔</span>
              <span>¡Ir al Panel de Preguntas! (<?= count($questions) ?> preguntas)</span>
            </button>
            <p style="font-size: 0.85rem; color: var(--text-muted); margin-top: 0.5rem;">
              Lee con atención antes de avanzar para responder con seguridad.
            </p>
          </div>
        <?php endif; ?>
      </div>

      <!-- SECCIÓN 2: RESPONDE Y PON A PRUEBA TU SABER -->
      <div class="card questions-main-card" id="questionsSection" style="border: 2px solid var(--border); <?= (!$submission) ? 'display: none;' : '' ?>">
        <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 1.5rem; border-bottom: 2px solid var(--border); padding-bottom: 0.75rem; flex-wrap: wrap; gap: 0.5rem;">
          <div style="display: flex; align-items: center; gap: 0.5rem;">
            <span style="font-size: 1.75rem;">✍️</span>
            <h2 style="font-size: 1.35rem;">Paso 2: Responde las Preguntas</h2>
          </div>
      <div style="display: flex; align-items: center; gap: 0.5rem;">
        <button type="button" class="btn btn-secondary btn-sm" onclick="toggleReading()" id="btnToggleReading" title="Consultar el texto">
          📖 Ver Lectura
        </button>
        <span style="font-size: 0.88rem; font-weight: 700; color: var(--text-muted);">
          <?= count($questions) ?> preguntas
        </span>
      </div>
    </div>

    <?php if ($submission): ?>
      <!-- MODO REVISIÓN (YA ENTREGADO) -->
      <div style="margin-bottom: 1.5rem;">
        <?php foreach ($questions as $idx => $q): 
          $ans = $answersMap[$q['id']] ?? null;
          $userAnsText = $ans['answer_text'] ?? 'Sin respuesta';
          $isCorrect = $ans['is_correct'] ?? null;
        ?>
          <div class="question-card" style="border-color: <?= $isCorrect === 1 ? '#a7f3d0' : ($isCorrect === 0 ? '#fecaca' : '#e2e8f0') ?>;">
            <div class="question-title">
              <span class="question-number"><?= $idx + 1 ?></span>
              <span><?= htmlspecialchars($q['question_text']) ?></span>
            </div>

            <div style="background: #f8fafc; padding: 1rem; border-radius: var(--radius-md); margin-top: 0.75rem;">
              <div style="font-size: 0.85rem; font-weight: 700; color: var(--text-secondary); margin-bottom: 0.25rem;">
                Tu respuesta:
              </div>
              <div style="font-size: 1rem; font-weight: 600; color: var(--text-primary);">
                <?= htmlspecialchars($userAnsText) ?>
              </div>

              <?php if ($isCorrect === 1): ?>
                <div style="color: #059669; font-weight: 700; font-size: 0.85rem; margin-top: 0.5rem; display: flex; align-items: center; gap: 0.35rem;">
                  <span>✅ ¡Respuesta Correcta!</span>
                  <span>(+<?= $ans['points_awarded'] ?> pts)</span>
                </div>
              <?php elseif ($isCorrect === 0): ?>
                <div style="color: #dc2626; font-weight: 700; font-size: 0.85rem; margin-top: 0.5rem;">
                  ❌ Respuesta esperada: <?= htmlspecialchars($q['correct_answer']) ?>
                </div>
              <?php else: ?>
                <div style="color: #4f46e5; font-weight: 600; font-size: 0.85rem; margin-top: 0.5rem;">
                  📝 Respuesta enviada para que papá la lea y califique.
                </div>
              <?php endif; ?>

              <?php if (!empty($q['explanation'])): ?>
                <div class="explanation-box">
                  💡 <strong>Explicación didáctica:</strong> <?= htmlspecialchars($q['explanation']) ?>
                </div>
              <?php endif; ?>
            </div>
          </div>
        <?php endforeach; ?>

        <?php if ($submission['parent_stars'] > 0 || !empty($submission['parent_feedback'])): ?>
          <div style="background: #fefce8; border: 2px solid #fef08a; padding: 1.25rem; border-radius: var(--radius-lg); margin-top: 1.5rem;">
            <h4 style="color: #854d0e; margin-bottom: 0.5rem;">⭐ Calificación y Mensaje de Papá</h4>
            <?php if ($submission['parent_stars'] > 0): ?>
              <div class="star-rating" style="margin-bottom: 0.5rem;">
                <?= str_repeat('⭐', $submission['parent_stars']) ?>
              </div>
            <?php endif; ?>
            <?php if (!empty($submission['parent_feedback'])): ?>
              <p style="color: #713f12; font-size: 1rem; font-style: italic;">
                "<?= htmlspecialchars($submission['parent_feedback']) ?>"
              </p>
            <?php endif; ?>
          </div>
        <?php endif; ?>
      </div>

    <?php else: ?>
      <!-- FORMULARIO PARA RESPONDER -->
      <form method="POST" action="activity.php?id=<?= $activityId ?>">
        <?php foreach ($questions as $idx => $q): ?>
          <div class="question-card">
            <div class="question-title">
              <span class="question-number"><?= $idx + 1 ?></span>
              <span><?= htmlspecialchars($q['question_text']) ?></span>
            </div>

            <?php if ($q['question_type'] === 'multiple_choice' || $q['question_type'] === 'true_false'): ?>
              <?php 
                $options = json_decode($q['options_json'], true) ?? [];
                if (empty($options) && $q['question_type'] === 'true_false') {
                    $options = ['Verdadero', 'Falso'];
                }
                // Aleatorizar el orden de las opciones para que la respuesta correcta no tenga posición fija
                if ($q['question_type'] === 'multiple_choice' && is_array($options)) {
                    shuffle($options);
                }
              ?>
              <div class="options-list">
                <?php foreach ($options as $optIdx => $opt): ?>
                  <label class="option-label">
                    <input type="radio" name="answers[<?= $q['id'] ?>]" value="<?= htmlspecialchars($opt) ?>" required>
                    <span><?= htmlspecialchars($opt) ?></span>
                  </label>
                <?php endforeach; ?>
              </div>

            <?php else: ?>
              <!-- Pregunta de texto o reflexiva -->
              <div>
                <textarea 
                  name="answers[<?= $q['id'] ?>]" 
                  class="input-textarea" 
                  placeholder="Escribe aquí tu respuesta con tus propias palabras..." 
                  required></textarea>
                <div style="font-size: 0.8rem; color: var(--text-muted); margin-top: 0.4rem;">
                  ✍️ Escribe con calma; tu papá leerá tu respuesta con mucha atención.
                </div>
              </div>
            <?php endif; ?>
          </div>
        <?php endforeach; ?>

        <div style="margin-top: 2rem;">
          <button type="submit" class="btn btn-primary btn-block btn-lg" onclick="SoundFx.coin()">
            <span>🚀</span>
            <span>¡Entregar Misión y Ganar <?= $activity['xp_reward'] ?> XP!</span>
          </button>
        </div>
      </form>
    <?php endif; ?>

      </div> <!-- fin #questionsSection -->

    </div> <!-- fin .activity-main-col -->

    <!-- Columna Lateral Sticky: Compañero Mascota Interactivo a los lados de los cuadros -->
    <aside class="activity-side-col">
      <?php if ($mascot): ?>
        <?= renderSideMascotCompanion($mascot['id'], $user['name']) ?>
      <?php endif; ?>
    </aside>

  </div> <!-- fin .activity-layout-grid -->

</div> <!-- fin .container -->

<script>
function unlockQuestions() {
  const qSection = document.getElementById('questionsSection');
  const btnGo = document.getElementById('btnGoQuestions');
  
  if (qSection) {
    qSection.style.display = 'block';
    SoundFx.click();
    
    // Animar mascota compañera al pasar a las preguntas
    <?php if ($mascot): ?>
    const slug = '<?= htmlspecialchars($mascot['id']) ?>';
    const sideMsg = document.getElementById('sideSpeechMessage_' + slug);
    const sideImg = document.getElementById('sideMascotImg_' + slug);
    if (sideMsg) {
      sideMsg.style.opacity = '0';
      setTimeout(() => {
        sideMsg.textContent = '¡Felicidades por leer, <?= htmlspecialchars($user['name']) ?>! Ahora responde las preguntas con calma. ¡Confío en tu inteligencia!';
        sideMsg.style.opacity = '1';
      }, 150);
    }
    if (sideImg) {
      sideImg.classList.remove('bounce-once');
      void sideImg.offsetWidth;
      sideImg.classList.add('bounce-once');
    }
    <?php endif; ?>

    // Desplazamiento suave hacia las preguntas
    qSection.scrollIntoView({ behavior: 'smooth', block: 'start' });
    
    if (btnGo) {
      btnGo.innerHTML = '<span>✅ ¡Lectura completada! Responde abajo ⬇️</span>';
      btnGo.classList.remove('btn-primary');
      btnGo.classList.add('btn-success');
    }

    // Actualizar cálculo del parallax con la nueva altura de la página
    if (window._updateHabitatParallax) {
      setTimeout(window._updateHabitatParallax, 150);
      setTimeout(window._updateHabitatParallax, 500);
    }
  }
}

function toggleReading() {
  const rSection = document.getElementById('readingSection');
  const btnToggle = document.getElementById('btnToggleReading');
  
  if (rSection) {
    if (rSection.style.display === 'none') {
      rSection.style.display = 'block';
      if (btnToggle) btnToggle.innerText = '🔼 Ocultar Lectura';
      rSection.scrollIntoView({ behavior: 'smooth', block: 'start' });
    } else {
      rSection.style.display = 'none';
      if (btnToggle) btnToggle.innerText = '📖 Ver Lectura';
    }

    if (window._updateHabitatParallax) {
      setTimeout(window._updateHabitatParallax, 200);
    }
  }
}

// Reacción alegre del compañero lateral al seleccionar una respuesta
document.addEventListener('DOMContentLoaded', () => {
  document.querySelectorAll('.option-label input[type="radio"]').forEach(radio => {
    radio.addEventListener('change', () => {
      const sideImg = document.querySelector('.side-mascot-img');
      if (sideImg) {
        sideImg.classList.remove('bounce-once');
        void sideImg.offsetWidth;
        sideImg.classList.add('bounce-once');
      }
    });
  });
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
