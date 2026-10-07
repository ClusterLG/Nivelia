<?php
require_once __DIR__ . '/../config/db.php';
requireAdmin();

$pdo = getDB();

// Procesar Calificación / Asignación de Estrellas
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_evaluation'])) {
    $subId = (int)$_POST['submission_id'];
    $stars = (int)($_POST['parent_stars'] ?? 5);
    $feedback = trim($_POST['parent_feedback'] ?? '');
    $bonusXp = (int)($_POST['bonus_xp'] ?? 0);

    if ($subId) {
        $stmtUpdate = $pdo->prepare("
            UPDATE student_submissions 
            SET parent_stars = :stars, parent_feedback = :feedback, status = 'reviewed', graded_at = NOW()
            WHERE id = :id
        ");
        $stmtUpdate->execute([
            'stars' => $stars,
            'feedback' => $feedback,
            'id' => $subId
        ]);

        // Otorgar XP extra si el papá le dio bonus
        if ($bonusXp > 0) {
            $userId = $pdo->query("SELECT user_id FROM student_submissions WHERE id = $subId")->fetchColumn();
            if ($userId) {
                $pdo->prepare("UPDATE users SET total_xp = total_xp + :bxp WHERE id = :uid")->execute(['bxp' => $bonusXp, 'uid' => $userId]);
            }
        }

        // Actualizar archivo físico en disco (carpeta respuestas/[Alumno]/)
        require_once __DIR__ . '/../config/records.php';
        recordSubmissionToDisk($subId, $pdo);

        $_SESSION['flash_success'] = '¡Calificación y mensaje motivador enviados con éxito a tu hijo (y archivados en respuestas/)!';
        header("Location: grade.php?submission_id=$subId");
        exit;
    }
}

// Filtro de estudiante
$filterStudent = (int)($_GET['student_id'] ?? 0);
$selectedSubId = (int)($_GET['submission_id'] ?? 0);

// Cargar lista de estudiantes
$students = $pdo->query("SELECT id, name, grade, avatar FROM users WHERE role = 'student' ORDER BY grade ASC")->fetchAll();

// Cargar entregas
$querySubs = "
    SELECT sub.*, u.name as student_name, u.grade as student_grade, u.avatar as student_avatar,
           a.title as activity_title, a.day_of_week, a.xp_reward,
           s.name as subject_name, s.slug as subject_slug, s.icon as subject_icon, s.color as subject_color
    FROM student_submissions sub
    JOIN users u ON sub.user_id = u.id
    JOIN activities a ON sub.activity_id = a.id
    JOIN subjects s ON a.subject_id = s.id
";
if ($filterStudent > 0) {
    $querySubs .= " WHERE sub.user_id = $filterStudent";
}
$querySubs .= " ORDER BY sub.submitted_at DESC";
$submissions = $pdo->query($querySubs)->fetchAll();

