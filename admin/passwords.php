<?php
require_once __DIR__ . '/../config/db.php';
requireAdmin();

$pdo = getDB();

// Procesar acciones POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? 'update_password';

    // 1. Actualizar contraseña existente
    if ($action === 'update_password') {
        $targetUserId = (int)($_POST['user_id'] ?? 0);
        $newPass = trim($_POST['new_password'] ?? '');

        if ($targetUserId && !empty($newPass)) {
            if (strlen($newPass) < 4) {
                $_SESSION['flash_error'] = 'La contraseña debe tener al menos 4 caracteres.';
            } else {
                $hashed = password_hash($newPass, PASSWORD_BCRYPT);
                $stmt = $pdo->prepare("UPDATE users SET password = :p WHERE id = :id");
                $stmt->execute(['p' => $hashed, 'id' => $targetUserId]);

                $stmtName = $pdo->prepare("SELECT name FROM users WHERE id = :id");
                $stmtName->execute(['id' => $targetUserId]);
                $userName = $stmtName->fetchColumn() ?: 'Usuario';

                $_SESSION['flash_success'] = "¡Contraseña actualizada con éxito para $userName!";
            }
        }
        header('Location: passwords.php');
        exit;
    }

    // 2. Crear nuevo usuario directamente en el sistema / Supabase
    if ($action === 'create_user') {
        $name = trim($_POST['name'] ?? '');
        $email = strtolower(trim($_POST['email'] ?? ''));
        $password = trim($_POST['password'] ?? '');
        $role = in_array($_POST['role'] ?? '', ['admin', 'student']) ? $_POST['role'] : 'student';
        $grade = ($role === 'student' && !empty($_POST['grade'])) ? (int)$_POST['grade'] : null;
        $avatar = trim($_POST['avatar'] ?? '🌟');

        if (empty($name) || empty($email) || empty($password)) {
            $_SESSION['flash_error'] = 'Todos los campos obligatorios deben ser completados.';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $_SESSION['flash_error'] = 'Por favor ingresa un correo electrónico válido.';
        } elseif (strlen($password) < 4) {
            $_SESSION['flash_error'] = 'La contraseña debe tener al menos 4 caracteres.';
        } else {
            // Verificar si el correo ya existe
            $stmtCheck = $pdo->prepare("SELECT id FROM users WHERE email = :email");
            $stmtCheck->execute(['email' => $email]);
            if ($stmtCheck->fetchColumn()) {
                $_SESSION['flash_error'] = "El correo '$email' ya se encuentra registrado.";
            } else {
                $hashed = password_hash($password, PASSWORD_BCRYPT);
                $stmtInsert = $pdo->prepare("
                    INSERT INTO users (name, email, password, role, grade, avatar, total_xp, streak_days, created_at)
                    VALUES (:name, :email, :password, :role, :grade, :avatar, 0, 1, NOW())
                ");
                $stmtInsert->execute([
                    'name' => $name,
                    'email' => $email,
                    'password' => $hashed,
                    'role' => $role,
                    'grade' => $grade,
                    'avatar' => $avatar
                ]);

                $_SESSION['flash_success'] = "🎉 ¡Usuario '$name' registrado exitosamente en la plataforma!";
            }
        }
        header('Location: passwords.php');
        exit;
    }

    // 3. Aprobar solicitud de acceso y crear cuenta
    if ($action === 'approve_request') {
        $reqId = (int)($_POST['request_id'] ?? 0);
        $assignedPass = trim($_POST['assigned_password'] ?? 'nivelia2026');
        if (empty($assignedPass)) $assignedPass = 'nivelia2026';

        $req = getUserAccessRequestById($reqId);
        if ($req) {
            // Verificar si el correo ya está registrado en users
            $stmtCheck = $pdo->prepare("SELECT id FROM users WHERE LOWER(email) = LOWER(:email)");
            $stmtCheck->execute(['email' => $req['email']]);
            $existingId = $stmtCheck->fetchColumn();

            if ($existingId) {
                // Si ya existe, actualizamos su clave
                $hashed = password_hash($assignedPass, PASSWORD_BCRYPT);
                $pdo->prepare("UPDATE users SET password = :p WHERE id = :id")->execute(['p' => $hashed, 'id' => $existingId]);
                updateUserAccessRequestStatus($reqId, 'approved');
                $_SESSION['flash_success'] = "La cuenta para {$req['student_name']} ya existía y fue actualizada con la nueva clave.";
            } else {
                // Crear usuario nuevo
                $hashed = password_hash($assignedPass, PASSWORD_BCRYPT);
                $stmtInsert = $pdo->prepare("
                    INSERT INTO users (name, email, password, role, grade, avatar, total_xp, streak_days, created_at)
                    VALUES (:name, :email, :password, 'student', :grade, '🌟', 0, 1, NOW())
                ");
                $stmtInsert->execute([
                    'name' => $req['student_name'],
                    'email' => $req['email'],
                    'password' => $hashed,
                    'grade' => $req['grade']
                ]);

                updateUserAccessRequestStatus($reqId, 'approved');
            }

            // Marcar notificaciones de esta solicitud como leídas
            try {
                $pdo->prepare("UPDATE admin_notifications SET is_read = 1 WHERE link LIKE '%solicitudes%'")->execute();
            } catch (Exception $e) {}

            // Generar enlace directo de WhatsApp con mensaje listo
            $cleanWa = preg_replace('/[^0-9]/', '', $req['whatsapp']);
            // Si el número venezolano empieza por 04... lo convertimos a 584...
            if (strpos($cleanWa, '04') === 0 && strlen($cleanWa) === 11) {
                $cleanWa = '58' . substr($cleanWa, 1);
            }
            $loginUrl = getBaseUrl() . 'login.php';
            $msg = "¡Hola {$req['student_name']}! 👋✨\nTu solicitud de acceso a *Nivelia* ha sido aprobada con éxito.\n\n📌 *Enlace de Acceso:* {$loginUrl}\n👤 *Usuario / Correo:* {$req['email']}\n🔒 *Contraseña:* {$assignedPass}\n🎓 *Grado:* {$req['grade']}to Grado de Primaria\n\n¡Bienvenido a tus misiones de estudio y refuerzo diario! 🎒🚀";
            $waLink = "https://wa.me/{$cleanWa}?text=" . rawurlencode($msg);

            $_SESSION['user_created_wa'] = [
                'name' => $req['student_name'],
                'email' => $req['email'],
                'password' => $assignedPass,
                'whatsapp' => $req['whatsapp'],
                'link' => $waLink
            ];

            $_SESSION['flash_success'] = "🎉 ¡Acceso creado exitosamente para {$req['student_name']}! Ahora puedes enviarle sus credenciales por WhatsApp.";
        }
        header('Location: passwords.php#solicitudes');
        exit;
    }

    // 4. Descartar solicitud de acceso
    if ($action === 'reject_request') {
        $reqId = (int)($_POST['request_id'] ?? 0);
        if ($reqId) {
            updateUserAccessRequestStatus($reqId, 'rejected');
            $_SESSION['flash_success'] = 'Solicitud descartada.';
        }
        header('Location: passwords.php#solicitudes');
        exit;
    }
}

// Consultar usuarios de forma portable (compatible con MySQL y PostgreSQL de Supabase)
$allUsers = $pdo->query("
    SELECT id, name, email, role, grade, avatar 
    FROM users 
    ORDER BY (CASE WHEN role = 'admin' THEN 1 ELSE 2 END), grade ASC, id ASC
")->fetchAll();

// Consultar solicitudes de acceso
$pendingRequests = getAllUserAccessRequests('pending');
$historyRequests = array_filter(getAllUserAccessRequests(), function($r) {
    return $r['status'] !== 'pending';
});

$approvedUserSession = $_SESSION['user_created_wa'] ?? null;
unset($_SESSION['user_created_wa']);

$pageTitle = 'Gestión de Cuentas y Accesos';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="container-narrow" style="max-width: 820px;">
  
  <div style="margin-bottom: 1.5rem; display: flex; align-items: center; justify-content: space-between;">
    <a href="index.php" class="btn btn-secondary btn-sm">⬅️ Volver al Panel</a>
    <a href="#solicitudes" class="btn btn-sm <?= count($pendingRequests) > 0 ? 'btn-warning' : 'btn-secondary' ?>" style="font-weight: 700;">
      <span>📋 Solicitudes</span>
      <?php if (count($pendingRequests) > 0): ?>
        <span style="background: #ef4444; color: #fff; padding: 2px 7px; border-radius: 999px; font-size: 0.75rem; margin-left: 0.35rem;">
          <?= count($pendingRequests) ?>
        </span>
      <?php endif; ?>
    </a>
  </div>

  <!-- Alerta de Usuario Aprobado Recientemente con Botón de WhatsApp -->
  <?php if ($approvedUserSession): ?>
    <div class="card" style="margin-bottom: 1.5rem; border: 2px solid #10b981; background: #ecfdf5; box-shadow: 0 10px 25px rgba(16, 185, 129, 0.15);">
      <div style="display: flex; align-items: flex-start; justify-content: space-between; gap: 1rem; flex-wrap: wrap;">
        <div>
          <h3 style="margin: 0 0 0.4rem 0; color: #065f46; font-size: 1.15rem; display: flex; align-items: center; gap: 0.5rem;">
            <span>🎉</span>
            <span>¡Cuenta creada para <?= htmlspecialchars($approvedUserSession['name']) ?>!</span>
          </h3>
          <p style="margin: 0 0 0.5rem 0; font-size: 0.88rem; color: #047857;">
            Usuario: <strong><?= htmlspecialchars($approvedUserSession['email']) ?></strong> &bull; 
            Clave: <strong><?= htmlspecialchars($approvedUserSession['password']) ?></strong> &bull; 
            WhatsApp: <strong><?= htmlspecialchars($approvedUserSession['whatsapp']) ?></strong>
          </p>
        </div>
        <a href="<?= $approvedUserSession['link'] ?>" target="_blank" rel="noopener noreferrer" class="btn btn-primary" style="background: #25d366; border-color: #22c55e; color: #fff; font-weight: 800; box-shadow: 0 4px 14px rgba(37, 211, 102, 0.4);">
          <span>💬 Enviar Credenciales por WhatsApp</span>
        </a>
      </div>
    </div>
  <?php endif; ?>

  <div class="card" style="margin-bottom: 1.5rem;">
    <div style="display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 0.75rem;">
      <div style="display: flex; align-items: center; gap: 0.75rem;">
        <span style="font-size: 2rem;">🔑</span>
        <div>
          <h1 style="font-size: 1.45rem; margin: 0 0 0.25rem 0;">Gestión de Cuentas y Accesos</h1>
          <p style="font-size: 0.9rem; color: var(--text-secondary); margin: 0;">
            Administra usuarios activos, crea cuentas nuevas y aprueba solicitudes de acceso.
          </p>
        </div>
      </div>
      <button type="button" class="btn btn-primary btn-sm" onclick="toggleNewUserForm()">
        <span>➕ Crear Usuario Manual</span>
      </button>
    </div>
  </div>

  <!-- Formulario Desplegable para Registrar Nuevo Usuario -->
  <div id="newUserCard" class="card" style="display: none; margin-bottom: 1.5rem; border: 2px solid var(--primary-light); background: #fdfefe;">
    <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 1rem; border-bottom: 1px solid var(--border); padding-bottom: 0.75rem;">
      <h3 style="font-size: 1.15rem; margin: 0; color: var(--primary);">
        👤 Registrar Nuevo Estudiante o Administrador
      </h3>
      <button type="button" onclick="toggleNewUserForm()" style="background: none; border: none; font-size: 1.1rem; cursor: pointer; color: var(--text-muted);">&times;</button>
    </div>

    <form method="POST" action="passwords.php">
      <input type="hidden" name="action" value="create_user">

      <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 1rem; margin-bottom: 1rem;">
        <div>
          <label class="form-label" for="new_name">Nombre Completo *</label>
          <input type="text" id="new_name" name="name" class="form-control" placeholder="Ej: Sofía Valentina" required>
        </div>
        <div>
          <label class="form-label" for="new_email">Correo Electrónico *</label>
          <input type="email" id="new_email" name="email" class="form-control" placeholder="usuario@correo.com" required>
        </div>
      </div>

      <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 1rem; margin-bottom: 1rem;">
        <div>
          <label class="form-label" for="new_pass">Contraseña *</label>
          <input type="password" id="new_pass" name="password" class="form-control" placeholder="••••••••" required>
        </div>
        <div>
          <label class="form-label" for="new_role">Rol en el Sistema *</label>
          <select id="new_role" name="role" class="form-control" onchange="toggleGradeField(this.value)">
            <option value="student">Estudiante (Alumno)</option>
            <option value="admin">Administrador (Papá / Tutor)</option>
          </select>
        </div>
        <div id="gradeFieldContainer">
          <label class="form-label" for="new_grade">Grado Escolar</label>
          <select id="new_grade" name="grade" class="form-control">
            <option value="1">1er Grado</option>
            <option value="2">2do Grado</option>
            <option value="3">3er Grado</option>
            <option value="4">4to Grado</option>
            <option value="5" selected>5to Grado de Primaria</option>
            <option value="6">6to Grado de Primaria</option>
          </select>
        </div>
        <div>
          <label class="form-label" for="new_avatar">Avatar (Emoji)</label>
          <select id="new_avatar" name="avatar" class="form-control">
            <option value="🌟">🌟 Estrella</option>
            <option value="🚀">🚀 Cohete</option>
            <option value="⚡">⚡ Relámpago</option>
            <option value="🎒">🎒 Mochila</option>
            <option value="🦉">🦉 Búho</option>
            <option value="🐬">🐬 Tonina</option>
            <option value="🦜">🦜 Guacamaya</option>
            <option value="👨‍🏫">👨‍🏫 Profesor</option>
          </select>
        </div>
      </div>

      <div style="display: flex; justify-content: flex-end; gap: 0.5rem; margin-top: 1.25rem;">
        <button type="button" class="btn btn-secondary btn-sm" onclick="toggleNewUserForm()">Cancelar</button>
        <button type="submit" class="btn btn-primary btn-sm">💾 Guardar Nuevo Usuario en Supabase</button>
      </div>
    </form>
  </div>

  <!-- SECCIÓN DE SOLICITUDES DE ACCESO -->
  <div id="solicitudes" class="card" style="margin-bottom: 2rem; border: 2px solid <?= count($pendingRequests) > 0 ? '#f59e0b' : 'var(--border)' ?>;">
    <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 1.25rem; flex-wrap: wrap; gap: 0.5rem;">
      <div style="display: flex; align-items: center; gap: 0.5rem;">
        <span style="font-size: 1.5rem;">📝</span>
        <h2 style="font-size: 1.25rem; margin: 0;">Solicitudes de Acceso de Nuevos Usuarios</h2>
      </div>
      <span class="badge" style="background: <?= count($pendingRequests) > 0 ? '#fef3c7; color: #b45309; border: 1px solid #fde68a;' : '#f1f5f9; color: #64748b;' ?> font-weight: 700; padding: 0.35rem 0.75rem; border-radius: 999px;">
        <?= count($pendingRequests) ?> <?= count($pendingRequests) === 1 ? 'pendiente' : 'pendientes' ?>
      </span>
    </div>

    <?php if (empty($pendingRequests)): ?>
      <div style="text-align: center; padding: 2rem 1rem; color: var(--text-muted); background: #f8fafc; border-radius: var(--radius-md); border: 1.5px dashed #e2e8f0;">
        <span style="font-size: 2rem; display: block; margin-bottom: 0.5rem;">✨</span>
        <p style="margin: 0; font-size: 0.95rem; font-weight: 600;">No hay solicitudes pendientes en este momento.</p>
        <p style="margin: 0.25rem 0 0 0; font-size: 0.83rem;">Cuando un nuevo usuario llene el formulario en la pantalla de inicio, aparecerá aquí para que le otorgues acceso.</p>
      </div>
    <?php else: ?>
      <div style="display: flex; flex-direction: column; gap: 1rem;">
        <?php foreach ($pendingRequests as $req): ?>
          <?php
            $cleanWa = preg_replace('/[^0-9]/', '', $req['whatsapp']);
            if (strpos($cleanWa, '04') === 0 && strlen($cleanWa) === 11) {
                $cleanWa = '58' . substr($cleanWa, 1);
            }
            $chatWaLink = "https://wa.me/{$cleanWa}?text=" . rawurlencode("¡Hola {$req['student_name']}! Recibimos tu solicitud para Nivelia.");
          ?>
          <div style="background: #fff; border: 1.5px solid #fde68a; border-left: 5px solid #f59e0b; border-radius: 12px; padding: 1.15rem; box-shadow: 0 4px 12px rgba(245, 158, 11, 0.08);">
            <div style="display: flex; align-items: flex-start; justify-content: space-between; gap: 1rem; flex-wrap: wrap; margin-bottom: 0.85rem;">
              <div>
                <h3 style="margin: 0 0 0.25rem 0; font-size: 1.1rem; color: #1e293b;">
                  👤 <?= htmlspecialchars($req['student_name']) ?>
                  <span class="badge" style="background: #e0e7ff; color: #4338ca; font-size: 0.75rem; margin-left: 0.5rem;">
                    <?= $req['grade'] ?>to Grado
                  </span>
                </h3>
                <div style="font-size: 0.85rem; color: var(--text-secondary); display: flex; flex-wrap: wrap; gap: 0.85rem; margin-top: 0.35rem;">
                  <span>📧 <a href="mailto:<?= htmlspecialchars($req['email']) ?>" style="color: var(--primary); font-weight: 600;"><?= htmlspecialchars($req['email']) ?></a></span>
                  <span>💬 WhatsApp: <strong><?= htmlspecialchars($req['whatsapp']) ?></strong></span>
                  <span style="color: var(--text-muted);">🕒 Solicitado: <?= date('d/m/Y h:i A', strtotime($req['created_at'])) ?></span>
                </div>
                <?php if (!empty($req['message'])): ?>
                  <div style="margin-top: 0.5rem; background: #f8fafc; padding: 0.5rem 0.75rem; border-radius: 8px; font-size: 0.84rem; color: #475569; font-style: italic;">
                    "<?= htmlspecialchars($req['message']) ?>"
                  </div>
                <?php endif; ?>
              </div>

              <div style="display: flex; gap: 0.5rem; align-items: center;">
                <a href="<?= $chatWaLink ?>" target="_blank" rel="noopener noreferrer" class="btn btn-sm btn-secondary" style="border-color: #25d366; color: #15803d;" title="Abrir chat de WhatsApp">
                  <span>💬 WhatsApp</span>
                </a>
              </div>
            </div>

            <!-- Formulario de Aprobación Inmediata -->
            <form method="POST" action="passwords.php" style="background: #fffbeb; border: 1px solid #fef3c7; border-radius: 8px; padding: 0.75rem 1rem; display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 0.75rem;">
              <input type="hidden" name="action" value="approve_request">
              <input type="hidden" name="request_id" value="<?= $req['id'] ?>">

              <div style="display: flex; align-items: center; gap: 0.5rem; flex: 1; min-width: 240px;">
                <label style="font-size: 0.82rem; font-weight: 700; color: #92400e; margin: 0; white-space: nowrap;">
                  🔒 Clave a Asignar:
                </label>
                <input 
                  type="text" 
                  name="assigned_password" 
                  value="nivelia2026" 
                  class="form-control" 
                  style="font-size: 0.85rem; padding: 0.35rem 0.65rem; background: #fff; max-width: 180px;" 
                  required>
              </div>

              <div style="display: flex; gap: 0.5rem;">
                <button type="submit" class="btn btn-sm btn-primary" style="background: #10b981; border-color: #059669; font-weight: 700;">
                  <span>⚡ Aprobar y Crear Cuenta</span>
                </button>
              </div>
            </form>

            <form method="POST" action="passwords.php" style="margin-top: 0.5rem; text-align: right;" onsubmit="return confirm('¿Seguro que deseas descartar esta solicitud?');">
              <input type="hidden" name="action" value="reject_request">
              <input type="hidden" name="request_id" value="<?= $req['id'] ?>">
              <button type="submit" style="background: none; border: none; color: #94a3b8; font-size: 0.78rem; cursor: pointer; text-decoration: underline;">
                Descartar solicitud
              </button>
            </form>
          </div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>

    <!-- Historial de Solicitudes Atendidas -->
    <?php if (!empty($historyRequests)): ?>
      <div style="margin-top: 1.5rem; border-top: 1px dashed var(--border); padding-top: 1rem;">
        <details>
          <summary style="font-size: 0.85rem; font-weight: 700; color: var(--text-muted); cursor: pointer;">
            📁 Ver historial de solicitudes atendidas (<?= count($historyRequests) ?>)
          </summary>
          <div style="margin-top: 0.75rem; display: flex; flex-direction: column; gap: 0.5rem;">
            <?php foreach ($historyRequests as $h): ?>
              <div style="display: flex; justify-content: space-between; align-items: center; padding: 0.5rem 0.75rem; background: #f8fafc; border-radius: 8px; font-size: 0.82rem;">
                <div>
                  <strong><?= htmlspecialchars($h['student_name']) ?></strong> (<?= $h['grade'] ?>to Grado) &bull; <?= htmlspecialchars($h['email']) ?>
                </div>
                <div>
                  <span class="badge" style="background: <?= $h['status'] === 'approved' ? '#d1fae5; color: #065f46;' : '#fee2e2; color: #991b1b;' ?>">
                    <?= $h['status'] === 'approved' ? '✅ Aprobada' : '❌ Descartada' ?>
                  </span>
                </div>
              </div>
            <?php endforeach; ?>
          </div>
        </details>
      </div>
    <?php endif; ?>
  </div>

  <!-- LISTA DE USUARIOS ACTIVOS EN SUPABASE / SISTEMA -->
  <div style="margin-bottom: 1rem; display: flex; align-items: center; justify-content: space-between;">
    <h2 style="font-size: 1.25rem; margin: 0;">👥 Cuentas de Acceso Activas (<?= count($allUsers) ?>)</h2>
  </div>

  <div style="display: flex; flex-direction: column; gap: 1.25rem;">
    <?php foreach ($allUsers as $u): ?>
      <div class="card" style="border: 2px solid var(--border);">
        <div style="display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 0.5rem; margin-bottom: 1rem;">
          <div style="display: flex; align-items: center; gap: 0.75rem;">
            <span style="font-size: 2.2rem;"><?= $u['avatar'] ?></span>
            <div>
              <h3 style="font-size: 1.15rem; margin: 0 0 0.2rem 0;"><?= htmlspecialchars($u['name']) ?></h3>
              <div style="font-size: 0.85rem; color: var(--text-secondary);">
                📧 Correo: <strong><?= htmlspecialchars($u['email']) ?></strong>
              </div>
            </div>
          </div>
          <span class="badge-subject <?= $u['role'] === 'admin' ? 'badge-algebra' : ($u['grade'] == 5 ? 'badge-matematicas' : 'badge-lectura') ?>">
            <?= $u['role'] === 'admin' ? 'Administrador' : $u['grade'] . 'to Grado' ?>
          </span>
        </div>

        <form method="POST" action="passwords.php" style="background: #f8fafc; padding: 1rem; border-radius: var(--radius-md); border: 1px solid var(--border);">
          <input type="hidden" name="action" value="update_password">
          <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
          
          <label class="form-label" style="font-size: 0.85rem;">Asignar Nueva Contraseña:</label>
          <div style="display: flex; gap: 0.75rem;">
            <input 
              type="text" 
              name="new_password" 
              class="form-control" 
              placeholder="Escribe la nueva contraseña..." 
              required 
              style="background: #ffffff;">
            
            <button type="submit" class="btn btn-primary btn-sm" style="white-space: nowrap;">
              Guardar Clave
            </button>
          </div>
        </form>
      </div>
    <?php endforeach; ?>
  </div>

</div>

<script>
function toggleNewUserForm() {
  const card = document.getElementById('newUserCard');
  if (card.style.display === 'none' || card.style.display === '') {
    card.style.display = 'block';
    card.scrollIntoView({ behavior: 'smooth' });
  } else {
    card.style.display = 'none';
  }
}

function toggleGradeField(role) {
  const container = document.getElementById('gradeFieldContainer');
  if (role === 'admin') {
    container.style.display = 'none';
  } else {
    container.style.display = 'block';
  }
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
