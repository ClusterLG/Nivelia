<?php
require_once __DIR__ . '/config/db.php';

// Si ya tiene una sesión activa, redirigir directo a su espacio
if (isLoggedIn()) {
    $user = currentUser();
    if ($user['role'] === 'admin') {
        header('Location: admin/index.php');
    } else {
        header('Location: student/dashboard.php');
    }
    exit;
}

$error = '';
$requestSuccess = '';
$pdo = getDB();

// Procesar formulario
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? 'login';

    // 1. Petición de nuevo acceso al Administrador
    if ($action === 'request_access') {
        $reqName = trim($_POST['req_name'] ?? '');
        $reqEmail = strtolower(trim($_POST['req_email'] ?? ''));
        $reqWhatsapp = trim($_POST['req_whatsapp'] ?? '');
        $reqGrade = (int)($_POST['req_grade'] ?? 0);
        $reqNotes = trim($_POST['req_notes'] ?? '');

        if (empty($reqName) || empty($reqEmail) || empty($reqWhatsapp) || $reqGrade < 1 || $reqGrade > 6) {
            $error = 'Para solicitar acceso debes completar: Nombre completo, Correo, WhatsApp y Grado escolar (1ro a 6to).';
        } elseif (!filter_var($reqEmail, FILTER_VALIDATE_EMAIL)) {
            $error = 'Por favor ingresa un correo electrónico válido para tu solicitud.';
        } else {
            // Verificar si el correo ya tiene cuenta activa
            $stmtCheck = $pdo->prepare("SELECT id FROM users WHERE LOWER(email) = LOWER(:e)");
            $stmtCheck->execute(['e' => $reqEmail]);
            if ($stmtCheck->fetchColumn()) {
                $error = 'Ya existe una cuenta registrada con el correo ' . htmlspecialchars($reqEmail) . '. Si olvidaste tu clave, consulta con el Administrador.';
            } else {
                $created = createUserAccessRequest($reqName, '', $reqEmail, $reqWhatsapp, $reqGrade, $reqNotes);
                if ($created) {
                    createAdminNotification(
                        1,
                        'request',
                        '📝 Nueva Solicitud de Acceso: ' . $reqName,
                        "$reqName solicita crear cuenta para {$reqGrade}to Grado. WhatsApp: $reqWhatsapp | Correo: $reqEmail",
                        'passwords.php#solicitudes'
                    );
                    $requestSuccess = "¡Tu petición ha sido enviada con éxito, " . htmlspecialchars($reqName) . "! 🎉 El administrador revisará tus datos y te contactará por WhatsApp ($reqWhatsapp) o correo ($reqEmail) para entregarte tu acceso.";
                } else {
                    $error = 'No se pudo registrar la solicitud en este momento. Intenta de nuevo.';
                }
            }
        }
    } 
    // 2. Inicio de sesión con Usuario o Correo y Contraseña
    else {
        $loginInput = trim($_POST['email'] ?? $_POST['login_input'] ?? '');
        $password = $_POST['password'] ?? '';

        if (empty($loginInput) || empty($password)) {
            $error = 'Por favor ingresa tu usuario o correo electrónico y tu contraseña.';
        } else {
            $user = findUserForLogin($loginInput);

            if ($user && password_verify($password, $user['password'])) {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['role'] = $user['role'];
                $_SESSION['user'] = $user;

                // Registrar log de inicio de sesión y auditoría de tiempo
                logSessionLogin($user['id']);

                // Notificar al admin si un alumno ha ingresado a estudiar
                if ($user['role'] === 'student') {
                    createAdminNotification(
                        $user['id'],
                        'session',
                        '🟢 ' . $user['name'] . ' inició sesión',
                        $user['name'] . ' (' . $user['grade'] . 'to Grado) ha ingresado a la plataforma para sus misiones de hoy.',
                        'session_logs.php'
                    );
                }

                $_SESSION['flash_success'] = "¡Bienvenido, " . $user['name'] . "! 🎉";
                
                if ($user['role'] === 'admin') {
                    header('Location: admin/index.php');
                } else {
                    header('Location: student/dashboard.php');
                }
                exit;
            } else {
                $error = 'Usuario o contraseña incorrectos. Verifica tus credenciales e intenta de nuevo.';
            }
        }
    }
}

$pageTitle = 'Comenzar Sesión';
require_once __DIR__ . '/includes/header.php';
?>