// Si hay una entrega seleccionada para revisar en detalle
$activeSubmission = null;
$submissionAnswers = [];
if ($selectedSubId > 0) {
    $stmtActSub = $pdo->prepare("
        SELECT sub.*, u.name as student_name, u.grade as student_grade, u.avatar as student_avatar,
               a.title as activity_title, a.summary as activity_summary, a.reading_content, a.day_of_week, a.xp_reward,
               s.name as subject_name, s.slug as subject_slug, s.icon as subject_icon
        FROM student_submissions sub
        JOIN users u ON sub.user_id = u.id
        JOIN activities a ON sub.activity_id = a.id
        JOIN subjects s ON a.subject_id = s.id
        WHERE sub.id = :id
    ");
    $stmtActSub->execute(['id' => $selectedSubId]);
    $activeSubmission = $stmtActSub->fetch();

    if ($activeSubmission) {
        $stmtAns = $pdo->prepare("
            SELECT sa.*, q.question_text, q.question_type, q.correct_answer, q.explanation, q.points as max_points
            FROM submission_answers sa
            JOIN activity_questions q ON sa.question_id = q.id
            WHERE sa.submission_id = :sid
            ORDER BY q.id ASC
        ");
        $stmtAns->execute(['sid' => $selectedSubId]);
        $submissionAnswers = $stmtAns->fetchAll();
    }
}

$pageTitle = 'Evaluación y Calificaciones';
require_once __DIR__ . '/../includes/header.php';
?>

<div style="margin-bottom: 2rem;">
  <div style="display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 1rem; margin-bottom: 1.5rem;">
    <div>
      <h1 style="font-size: 1.85rem;">⭐ Calificaciones y Retroalimentación</h1>
      <p style="color: var(--text-secondary);">
        Revisa las respuestas de Alexander y Dimas, asígnales estrellas de recompensa y déjales comentarios formativos.
      </p>
    </div>

    <div style="display: flex; gap: 0.5rem;">
      <a href="grade.php" class="btn btn-sm <?= $filterStudent === 0 ? 'btn-primary' : 'btn-secondary' ?>">Todas</a>
      <?php foreach ($students as $stu): ?>
        <a href="grade.php?student_id=<?= $stu['id'] ?>" class="btn btn-sm <?= $filterStudent === $stu['id'] ? 'btn-primary' : 'btn-secondary' ?>">
          <?= $stu['avatar'] ?> <?= htmlspecialchars($stu['name']) ?>
        </a>
      <?php endforeach; ?>
    </div>
  </div>

  <div style="display: grid; grid-template-columns: <?= $activeSubmission ? '380px 1fr' : '1fr' ?>; gap: 1.5rem; align-items: start;">
    
    <!-- COLUMNA 1: LISTADO DE ENTREGAS -->
    <div>
      <h2 style="font-size: 1.15rem; margin-bottom: 0.75rem;">Entregas (<?= count($submissions) ?>)</h2>

      <?php if (empty($submissions)): ?>
        <div class="card" style="text-align: center; padding: 2rem;">
          <p style="color: var(--text-muted);">No hay actividades entregadas todavía.</p>
        </div>
      <?php else: ?>
        <div style="display: flex; flex-direction: column; gap: 0.75rem;">
          <?php foreach ($submissions as $sub): 
            $isSelected = ($activeSubmission && $activeSubmission['id'] === $sub['id']);
          ?>
            <a href="grade.php?submission_id=<?= $sub['id'] ?><?= $filterStudent ? '&student_id=' . $filterStudent : '' ?>" 
               class="card card-interactive" 
               style="padding: 1rem; text-decoration: none; border-color: <?= $isSelected ? 'var(--primary)' : 'var(--border)' ?>; background: <?= $isSelected ? '#eef2ff' : 'var(--bg-card)' ?>;">
              
              <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 0.4rem;">
                <div style="display: flex; align-items: center; gap: 0.4rem; font-weight: 700; font-size: 0.95rem; color: var(--text-primary);">
                  <span><?= $sub['student_avatar'] ?></span>
                  <span><?= htmlspecialchars($sub['student_name']) ?></span>
                </div>
                <span style="font-size: 0.8rem; font-weight: 700; color: #059669;">
                  <?= $sub['score'] ?>/<?= $sub['max_score'] ?> pts
                </span>
              </div>

              <div style="font-size: 0.88rem; color: var(--text-secondary); margin-bottom: 0.5rem; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
                <?= $sub['subject_icon'] ?> <?= htmlspecialchars($sub['activity_title']) ?>
              </div>

              <div style="display: flex; align-items: center; justify-content: space-between; font-size: 0.78rem;">
                <span style="color: var(--text-muted); text-transform: capitalize;">
                  <?= getDayNameSpanish($sub['day_of_week']) ?> • <?= date('d/m H:i', strtotime($sub['submitted_at'])) ?>
                </span>
                <span>
                  <?php if ($sub['parent_stars'] > 0): ?>
                    <?= str_repeat('⭐', $sub['parent_stars']) ?>
                  <?php else: ?>
                    <span style="background: #fef2f2; color: #dc2626; padding: 0.15rem 0.45rem; border-radius: var(--radius-full); font-weight: 700;">
                      Por evaluar
                    </span>
                  <?php endif; ?>
                </span>
              </div>
            </a>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </div>

    <!-- COLUMNA 2: DETALLE Y EVALUACIÓN -->
    <?php if ($activeSubmission): ?>
      <div class="card" style="box-shadow: var(--shadow-lg);">
        <div style="display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 0.5rem; margin-bottom: 1rem; border-bottom: 2px solid var(--border); padding-bottom: 0.75rem;">
          <div>
            <div style="display: flex; align-items: center; gap: 0.5rem; margin-bottom: 0.25rem;">
              <span style="font-size: 1.5rem;"><?= $activeSubmission['student_avatar'] ?></span>
              <h2 style="font-size: 1.35rem;"><?= htmlspecialchars($activeSubmission['student_name']) ?> (<?= $activeSubmission['student_grade'] ?>to Grado)</h2>
            </div>
            <div style="font-size: 0.95rem; color: var(--text-secondary);">
              <?= $activeSubmission['subject_icon'] ?> <?= htmlspecialchars($activeSubmission['activity_title']) ?>
            </div>
          </div>

          <div style="text-align: right;">
            <div style="font-size: 1.25rem; font-weight: 800; color: #059669;">
              <?= $activeSubmission['score'] ?> / <?= $activeSubmission['max_score'] ?> pts
            </div>
            <div style="font-size: 0.8rem; color: var(--text-muted);">
              Entregado el <?= date('d/m/Y h:i A', strtotime($activeSubmission['submitted_at'])) ?>
            </div>
          </div>
        </div>

        <!-- Respuestas del estudiante -->
        <h3 style="font-size: 1.1rem; margin-bottom: 1rem;">Respuestas del Estudiante:</h3>
        
        <div style="display: flex; flex-direction: column; gap: 1rem; margin-bottom: 2rem;">
          <?php foreach ($submissionAnswers as $idx => $ans): ?>
            <div style="background: #f8fafc; border: 1.5px solid var(--border); border-radius: var(--radius-md); padding: 1rem;">
              <div style="display: flex; align-items: flex-start; justify-content: space-between; gap: 0.5rem; margin-bottom: 0.5rem;">
                <div style="font-weight: 700; font-size: 0.95rem; color: var(--text-primary);">
                  Pregunta <?= $idx + 1 ?>: <?= htmlspecialchars($ans['question_text']) ?>
                </div>
                <span style="font-size: 0.8rem; font-weight: 700; color: #059669; white-space: nowrap;">
                  <?= $ans['points_awarded'] ?>/<?= $ans['max_points'] ?> pts
                </span>
              </div>

              <div style="background: #ffffff; padding: 0.75rem; border-radius: var(--radius-sm); border: 1px solid #e2e8f0; margin-bottom: 0.4rem;">
                <div style="font-size: 0.78rem; text-transform: uppercase; color: var(--text-muted); font-weight: 700;">Respuesta de tu hijo:</div>
                <div style="font-size: 0.95rem; font-weight: 600; color: var(--text-primary); margin-top: 0.2rem;">
                  <?= nl2br(htmlspecialchars($ans['answer_text'])) ?>
                </div>
              </div>

              <?php if (!empty($ans['correct_answer'])): ?>
                <div style="font-size: 0.82rem; color: var(--text-secondary);">
                  <strong>Respuesta oficial esperada:</strong> <?= htmlspecialchars($ans['correct_answer']) ?>
                </div>
              <?php endif; ?>
            </div>
          <?php endforeach; ?>
        </div>

        <!-- FORMULARIO DE EVALUACIÓN DE PAPÁ -->
        <div style="background: #fffbeb; border: 2px solid #fde68a; border-radius: var(--radius-lg); padding: 1.5rem;">
          <h3 style="color: #92400e; font-size: 1.2rem; margin-bottom: 0.5rem;">⭐ Calificar y Enviar Mensaje a tu Hijo</h3>
          <p style="font-size: 0.9rem; color: #b45309; margin-bottom: 1.25rem;">
            Tu hijo verá esta calificación y tus palabras de aliento directamente en su pantalla.
          </p>

          <form method="POST" action="grade.php?submission_id=<?= $activeSubmission['id'] ?>">
            <input type="hidden" name="save_evaluation" value="1">
            <input type="hidden" name="submission_id" value="<?= $activeSubmission['id'] ?>">

            <div class="form-group">
              <label class="form-label" style="color: #92400e;">Estrellas de Desempeño:</label>
              <div style="display: flex; gap: 1rem; align-items: center; flex-wrap: wrap;">
                <?php for ($s = 5; $s >= 1; $s--): ?>
                  <label style="display: flex; align-items: center; gap: 0.35rem; cursor: pointer; background: #ffffff; padding: 0.4rem 0.85rem; border-radius: var(--radius-md); border: 1.5px solid #fde68a;">
                    <input type="radio" name="parent_stars" value="<?= $s ?>" <?= ($activeSubmission['parent_stars'] == $s || ($activeSubmission['parent_stars'] == 0 && $s == 5)) ? 'checked' : '' ?>>
                    <span style="font-size: 1.1rem;"><?= str_repeat('⭐', $s) ?></span>
                  </label>
                <?php endfor; ?>
              </div>
            </div>

            <div class="form-group">
              <label class="form-label" style="color: #92400e;">Puntos Extra de Motivación (Bonus XP):</label>
              <input type="number" name="bonus_xp" class="form-control" value="0" min="0" max="100" style="max-width: 140px;">
              <small style="color: #b45309;">(Opcional: puedes sumar puntos XP extra a su cuenta)</small>
            </div>

            <div class="form-group">
              <label class="form-label" style="color: #92400e;">Mensaje de Papá (Consejo, Felicitación o Corrección):</label>
              <textarea name="parent_feedback" class="input-textarea" style="min-height: 100px; background: #ffffff;" placeholder="Ej: ¡Excelente razonamiento, hijo! Me encantó cómo explicaste el protocolo de la mochila de 72 horas."><?= htmlspecialchars($activeSubmission['parent_feedback'] ?? '') ?></textarea>
            </div>

            <button type="submit" class="btn btn-primary btn-block btn-lg" style="background: linear-gradient(135deg, #d97706, #b45309); border: none;">
              <span>⭐</span>
              <span>Guardar Calificación y Enviar a mi Hijo</span>
            </button>
          </form>
        </div>

      </div>
    <?php endif; ?>

  </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