<style>
  html, body {
    width: 100%;
    max-width: 100%;
    margin: 0;
    padding: 0;
    overflow-x: hidden;
  }
  body {
    background: linear-gradient(rgba(240, 249, 255, 0.15), rgba(240, 249, 255, 0.35)), 
                url('assets/images/nivelia_paisaje.jpg') no-repeat center center fixed !important;
    background-size: cover !important;
    min-height: 100vh;
    display: flex;
    flex-direction: column;
  }
  .navbar {
    width: 100% !important;
    background: rgba(255, 255, 255, 0.90) !important;
    backdrop-filter: blur(12px) !important;
    -webkit-backdrop-filter: blur(12px) !important;
    border-bottom: 1px solid rgba(255, 255, 255, 0.6) !important;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.05) !important;
  }
  .navbar-inner {
    width: 100% !important;
    max-width: 100% !important;
    padding-left: clamp(0.75rem, 3vw, 2.5rem) !important;
    padding-right: clamp(0.75rem, 3vw, 2.5rem) !important;
  }
  .footer {
    width: 100% !important;
    background: rgba(255, 255, 255, 0.92) !important;
    backdrop-filter: blur(12px) !important;
    -webkit-backdrop-filter: blur(12px) !important;
    border-top: 1px solid rgba(255, 255, 255, 0.6) !important;
  }
  .footer .container {
    width: 100% !important;
    max-width: 100% !important;
    padding-left: clamp(0.75rem, 3vw, 2.5rem) !important;
    padding-right: clamp(0.75rem, 3vw, 2.5rem) !important;
  }

  /* Ajuste automático fluido al ancho de cualquier dispositivo */
  main.container {
    width: 100% !important;
    max-width: 100% !important;
    padding-left: clamp(0.75rem, 3vw, 2.5rem) !important;
    padding-right: clamp(0.75rem, 3vw, 2.5rem) !important;
    padding-top: clamp(0.5rem, 1.5vw, 1.5rem) !important;
    padding-bottom: clamp(1rem, 2vw, 2.5rem) !important;
    display: flex;
    flex-direction: column;
    justify-content: center;
    align-items: center;
    flex: 1;
  }
  
  .login-stage-container {
    width: 100%;
    max-width: 1320px;
    margin: 0 auto;
    display: grid;
    grid-template-columns: minmax(0, 1.05fr) minmax(0, 0.95fr);
    gap: clamp(1rem, 2vw, 2rem);
    align-items: stretch;
  }

  .hero-showcase {
    background: rgba(255, 255, 255, 0.94);
    backdrop-filter: blur(16px);
    -webkit-backdrop-filter: blur(16px);
    border-radius: 24px;
    border: 2px solid rgba(255, 255, 255, 0.85);
    box-shadow: 0 15px 35px rgba(15, 23, 42, 0.14);
    overflow: hidden;
    display: flex;
    flex-direction: column;
    justify-content: space-between;
    transition: transform 0.3s ease, box-shadow 0.3s ease;
  }
  
  .hero-showcase:hover {
    box-shadow: 0 20px 40px rgba(15, 23, 42, 0.18);
  }

  .hero-img-wrap {
    position: relative;
    width: 100%;
    height: 310px;
    overflow: hidden;
    border-bottom: 2px solid rgba(226, 232, 240, 0.8);
    background: #0f172a;
  }

  .carousel-slide {
    position: absolute;
    inset: 0;
    opacity: 0;
    visibility: hidden;
    transition: opacity 0.4s ease, transform 0.4s ease;
    transform: scale(0.98);
  }

  .carousel-slide.active {
    opacity: 1;
    visibility: visible;
    transform: scale(1);
  }

  .slide-full-photo {
    width: 100%;
    height: 100%;
    object-fit: cover;
    display: block;
    transition: transform 0.5s ease;
  }

  .hero-showcase:hover .slide-full-photo {
    transform: scale(1.02);
  }

  /* Escenario para tutor individual */
  .single-mascot-scene {
    position: relative;
    width: 100%;
    height: 100%;
    background: radial-gradient(circle at 50% 65%, var(--mascot-glow, rgba(79, 70, 229, 0.25)) 0%, rgba(255, 255, 255, 0.1) 60%),
                url('assets/images/nivelia_paisaje.jpg') center center/cover no-repeat;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    overflow: hidden;
  }

  .single-mascot-render {
    height: 220px;
    width: auto;
    max-width: 80%;
    object-fit: contain;
    filter: drop-shadow(0 14px 22px rgba(0, 0, 0, 0.35));
    animation: mascotBounce 3.5s ease-in-out infinite;
    z-index: 2;
    margin-top: -15px;
  }

  @keyframes mascotBounce {
    0%, 100% { transform: translateY(0px) scale(1); }
    50% { transform: translateY(-8px) scale(1.02); }
  }

  .mascot-speech-bubble-pop {
    position: absolute;
    bottom: 35px;
    left: 50%;
    transform: translateX(-50%);
    background: rgba(255, 255, 255, 0.96);
    backdrop-filter: blur(10px);
    -webkit-backdrop-filter: blur(10px);
    border: 2px solid var(--mascot-accent, #4f46e5);
    padding: 0.45rem 1rem;
    border-radius: 999px;
    font-size: 0.8rem;
    font-weight: 800;
    color: var(--text-primary);
    box-shadow: 0 8px 24px rgba(0, 0, 0, 0.16);
    white-space: nowrap;
    max-width: 90%;
    text-overflow: ellipsis;
    overflow: hidden;
    z-index: 4;
    animation: popIn 0.35s cubic-bezier(0.16, 1, 0.3, 1);
  }

  @keyframes popIn {
    0% { transform: translateX(-50%) scale(0.85); opacity: 0; }
    100% { transform: translateX(-50%) scale(1); opacity: 1; }
  }

  .carousel-arrow {
    position: absolute;
    top: 50%;
    transform: translateY(-50%);
    width: 38px;
    height: 38px;
    border-radius: 50%;
    background: rgba(255, 255, 255, 0.88);
    backdrop-filter: blur(8px);
    -webkit-backdrop-filter: blur(8px);
    border: 1.5px solid rgba(255, 255, 255, 0.95);
    color: #1e293b;
    font-size: 1.5rem;
    font-weight: 700;
    line-height: 1;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    box-shadow: 0 4px 14px rgba(0, 0, 0, 0.16);
    z-index: 10;
    transition: all 0.2s ease;
    padding: 0;
  }
  .carousel-arrow:hover {
    background: #fff;
    transform: translateY(-50%) scale(1.12);
    color: var(--primary);
  }
  .carousel-arrow-prev { left: 10px; }
  .carousel-arrow-next { right: 10px; }

  .carousel-dots {
    position: absolute;
    bottom: 8px;
    left: 50%;
    transform: translateX(-50%);
    display: flex;
    align-items: center;
    gap: 6px;
    z-index: 10;
    background: rgba(0, 0, 0, 0.28);
    backdrop-filter: blur(6px);
    -webkit-backdrop-filter: blur(6px);
    padding: 4px 10px;
    border-radius: 999px;
  }
  .carousel-dot {
    width: 8px;
    height: 8px;
    border-radius: 50%;
    background: rgba(255, 255, 255, 0.55);
    cursor: pointer;
    transition: all 0.25s ease;
    display: inline-block;
  }
  .carousel-dot.active {
    width: 22px;
    border-radius: 999px;
    background: #fff;
    box-shadow: 0 0 10px rgba(255, 255, 255, 0.9);
  }

  .hero-tag-floating {
    position: absolute;
    top: 12px;
    left: 12px;
    background: rgba(255, 255, 255, 0.94);
    backdrop-filter: blur(8px);
    -webkit-backdrop-filter: blur(8px);
    border: 1px solid rgba(255, 255, 255, 0.9);
    padding: 0.35rem 0.8rem;
    border-radius: 999px;
    font-size: 0.78rem;
    font-weight: 800;
    color: #4338ca;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.12);
    display: flex;
    align-items: center;
    gap: 0.35rem;
    z-index: 6;
  }

  .hero-tag-badge {
    position: absolute;
    top: 12px;
    right: 12px;
    background: linear-gradient(135deg, #10b981, #059669);
    color: #fff;
    padding: 0.35rem 0.75rem;
    border-radius: 999px;
    font-size: 0.75rem;
    font-weight: 800;
    box-shadow: 0 4px 12px rgba(16, 185, 129, 0.3);
    letter-spacing: 0.02em;
    z-index: 6;
  }

  .hero-content {
    padding: 1.25rem 1.5rem;
    flex: 1;
    display: flex;
    flex-direction: column;
    justify-content: space-between;
  }

  .mascots-chips {
    display: flex;
    flex-wrap: wrap;
    gap: 0.45rem;
    margin-top: 0.65rem;
  }

  button.mascot-chip {
    display: inline-flex;
    align-items: center;
    gap: 0.4rem;
    background: #f8fafc;
    border: 1.5px solid #e2e8f0;
    padding: 0.35rem 0.75rem;
    border-radius: 999px;
    font-size: 0.78rem;
    font-weight: 700;
    color: var(--text-primary);
    cursor: pointer;
    font-family: inherit;
    transition: all 0.2s cubic-bezier(0.16, 1, 0.3, 1);
    outline: none;
  }

  button.mascot-chip:hover {
    background: #f1f5f9;
    border-color: #cbd5e1;
    transform: translateY(-2px);
  }

  button.mascot-chip.active {
    background: #4f46e5 !important;
    color: #ffffff !important;
    border-color: #4f46e5 !important;
    box-shadow: 0 4px 14px rgba(79, 70, 229, 0.4) !important;
    transform: translateY(-2px) scale(1.03);
  }

  button.mascot-chip.active span {
    color: #ffffff !important;
  }

  button.mascot-chip img {
    width: 22px;
    height: 22px;
    border-radius: 50%;
    object-fit: contain;
    background: #fff;
    box-shadow: 0 1px 2px rgba(0,0,0,0.1);
  }

  .login-card-glass {
    background: rgba(255, 255, 255, 0.95);
    backdrop-filter: blur(16px);
    -webkit-backdrop-filter: blur(16px);
    border-radius: 24px;
    border: 2px solid rgba(255, 255, 255, 0.85);
    box-shadow: 0 15px 35px rgba(15, 23, 42, 0.16);
    padding: 1.75rem 1.65rem;
    display: flex;
    flex-direction: column;
    justify-content: space-between;
  }

  @media (max-width: 840px) {
    .login-stage-container {
      grid-template-columns: 1fr;
      width: 100%;
      max-width: 560px;
      gap: 1.25rem;
      margin-top: 0.25rem;
    }
    .hero-img-wrap {
      height: 250px;
    }
    .single-mascot-render {
      height: 180px;
    }
    .hero-content {
      padding: 1.15rem 1.25rem;
    }
    .login-card-glass {
      padding: 1.5rem 1.25rem;
    }
  }
</style>

<div class="login-stage-container">
  
  <!-- Columna 1: Carrusel Emotivo con Todos los Personajes y Paisaje -->
  <div class="hero-showcase" id="tutorCarousel">
    <div class="hero-img-wrap">
      
      <!-- Slide 0: Todos juntos -->
      <div class="carousel-slide active" data-slide="0">
        <img src="assets/images/nivelia_portada_personajes.jpg" alt="Tutores de Nivelia" class="slide-full-photo">
        <div class="hero-tag-floating">
          <span>🇻🇪</span>
          <span>Aventura de Aprendizaje</span>
        </div>
        <div class="hero-tag-badge">
          <span>✨ Equipo Completo</span>
        </div>
      </div>

      <!-- Slide 1: Chigui -->
      <div class="carousel-slide" data-slide="1">
        <div class="single-mascot-scene" style="--mascot-accent: #d97706; --mascot-glow: rgba(217, 119, 6, 0.35);">
          <img src="assets/images/personajes/chiguire_pixar.png" alt="Chigui" class="single-mascot-render">
          <div class="mascot-speech-bubble-pop">
            <span>¡La sabiduría empieza en casa con calma y respeto! 📜🌟</span>
          </div>
        </div>
        <div class="hero-tag-floating">
          <span>🎓</span>
          <span>Chigui &bull; Historia y Valores</span>
        </div>
        <div class="hero-tag-badge" style="background: linear-gradient(135deg, #d97706, #b45309);">
          <span>⭐ Guía Sabio</span>
        </div>
      </div>

      <!-- Slide 2: Maya -->
      <div class="carousel-slide" data-slide="2">
        <div class="single-mascot-scene" style="--mascot-accent: #ef4444; --mascot-glow: rgba(239, 68, 68, 0.35);">
          <img src="assets/images/personajes/guacamaya_pixar.png" alt="Maya" class="single-mascot-render">
          <div class="mascot-speech-bubble-pop">
            <span>¡Abre tus alas de lectura y vuela hacia tus sueños! 📖✨</span>
          </div>
        </div>
        <div class="hero-tag-floating">
          <span>🦜</span>
          <span>Maya &bull; Lectura y Lenguaje</span>
        </div>
        <div class="hero-tag-badge" style="background: linear-gradient(135deg, #ef4444, #dc2626);">
          <span>✨ Creatividad</span>
        </div>
      </div>

      <!-- Slide 3: Toni -->
      <div class="carousel-slide" data-slide="3">
        <div class="single-mascot-scene" style="--mascot-accent: #06b6d4; --mascot-glow: rgba(6, 182, 212, 0.35);">
          <img src="assets/images/personajes/tonina_pixar.png" alt="Toni" class="single-mascot-render">
          <div class="mascot-speech-bubble-pop">
            <span>¡Descubre las maravillas de la ciencia y la naturaleza! 🌊🐬</span>
          </div>
        </div>
        <div class="hero-tag-floating">
          <span>🐬</span>
          <span>Toni &bull; Biología y Ciencias</span>
        </div>
        <div class="hero-tag-badge" style="background: linear-gradient(135deg, #06b6d4, #0891b2);">
          <span>🌊 Exploradora</span>
        </div>
      </div>

      <!-- Slide 4: Lutri -->
      <div class="carousel-slide" data-slide="4">
        <div class="single-mascot-scene" style="--mascot-accent: #8b5cf6; --mascot-glow: rgba(139, 92, 246, 0.35);">
          <img src="assets/images/personajes/nutria_pixar.png" alt="Lutri" class="single-mascot-render">
          <div class="mascot-speech-bubble-pop">
            <span>¡Las matemáticas son tu superpoder para triunfar! 🧮⚡</span>
          </div>
        </div>
        <div class="hero-tag-floating">
          <span>🦦</span>
          <span>Lutri &bull; Matemáticas y Lógica</span>
        </div>
        <div class="hero-tag-badge" style="background: linear-gradient(135deg, #8b5cf6, #7c3aed);">
          <span>⚡ Lógica Ágil</span>
        </div>
      </div>

      <!-- Slide 5: Nacho -->
      <div class="carousel-slide" data-slide="5">
        <div class="single-mascot-scene" style="--mascot-accent: #10b981; --mascot-glow: rgba(16, 185, 129, 0.35);">
          <img src="assets/images/personajes/iguana_pixar.png" alt="Nacho" class="single-mascot-render">
          <div class="mascot-speech-bubble-pop">
            <span>¡Conoce tu tierra y mantén a tu familia segura! ⛰️🦎</span>
          </div>
        </div>
        <div class="hero-tag-floating">
          <span>🦎</span>
          <span>Nacho &bull; Geografía y Prevención</span>
        </div>
        <div class="hero-tag-badge" style="background: linear-gradient(135deg, #10b981, #059669);">
          <span>🛡️ Protección</span>
        </div>
      </div>

      <!-- Slide 6: Tito -->
      <div class="carousel-slide" data-slide="6">
        <div class="single-mascot-scene" style="--mascot-accent: #84cc16; --mascot-glow: rgba(132, 204, 22, 0.35);">
          <img src="assets/images/personajes/periquito_pixar.png" alt="Tito" class="single-mascot-render">
          <div class="mascot-speech-bubble-pop">
            <span>¡Despeja las incógnitas jugando con una gran sonrisa! 💡🐦</span>
          </div>
        </div>
        <div class="hero-tag-floating">
          <span>🐦</span>
          <span>Tito &bull; Álgebra y Desafíos</span>
        </div>
        <div class="hero-tag-badge" style="background: linear-gradient(135deg, #84cc16, #65a30d);">
          <span>🎯 Ingenio</span>
        </div>
      </div>

      <!-- Flechas de navegación -->
      <button type="button" class="carousel-arrow carousel-arrow-prev" onclick="changeSlideStep(-1)" aria-label="Anterior">‹</button>
      <button type="button" class="carousel-arrow carousel-arrow-next" onclick="changeSlideStep(1)" aria-label="Siguiente">›</button>

      <!-- Puntos indicadores (dots) -->
      <div class="carousel-dots" id="carouselDots">
        <span class="carousel-dot active" onclick="goToSlide(0)" title="Todos"></span>
        <span class="carousel-dot" onclick="goToSlide(1)" title="Chigui"></span>
        <span class="carousel-dot" onclick="goToSlide(2)" title="Maya"></span>
        <span class="carousel-dot" onclick="goToSlide(3)" title="Toni"></span>
        <span class="carousel-dot" onclick="goToSlide(4)" title="Lutri"></span>
        <span class="carousel-dot" onclick="goToSlide(5)" title="Nacho"></span>
        <span class="carousel-dot" onclick="goToSlide(6)" title="Tito"></span>
      </div>
    </div>

    <!-- Contenido y mensaje dinámico del tutor -->
    <div class="hero-content">
      <div id="slideContent">
        <h2 style="font-size: 1.3rem; font-weight: 800; color: #4f46e5; margin-bottom: 0.45rem; line-height: 1.3;">
          ¡Aprende cada día con tus tutores favoritos! 🎒✨
        </h2>
        <p style="font-size: 0.92rem; color: var(--text-secondary); line-height: 1.55; margin: 0 0 0.65rem 0;">
          En <strong>Nivelia</strong> transformamos las materias de tu grado escolar en retos diarios para superar cualquier debilidad y construir un futuro brillante.
        </p>
        <div style="font-size: 0.82rem; font-weight: 700; color: #4f46e5; font-style: italic; opacity: 0.95;">
          💡 "Bases sólidas hoy, grandes profesionales mañana."
        </div>
      </div>

      <!-- Chips de tutores vinculados al carrusel -->
      <div style="margin-top: 1.25rem;">
        <div style="font-size: 0.78rem; font-weight: 800; text-transform: uppercase; letter-spacing: 0.05em; color: var(--text-muted); margin-bottom: 0.5rem;">
          🐾 Selecciona un tutor para escuchar su mensaje:
        </div>
        <div class="mascots-chips" id="mascotChips">
          <button type="button" class="mascot-chip active" onclick="goToSlide(0)" data-chip="0" title="Todo el Equipo">
            <span style="font-size: 0.95rem;">✨</span>
            <span>Todos</span>
          </button>
          <button type="button" class="mascot-chip" onclick="goToSlide(1)" data-chip="1" title="Sabiduría y Convivencia">
            <img src="assets/images/personajes/chiguire_pixar.png" alt="Chigui">
            <span>Chigui &bull; Historia</span>
          </button>
          <button type="button" class="mascot-chip" onclick="goToSlide(2)" data-chip="2" title="Lectura y Redacción">
            <img src="assets/images/personajes/guacamaya_pixar.png" alt="Maya">
            <span>Maya &bull; Lectura</span>
          </button>
          <button type="button" class="mascot-chip" onclick="goToSlide(3)" data-chip="3" title="Biología y Naturaleza">
            <img src="assets/images/personajes/tonina_pixar.png" alt="Toni">
            <span>Toni &bull; Ciencias</span>
          </button>
          <button type="button" class="mascot-chip" onclick="goToSlide(4)" data-chip="4" title="Matemáticas y Lógica">
            <img src="assets/images/personajes/nutria_pixar.png" alt="Lutri">
            <span>Lutri &bull; Mate</span>
          </button>
          <button type="button" class="mascot-chip" onclick="goToSlide(5)" data-chip="5" title="Geografía y Prevención">
            <img src="assets/images/personajes/iguana_pixar.png" alt="Nacho">
            <span>Nacho &bull; Geografía</span>
          </button>
          <button type="button" class="mascot-chip" onclick="goToSlide(6)" data-chip="6" title="Álgebra y Desafíos">
            <img src="assets/images/personajes/periquito_pixar.png" alt="Tito">
            <span>Tito &bull; Álgebra</span>
          </button>
        </div>
      </div>
    </div>
  </div>

  <!-- Columna 2: Tarjeta de Acceso a Nivelia -->
  <div class="login-card-glass">
    <div>
      <div style="text-align: center; margin-bottom: 1.25rem;">
        <div class="login-logo-video-container" style="display: flex; justify-content: center; align-items: center; margin-bottom: 0.85rem;">
          <video 
            id="introLogoVideo" 
            src="logo/_logo-material-assamble_1080p_20261006185523.mp4" 
            autoplay 
            muted 
            playsinline 
            preload="auto"
            title="Logo Estudio en Casa"
            style="width: 100%; max-width: 220px; height: auto; border-radius: 14px; box-shadow: 0 4px 18px rgba(15, 23, 42, 0.08); background: #f8fafc; object-fit: contain; cursor: pointer; transition: transform 0.25s ease;"
          >
            Tu navegador no soporta videos HTML5.
          </video>
        </div>
        <h1 style="font-size: 1.6rem; font-weight: 800; margin-bottom: 0.25rem;">¡Hola de nuevo! 👋</h1>
        <p style="font-size: 0.92rem; color: var(--text-secondary); margin: 0;">
          Ingresa para continuar tus misiones de hoy
        </p>
      </div>

      <?php if (!empty($requestSuccess)): ?>
        <div style="background: #ecfdf5; color: #065f46; border: 1.5px solid #a7f3d0; padding: 0.95rem 1.15rem; border-radius: var(--radius-md); margin-bottom: 1.25rem; font-size: 0.9rem; font-weight: 600; display: flex; align-items: flex-start; gap: 0.6rem; box-shadow: 0 4px 12px rgba(16, 185, 129, 0.1);">
          <span style="font-size: 1.25rem; line-height: 1;">🎉</span>
          <div style="line-height: 1.45;"><?= htmlspecialchars($requestSuccess) ?></div>
        </div>
      <?php endif; ?>

      <?php if ($error): ?>
        <div style="background: var(--danger-light); color: var(--danger); border: 1px solid #fecaca; padding: 0.85rem 1rem; border-radius: var(--radius-md); margin-bottom: 1.25rem; font-size: 0.9rem; font-weight: 600; display: flex; align-items: center; gap: 0.5rem;">
          <span>⚠️</span>
          <span><?= htmlspecialchars($error) ?></span>
        </div>
      <?php endif; ?>

      <form method="POST" action="login.php">
        <input type="hidden" name="action" value="login">
        <div class="form-group" style="margin-bottom: 1rem;">
          <label class="form-label" for="email">
            👤 Usuario o Correo Electrónico
          </label>
          <input 
            type="text" 
            id="email" 
            name="email" 
            class="form-control" 
            placeholder="Tu usuario o correo (ej: dimas, alexander, papa)" 
            value="<?= htmlspecialchars($_POST['email'] ?? $_POST['login_input'] ?? '') ?>"
            required 
            autocomplete="username">
        </div>

        <div class="form-group" style="margin-bottom: 1.5rem;">
          <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 0.35rem;">
            <label class="form-label" for="password" style="margin-bottom: 0;">
              🔒 Contraseña
            </label>
            <button type="button" onclick="togglePasswordVisibility()" style="background: none; border: none; color: var(--primary); font-size: 0.82rem; font-weight: 600; cursor: pointer;">
              👁️ Ver / Ocultar
            </button>
          </div>
          <input 
            type="password" 
            id="password" 
            name="password" 
            class="form-control" 
            placeholder="••••••••" 
            required 
            autocomplete="current-password">
        </div>

        <button type="submit" class="btn btn-primary btn-block btn-lg" style="box-shadow: 0 4px 14px rgba(79, 70, 229, 0.35); font-size: 1rem;">
          <span>🚀</span>
          <span>Entrar a Nivelia</span>
        </button>
      </form>

      <!-- Botón para solicitar cuenta al Administrador -->
      <div style="margin-top: 1.5rem; padding-top: 1.25rem; border-top: 1.5px dashed #cbd5e1; text-align: center;">
        <p style="font-size: 0.86rem; color: var(--text-secondary); margin: 0 0 0.65rem 0; font-weight: 600;">
          ¿Deseas registrar a un nuevo estudiante?
        </p>
        <button type="button" class="btn btn-secondary btn-block" onclick="openRequestModal()" style="font-size: 0.9rem; font-weight: 700; color: #4f46e5; border-color: #c7d2fe; background: #eef2ff;">
          <span>📝</span>
          <span>Solicitar Cuenta al Administrador</span>
        </button>
      </div>
    </div>

  </div>

</div>

<!-- Modal de Solicitud de Acceso para Nuevos Usuarios -->
<div id="requestModal" style="display: none; position: fixed; inset: 0; background: rgba(15, 23, 42, 0.7); backdrop-filter: blur(8px); -webkit-backdrop-filter: blur(8px); z-index: 9999; align-items: center; justify-content: center; padding: 1rem; overflow-y: auto;">
  <div style="background: #ffffff; width: 100%; max-width: 520px; border-radius: 24px; box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25); border: 2px solid rgba(255, 255, 255, 0.8); overflow: hidden; margin: auto; animation: popIn 0.3s cubic-bezier(0.16, 1, 0.3, 1);">
    
    <!-- Encabezado del Modal -->
    <div style="background: linear-gradient(135deg, #4f46e5, #6366f1); color: #ffffff; padding: 1.25rem 1.5rem; display: flex; align-items: center; justify-content: space-between;">
      <div style="display: flex; align-items: center; gap: 0.65rem;">
        <span style="font-size: 1.6rem;">📝</span>
        <div>
          <h3 style="margin: 0; font-size: 1.2rem; font-weight: 800; color: #ffffff;">Solicitud de Nuevo Acceso</h3>
          <p style="margin: 0; font-size: 0.82rem; color: rgba(255, 255, 255, 0.9);">
            Petición directa al Administrador de Nivelia
          </p>
        </div>
      </div>
      <button type="button" onclick="closeRequestModal()" style="background: rgba(255, 255, 255, 0.2); border: none; color: #ffffff; font-size: 1.25rem; line-height: 1; border-radius: 50%; width: 32px; height: 32px; cursor: pointer; display: flex; align-items: center; justify-content: center; transition: background 0.2s ease;">
        &times;
      </button>
    </div>

    <!-- Contenido y Formulario -->
    <form method="POST" action="login.php" style="padding: 1.5rem;">
      <input type="hidden" name="action" value="request_access">

      <div style="background: #f0fdf4; border: 1px solid #bbf7d0; border-radius: 12px; padding: 0.75rem 0.9rem; margin-bottom: 1.25rem; font-size: 0.84rem; color: #166534; display: flex; gap: 0.5rem; align-items: flex-start;">
        <span>ℹ️</span>
        <span>Deja tus datos para que el administrador cree tus credenciales y te las entregue por WhatsApp o correo.</span>
      </div>

      <div class="form-group" style="margin-bottom: 1rem;">
        <label class="form-label" for="req_name" style="font-size: 0.88rem; font-weight: 700;">
          👤 Nombre Completo del Alumno / Solicitante *
        </label>
        <input 
          type="text" 
          id="req_name" 
          name="req_name" 
          class="form-control" 
          placeholder="Ej: Sofía Valentina Martínez" 
          required>
      </div>

      <div class="form-group" style="margin-bottom: 1rem;">
        <label class="form-label" for="req_email" style="font-size: 0.88rem; font-weight: 700;">
          📧 Correo Electrónico *
        </label>
        <input 
          type="email" 
          id="req_email" 
          name="req_email" 
          class="form-control" 
          placeholder="tu-correo@ejemplo.com" 
          required>
      </div>

      <div class="form-group" style="margin-bottom: 1rem;">
        <label class="form-label" for="req_whatsapp" style="font-size: 0.88rem; font-weight: 700;">
          💬 Teléfono o WhatsApp *
        </label>
        <input 
          type="text" 
          id="req_whatsapp" 
          name="req_whatsapp" 
          class="form-control" 
          placeholder="Ej: +58 412 1234567 o 0414-9876543" 
          required>
        <span style="font-size: 0.75rem; color: var(--text-muted); display: block; margin-top: 0.25rem;">
          Por esta vía el administrador te enviará tu clave de acceso.
        </span>
      </div>

      <div class="form-group" style="margin-bottom: 1rem;">
        <label class="form-label" for="req_grade" style="font-size: 0.88rem; font-weight: 700;">
          🎓 Grado Académico *
        </label>
        <select id="req_grade" name="req_grade" class="form-control" required style="font-weight: 600;">
          <option value="" disabled selected>Selecciona tu grado escolar...</option>
          <option value="1">1er Grado de Primaria</option>
          <option value="2">2do Grado de Primaria</option>
          <option value="3">3er Grado de Primaria</option>
          <option value="4">4to Grado de Primaria</option>
          <option value="5">5to Grado de Primaria</option>
          <option value="6">6to Grado de Primaria</option>
        </select>
      </div>

      <div class="form-group" style="margin-bottom: 1.25rem;">
        <label class="form-label" for="req_notes" style="font-size: 0.88rem; font-weight: 700;">
          ✍️ Nota o Comentario Adicional (Opcional)
        </label>
        <textarea 
          id="req_notes" 
          name="req_notes" 
          class="form-control" 
          rows="2" 
          placeholder="Ej: Deseo nivelar matemáticas y lectura..."></textarea>
      </div>

      <div style="display: flex; gap: 0.75rem; justify-content: flex-end; align-items: center;">
        <button type="button" class="btn btn-secondary" onclick="closeRequestModal()" style="font-size: 0.9rem;">
          Cancelar
        </button>
        <button type="submit" class="btn btn-primary" style="font-size: 0.9rem; font-weight: 800; box-shadow: 0 4px 12px rgba(79, 70, 229, 0.35);">
          <span>🚀</span>
          <span>Enviar Solicitud al Admin</span>
        </button>
      </div>
    </form>

  </div>
</div>

<script>
function openRequestModal() {
  const m = document.getElementById('requestModal');
  if (m) {
    m.style.display = 'flex';
    document.body.style.overflow = 'hidden';
  }
}

function closeRequestModal() {
  const m = document.getElementById('requestModal');
  if (m) {
    m.style.display = 'none';
    document.body.style.overflow = '';
  }
}

function togglePasswordVisibility() {
  const passInput = document.getElementById('password');
  if (passInput.type === 'password') {
    passInput.type = 'text';
  } else {
    passInput.type = 'password';
  }
}

// Datos de cada tutor y mensajes motivacionales vinculados
const slidesData = [
  {
    title: "¡Aprende cada día con tus tutores favoritos! 🎒✨",
    text: "En <strong>Nivelia</strong> transformamos las materias de tu grado escolar en retos diarios para superar cualquier debilidad y construir un futuro brillante.",
    motto: "Bases sólidas hoy, grandes profesionales mañana.",
    color: "#4f46e5"
  },
  {
    title: "Chigui: ¡La sabiduría se construye con calma y respeto! 📜🌟",
    text: "¡Hola, gran estudiante! Conocer nuestra historia, valorar tu esfuerzo diario y convivir en paz te convertirá en un líder ejemplar. ¡El futuro comienza hoy!",
    motto: "La constancia diaria convierte cualquier meta en realidad.",
    color: "#d97706"
  },
  {
    title: "Maya: ¡Tus palabras tienen el poder de volar muy alto! 📖✨",
    text: "¡Hola, lector estrella! Quien lee con entusiasmo y escribe con claridad tiene el mundo en sus manos. ¡Ven a descubrir emocionantes aventuras en cada lección!",
    motto: "Quien lee y comprende, vuela hacia donde sueñe.",
    color: "#ef4444"
  },
  {
    title: "Toni: ¡Sumérgete a descubrir los secretos de la vida! 🌊🔬",
    text: "¡Qué alegría verte! La ciencia nos enseña cómo funciona la naturaleza, nuestro cuerpo y nuestro planeta. Sé curioso, pregunta sin miedo y aprende jugando.",
    motto: "La curiosidad científica es el motor de las grandes mentes.",
    color: "#06b6d4"
  },
  {
    title: "Lutri: ¡Las matemáticas son tu superpoder secreto! 🧮⚡",
    text: "¡Epa, genio! Los números, las operaciones y los problemas son como rompecabezas divertidos. Domina tus bases hoy y serás un profesional brillante.",
    motto: "Paso a paso, ningún problema matemático se resiste a tu ingenio.",
    color: "#8b5cf6"
  },
  {
    title: "Nacho: ¡Conoce tu tierra y mantén seguro a tu hogar! ⛰️🛡️",
    text: "¡Saludos desde La Guaira! Conocer nuestras montañas, costas y saber cómo prevenir riesgos nos hace sabios, valientes y responsables. ¡Vamos con todo!",
    motto: "Estar preparado y conocer tu entorno te hace invencible.",
    color: "#10b981"
  },
  {
    title: "Tito: ¡Despeja las incógnitas jugando con una gran sonrisa! 💡🐦",
    text: "¡Hola, campeón! Las fracciones y las ecuaciones no son misterios difíciles: ¡son pistas de detectives! Con práctica diaria, ninguna debilidad te detendrá.",
    motto: "El ingenio alegre y la práctica diaria lo logran todo.",
    color: "#84cc16"
  }
];

let currentSlideIdx = 0;
let autoSlideTimer = null;

function renderSlideContent(idx) {
  const data = slidesData[idx];
  const container = document.getElementById('slideContent');
  if (!container) return;
  container.style.opacity = '0';
  container.style.transform = 'translateY(4px)';
  
  setTimeout(() => {
    container.innerHTML = `
      <h2 style="font-size: 1.3rem; font-weight: 800; color: ${data.color}; margin-bottom: 0.45rem; line-height: 1.3; transition: color 0.3s ease;">
        ${data.title}
      </h2>
      <p style="font-size: 0.92rem; color: var(--text-secondary); line-height: 1.55; margin: 0 0 0.65rem 0;">
        ${data.text}
      </p>
      <div style="font-size: 0.82rem; font-weight: 700; color: ${data.color}; font-style: italic; opacity: 0.95;">
        💡 "${data.motto}"
      </div>
    `;
    container.style.transition = 'opacity 0.25s ease, transform 0.25s ease';
    container.style.opacity = '1';
    container.style.transform = 'translateY(0)';
  }, 120);
}

function goToSlide(idx, playSound = true) {
  currentSlideIdx = (idx + slidesData.length) % slidesData.length;
  
  // Actualizar diapositivas
  document.querySelectorAll('.carousel-slide').forEach((el, i) => {
    el.classList.toggle('active', i === currentSlideIdx);
  });
  
  // Actualizar puntos indicadores
  document.querySelectorAll('.carousel-dot').forEach((el, i) => {
    el.classList.toggle('active', i === currentSlideIdx);
  });
  
  // Actualizar chips de selección
  document.querySelectorAll('.mascot-chip').forEach((el) => {
    const chipIdx = parseInt(el.getAttribute('data-chip'), 10);
    const isActive = (chipIdx === currentSlideIdx);
    el.classList.toggle('active', isActive);
    if (isActive) {
      const activeColor = slidesData[currentSlideIdx].color;
      el.style.borderColor = activeColor;
    } else {
      el.style.borderColor = '';
    }
  });

  renderSlideContent(currentSlideIdx);

  // Sonido suave de interacción Web Audio
  if (playSound && window.SoundFx && typeof SoundFx.playTone === 'function') {
    const freqs = [523, 587, 659, 698, 784, 880, 988];
    SoundFx.playTone(freqs[currentSlideIdx] || 520, 'sine', 0.12);
  }

  resetAutoSlide();
}

function changeSlideStep(step) {
  goToSlide(currentSlideIdx + step, true);
}

function resetAutoSlide() {
  if (autoSlideTimer) clearInterval(autoSlideTimer);
  // Si el usuario eligió un tutor específico (slide > 0), dejarlo fijo para que lo aprecie
  // Solo rotar automáticamente en la pantalla de "Todos" (slide 0)
  if (currentSlideIdx === 0) {
    autoSlideTimer = setInterval(() => {
      goToSlide(currentSlideIdx + 1, false);
    }, 8000);
  }
}

// Iniciar auto-slide y pausar si el ratón está encima del carrusel
document.addEventListener('DOMContentLoaded', () => {
  resetAutoSlide();
  const carouselEl = document.getElementById('tutorCarousel');
  if (carouselEl) {
    carouselEl.addEventListener('mouseenter', () => {
      if (autoSlideTimer) clearInterval(autoSlideTimer);
    });
    carouselEl.addEventListener('mouseleave', () => {
      resetAutoSlide();
    });
  }

  // Iniciar video animado del logo sin audio y congelar en el último fotograma
  const introVideo = document.getElementById('introLogoVideo');
  if (introVideo) {
    introVideo.muted = true;
    const playPromise = introVideo.play();
    if (playPromise !== undefined) {
      playPromise.catch(() => {
        // Manejar política de autoplay si el navegador lo requiere
      });
    }

    introVideo.addEventListener('ended', function() {
      this.pause();
    });

    introVideo.addEventListener('click', function() {
      this.currentTime = 0;
      this.play();
    });
  }
});
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
