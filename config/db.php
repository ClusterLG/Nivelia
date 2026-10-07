<?php
/**
 * Configuración de la Base de Datos y Entorno
 * Plataforma: Nivelia
 */

// Establecer la zona horaria oficial de Venezuela
date_default_timezone_set('America/Caracas');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

define('DB_HOST', '127.0.0.1');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'estudio_en_casa');
define('APP_NAME', 'Nivelia');
define('APP_TAGLINE', 'Bases sólidas hoy, grandes profesionales mañana');

require_once __DIR__ . '/supabase_config.php';

// Conexión PDO con soporte dual: Supabase (PostgreSQL) y XAMPP (MySQL)
function getDB() {
    static $pdo = null;
    if ($pdo !== null) {
        return $pdo;
    }

    try {
        // Conexión a Supabase (PostgreSQL en la nube)
        if (defined('USE_SUPABASE') && USE_SUPABASE) {
            $port = defined('SUPABASE_PORT') ? SUPABASE_PORT : '5432';
            $ssl = defined('SUPABASE_SSL') ? SUPABASE_SSL : 'require';
            $dsn = "pgsql:host=" . SUPABASE_HOST . ";port=" . $port . ";dbname=" . SUPABASE_DB . ";sslmode=" . $ssl;
            $pdo = new PDO($dsn, SUPABASE_USER, SUPABASE_PASS, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ]);
            return $pdo;
        }

        // Conexión por defecto a MySQL de XAMPP local
        // Primero nos conectamos al servidor para garantizar que la BD existe
        $dsnWithoutDb = "mysql:host=" . DB_HOST . ";charset=utf8mb4";
        $pdoInit = new PDO($dsnWithoutDb, DB_USER, DB_PASS, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);
        
        // Crear base de datos si no existe
        $pdoInit->exec("CREATE DATABASE IF NOT EXISTS `" . DB_NAME . "` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
        
        // Conexión a la base de datos específica
        $dsnWithDb = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4";
        $pdo = new PDO($dsnWithDb, DB_USER, DB_PASS, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]);

        return $pdo;
    } catch (PDOException $e) {
        $source = (defined('USE_SUPABASE') && USE_SUPABASE) ? 'Supabase (PostgreSQL)' : 'MySQL (XAMPP)';
        die("<div style='font-family:sans-serif;padding:30px;background:#fee;color:#900;border:2px solid #c00;border-radius:10px;max-width:600px;margin:40px auto;text-align:center;'>
            <h2>⚠️ Error de Conexión a la Base de Datos ($source)</h2>
            <p>No se pudo establecer conexión con la base de datos.</p>
            <p><strong>Detalle:</strong> " . htmlspecialchars($e->getMessage()) . "</p>
        </div>");
    }
}

// Helpers de Sesión y Autenticación
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function isAdmin() {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
}

function currentUser() {
    if (isset($_SESSION['user_id'])) {
        static $cached = null;
        if ($cached !== null) {
            return $cached;
        }
        $pdo = getDB();
        $stmt = $pdo->prepare("SELECT id, name, email, role, grade, avatar, total_xp, streak_days FROM users WHERE id = :id");
        $stmt->execute(['id' => $_SESSION['user_id']]);
        $u = $stmt->fetch();
        if ($u) {
            $_SESSION['user'] = $u;
            $cached = $u;
            return $u;
        }
    }
    return $_SESSION['user'] ?? null;
}

function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: ' . getBaseUrl() . 'login.php');
        exit;
    }
}

function requireAdmin() {
    requireLogin();
    if (!isAdmin()) {
        header('Location: ' . getBaseUrl() . 'student/dashboard.php');
        exit;
    }
}

/**
 * Búsqueda inteligente de usuario para inicio de sesión
 * Soporta correo exacto, nombre exacto o alias comunes (dimas, alexander, papa, admin)
 */
function findUserForLogin($loginInput) {
    $loginInput = trim($loginInput);
    if (empty($loginInput)) return null;

    $pdo = getDB();
    $low = strtolower($loginInput);

    // 1. Alias rápidos para usuarios frecuentes
    if ($low === 'dimas') {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE LOWER(name) LIKE :t1 OR LOWER(email) LIKE :t2 LIMIT 1");
        $stmt->execute(['t1' => '%dimas%', 't2' => '%dimas%']);
        $u = $stmt->fetch();
        if ($u) return $u;
    }
    if ($low === 'alexander' || $low === 'alex') {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE LOWER(name) LIKE :t1 OR LOWER(email) LIKE :t2 LIMIT 1");
        $stmt->execute(['t1' => '%alex%', 't2' => '%alex%']);
        $u = $stmt->fetch();
        if ($u) return $u;
    }
    if ($low === 'admin' || $low === 'papa' || $low === 'tutor') {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE role = 'admin' LIMIT 1");
        $stmt->execute();
        $u = $stmt->fetch();
        if ($u) return $u;
    }

    // 2. Búsqueda exacta por email o por nombre completo
    $stmt = $pdo->prepare("SELECT * FROM users WHERE LOWER(email) = :email OR LOWER(name) = :name LIMIT 1");
    $stmt->execute(['email' => $low, 'name' => $low]);
    return $stmt->fetch() ?: null;
}

/**
 * Gestión de Solicitudes de Acceso para Nuevos Usuarios
 */
function createUserAccessRequest($studentName, $guardianName, $email, $whatsapp, $grade, $message = '') {
    try {
        $pdo = getDB();
        $stmt = $pdo->prepare("
            INSERT INTO user_access_requests (student_name, guardian_name, email, whatsapp, grade, message, status, created_at)
            VALUES (:name, :guardian, :email, :wa, :grade, :msg, 'pending', NOW())
        ");
        return $stmt->execute([
            'name' => trim($studentName),
            'guardian' => trim($guardianName ?: ''),
            'email' => strtolower(trim($email)),
            'wa' => trim($whatsapp),
            'grade' => (int)$grade,
            'msg' => trim($message)
        ]);
    } catch (Exception $e) {
        return false;
    }
}

function getAllUserAccessRequests($status = null) {
    try {
        $pdo = getDB();
        if ($status !== null) {
            $stmt = $pdo->prepare("SELECT * FROM user_access_requests WHERE status = :st ORDER BY created_at DESC");
            $stmt->execute(['st' => $status]);
            return $stmt->fetchAll();
        } else {
            return $pdo->query("SELECT * FROM user_access_requests ORDER BY (CASE status WHEN 'pending' THEN 1 ELSE 2 END), created_at DESC")->fetchAll();
        }
    } catch (Exception $e) {
        return [];
    }
}

function getUserAccessRequestById($id) {
    try {
        $pdo = getDB();
        $stmt = $pdo->prepare("SELECT * FROM user_access_requests WHERE id = :id");
        $stmt->execute(['id' => (int)$id]);
        return $stmt->fetch() ?: null;
    } catch (Exception $e) {
        return null;
    }
}

function updateUserAccessRequestStatus($id, $status) {
    try {
        $pdo = getDB();
        $stmt = $pdo->prepare("UPDATE user_access_requests SET status = :st, reviewed_at = NOW() WHERE id = :id");
        return $stmt->execute(['st' => $status, 'id' => (int)$id]);
    } catch (Exception $e) {
        return false;
    }
}


// URL base dinámica que soporta acceso por localhost y por IP desde celulares
function getBaseUrl() {
    $scriptDir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME']));
    // Si estamos en subcarpeta 'admin' o 'student', subimos a la raíz
    if (basename($scriptDir) === 'admin' || basename($scriptDir) === 'student') {
        $scriptDir = dirname($scriptDir);
    }
    $baseUrl = rtrim($scriptDir, '/') . '/';
    return $baseUrl;
}

// Nombre de los días en español
function getDayNameSpanish($day) {
    $days = [
        'lunes' => 'Lunes',
        'martes' => 'Martes',
        'miercoles' => 'Miércoles',
        'jueves' => 'Jueves',
        'viernes' => 'Viernes',
        'sabado' => 'Sábado',
        'domingo' => 'Domingo'
    ];
    return $days[strtolower($day)] ?? ucfirst($day);
}

// Obtener día actual en formato del sistema (lunes a viernes)
function getCurrentDaySlug() {
    $dayOfWeek = date('N'); // 1 = Lunes, 7 = Domingo
    $map = [
        1 => 'lunes',
        2 => 'martes',
        3 => 'miercoles',
        4 => 'jueves',
        5 => 'viernes',
        6 => 'sabado',
        7 => 'domingo'
    ];
    return $map[$dayOfWeek] ?? 'lunes';
}

// Obtener número de orden de un día de la semana escolar (1 a 5)
function getDayNumber($slug) {
    $map = [
        'lunes' => 1,
        'martes' => 2,
        'miercoles' => 3,
        'jueves' => 4,
        'viernes' => 5,
        'sabado' => 6,
        'domingo' => 7
    ];
    return $map[strtolower($slug)] ?? 1;
}

// Saber si un día escolar y semana están desbloqueados para los estudiantes
// Regla: Los alumnos solo pueden ver los días de la semana activa y solo el material de los días transcurridos y del mismo día (se actualizan a medianoche)
function isDayUnlockedForStudents($daySlug, $weekNumber = null, $userGrade = null) {
    if (isAdmin()) {
        return true; // Papá siempre puede ver y planificar todos los días y semanas
    }
    
    if ($userGrade === null) {
        $u = currentUser();
        $userGrade = $u['grade'] ?? 6;
    }
    
    if ($weekNumber !== null) {
        $activeWeek = getActiveWeekForGrade($userGrade);
        // Semanas futuras están bloqueadas
        if ($weekNumber > $activeWeek) {
            return false;
        }
        // Semanas pasadas completadas están disponibles para repasar
        if ($weekNumber < $activeWeek) {
            return true;
        }
    }

    $currentDayNum = (int)date('N'); // 1 = Lunes, 2 = Martes, ..., 5 = Viernes, 6 = Sábado, 7 = Domingo
    if ($currentDayNum >= 6) {
        return true; // En fin de semana, toda la semana escolar ya transcurrió
    }
    $targetDayNum = getDayNumber($daySlug);
    return $targetDayNum <= $currentDayNum;
}

// Configuraciones del Sistema (Semana Activa por Grado, Lapso, etc.)
function getSystemSetting($key, $default = null) {
    static $cache = [];
    if (isset($cache[$key])) return $cache[$key];
    try {
        $pdo = getDB();
        $stmt = $pdo->prepare("SELECT setting_value FROM system_settings WHERE setting_key = :k");
        $stmt->execute(['k' => $key]);
        $val = $stmt->fetchColumn();
        $res = ($val !== false) ? $val : $default;
        $cache[$key] = $res;
        return $res;
    } catch (Exception $e) {
        return $default;
    }
}

function setSystemSetting($key, $value) {
    try {
        $pdo = getDB();
        $isPg = ($pdo->getAttribute(PDO::ATTR_DRIVER_NAME) === 'pgsql');
        $sql = $isPg
            ? "INSERT INTO system_settings (setting_key, setting_value, updated_at)
               VALUES (:k, :v, NOW())
               ON CONFLICT (setting_key) DO UPDATE SET setting_value = EXCLUDED.setting_value, updated_at = NOW()"
            : "INSERT INTO system_settings (setting_key, setting_value)
               VALUES (:k, :v)
               ON DUPLICATE KEY UPDATE setting_value = :v, updated_at = NOW()";
        $stmt = $pdo->prepare($sql);
        return $stmt->execute(['k' => $key, 'v' => (string)$value]);
    } catch (Exception $e) {
        return false;
    }
}

function getActiveWeekForGrade($grade) {
    $key = ($grade == 5) ? 'active_week_5to' : 'active_week_6to';
    return (int)getSystemSetting($key, 1);
}

function setActiveWeekForGrade($grade, $week) {
    $key = ($grade == 5) ? 'active_week_5to' : 'active_week_6to';
    return setSystemSetting($key, max(1, (int)$week));
}

// Notificaciones del Administrador
function createAdminNotification($userId, $type, $title, $message, $link = null) {
    try {
        $pdo = getDB();
        $stmt = $pdo->prepare("
            INSERT INTO admin_notifications (user_id, type, title, message, link, is_read, created_at)
            VALUES (:uid, :type, :title, :msg, :link, 0, NOW())
        ");
        return $stmt->execute([
            'uid' => $userId,
            'type' => $type,
            'title' => $title,
            'msg' => $message,
            'link' => $link
        ]);
    } catch (Exception $e) {
        return false;
    }
}

function getUnreadAdminNotificationsCount() {
    try {
        $pdo = getDB();
        return (int)$pdo->query("SELECT COUNT(*) FROM admin_notifications WHERE is_read = 0")->fetchColumn();
    } catch (Exception $e) {
        return 0;
    }
}

// Logs y Auditoría de Sesiones
function logSessionLogin($userId) {
    try {
        $pdo = getDB();
        $token = bin2hex(random_bytes(16));
        $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
        $ua = substr($_SERVER['HTTP_USER_AGENT'] ?? 'Desconocido', 0, 250);

        $stmt = $pdo->prepare("
            INSERT INTO user_session_logs (user_id, session_token, ip_address, user_agent, login_at, last_activity_at, duration_seconds, status)
            VALUES (:uid, :token, :ip, :ua, NOW(), NOW(), 0, 'active')
        ");
        $stmt->execute([
            'uid' => $userId,
            'token' => $token,
            'ip' => $ip,
            'ua' => $ua
        ]);
        $_SESSION['session_log_id'] = $pdo->lastInsertId();
    } catch (Exception $e) {}
}

function updateSessionHeartbeat() {
    if (empty($_SESSION['session_log_id'])) return;
    try {
        $pdo = getDB();
        $isPg = ($pdo->getAttribute(PDO::ATTR_DRIVER_NAME) === 'pgsql');
        $durationExpr = $isPg ? "EXTRACT(EPOCH FROM (NOW() - login_at))::int" : "TIMESTAMPDIFF(SECOND, login_at, NOW())";
        $stmt = $pdo->prepare("
            UPDATE user_session_logs 
            SET last_activity_at = NOW(),
                duration_seconds = $durationExpr
            WHERE id = :id AND status = 'active'
        ");
        $stmt->execute(['id' => $_SESSION['session_log_id']]);
    } catch (Exception $e) {}
}

function logSessionLogout() {
    if (empty($_SESSION['session_log_id'])) return;
    try {
        $pdo = getDB();
        $isPg = ($pdo->getAttribute(PDO::ATTR_DRIVER_NAME) === 'pgsql');
        $durationExpr = $isPg ? "EXTRACT(EPOCH FROM (NOW() - login_at))::int" : "TIMESTAMPDIFF(SECOND, login_at, NOW())";
        $stmt = $pdo->prepare("
            UPDATE user_session_logs 
            SET logout_at = NOW(),
                last_activity_at = NOW(),
                duration_seconds = $durationExpr,
                status = 'closed'
            WHERE id = :id
        ");
        $stmt->execute(['id' => $_SESSION['session_log_id']]);
        unset($_SESSION['session_log_id']);
    } catch (Exception $e) {}
}

// Registrar latido de actividad si hay sesión activa
if (isLoggedIn()) {
    updateSessionHeartbeat();
}

/**
 * GESTIÓN Y REORGANIZACIÓN DE BLOQUES SEMANALES Y OBJETIVOS DIDÁCTICOS
 */

function swapWeeklyBlocks($grade, $weekA, $weekB) {
    $weekA = (int)$weekA;
    $weekB = (int)$weekB;
    $grade = (int)$grade;
    if ($weekA < 1 || $weekB < 1 || $weekA === $weekB || !in_array($grade, [5, 6])) {
        return ['success' => false, 'message' => 'Parámetros inválidos para intercambiar semanas.'];
    }

    $pdo = getDB();
    $pdo->beginTransaction();
    try {
        $lapsoA = (int)ceil($weekA / 10);
        $lapsoB = (int)ceil($weekB / 10);
        $tempWeek = 9999;

        // 1. Mover semana A a temporal
        $stmtP1 = $pdo->prepare("UPDATE weekly_plans SET week_number = :t WHERE week_number = :a AND grade = :g");
        $stmtP1->execute(['t' => $tempWeek, 'a' => $weekA, 'g' => $grade]);

        $stmtA1 = $pdo->prepare("UPDATE activities SET week_number = :t WHERE week_number = :a AND grade = :g");
        $stmtA1->execute(['t' => $tempWeek, 'a' => $weekA, 'g' => $grade]);

        // 2. Mover semana B a semana A
        $stmtP2 = $pdo->prepare("UPDATE weekly_plans SET week_number = :a, lapso = :la WHERE week_number = :b AND grade = :g");
        $stmtP2->execute(['a' => $weekA, 'la' => $lapsoA, 'b' => $weekB, 'g' => $grade]);

        $stmtA2 = $pdo->prepare("UPDATE activities SET week_number = :a, lapso = :la WHERE week_number = :b AND grade = :g");
        $stmtA2->execute(['a' => $weekA, 'la' => $lapsoA, 'b' => $weekB, 'g' => $grade]);

        // 3. Mover temporal (ex semana A) a semana B
        $stmtP3 = $pdo->prepare("UPDATE weekly_plans SET week_number = :b, lapso = :lb WHERE week_number = :t AND grade = :g");
        $stmtP3->execute(['b' => $weekB, 'lb' => $lapsoB, 't' => $tempWeek, 'g' => $grade]);

        $stmtA3 = $pdo->prepare("UPDATE activities SET week_number = :b, lapso = :lb WHERE week_number = :t AND grade = :g");
        $stmtA3->execute(['b' => $weekB, 'lb' => $lapsoB, 't' => $tempWeek, 'g' => $grade]);

        $pdo->commit();
        $studentName = ($grade == 5) ? 'Dimas (5to Grado)' : 'Alexander (6to Grado)';
        return [
            'success' => true,
            'message' => "¡Bloques semanales intercambiados con éxito para $studentName! (Semana $weekA ⇄ Semana $weekB)."
        ];
    } catch (Exception $e) {
        $pdo->rollBack();
        return ['success' => false, 'message' => 'Error al intercambiar bloques: ' . $e->getMessage()];
    }
}

function shiftWeeklyBlocks($grade, $fromWeek, $toWeek) {
    $fromWeek = (int)$fromWeek;
    $toWeek = (int)$toWeek;
    $grade = (int)$grade;
    if ($fromWeek < 1 || $toWeek < 1 || !in_array($grade, [5, 6])) {
        return ['success' => false, 'message' => 'Parámetros inválidos para desplazar semanas.'];
    }
    if ($fromWeek === $toWeek) {
        return ['success' => true, 'message' => 'La semana de origen y destino son idénticas. No se realizaron cambios.'];
    }

    $pdo = getDB();
    $pdo->beginTransaction();
    try {
        $tempWeek = 9999;
        // 1. Mover fromWeek a temporal
        $pdo->prepare("UPDATE weekly_plans SET week_number = :t WHERE week_number = :f AND grade = :g")
            ->execute(['t' => $tempWeek, 'f' => $fromWeek, 'g' => $grade]);
        $pdo->prepare("UPDATE activities SET week_number = :t WHERE week_number = :f AND grade = :g")
            ->execute(['t' => $tempWeek, 'f' => $fromWeek, 'g' => $grade]);

        // 2. Desplazar semanas intermedias
        if ($fromWeek < $toWeek) {
            for ($w = $fromWeek + 1; $w <= $toWeek; $w++) {
                $target = $w - 1;
                $lapso = (int)ceil($target / 10);
                $pdo->prepare("UPDATE weekly_plans SET week_number = :target, lapso = :lapso WHERE week_number = :w AND grade = :g")
                    ->execute(['target' => $target, 'lapso' => $lapso, 'w' => $w, 'g' => $grade]);
                $pdo->prepare("UPDATE activities SET week_number = :target, lapso = :lapso WHERE week_number = :w AND grade = :g")
                    ->execute(['target' => $target, 'lapso' => $lapso, 'w' => $w, 'g' => $grade]);
            }
        } else {
            for ($w = $fromWeek - 1; $w >= $toWeek; $w--) {
                $target = $w + 1;
                $lapso = (int)ceil($target / 10);
                $pdo->prepare("UPDATE weekly_plans SET week_number = :target, lapso = :lapso WHERE week_number = :w AND grade = :g")
                    ->execute(['target' => $target, 'lapso' => $lapso, 'w' => $w, 'g' => $grade]);
                $pdo->prepare("UPDATE activities SET week_number = :target, lapso = :lapso WHERE week_number = :w AND grade = :g")
                    ->execute(['target' => $target, 'lapso' => $lapso, 'w' => $w, 'g' => $grade]);
            }
        }

        // 3. Ubicar temporal en semana destino
        $lapsoFinal = (int)ceil($toWeek / 10);
        $pdo->prepare("UPDATE weekly_plans SET week_number = :to, lapso = :lapso WHERE week_number = :t AND grade = :g")
            ->execute(['to' => $toWeek, 'lapso' => $lapsoFinal, 't' => $tempWeek, 'g' => $grade]);
        $pdo->prepare("UPDATE activities SET week_number = :to, lapso = :lapso WHERE week_number = :t AND grade = :g")
            ->execute(['to' => $toWeek, 'lapso' => $lapsoFinal, 't' => $tempWeek, 'g' => $grade]);

        $pdo->commit();
        $studentName = ($grade == 5) ? 'Dimas (5to Grado)' : 'Alexander (6to Grado)';
        return [
            'success' => true,
            'message' => "¡Bloque de la Semana $fromWeek trasladado a la Semana $toWeek con ajuste secuencial para $studentName!"
        ];
    } catch (Exception $e) {
        $pdo->rollBack();
        return ['success' => false, 'message' => 'Error al mover bloques: ' . $e->getMessage()];
    }
}

function moveActivityToWeekAndDay($activityId, $newWeek, $newDay, $newGrade = null) {
    $activityId = (int)$activityId;
    $newWeek = max(1, (int)$newWeek);
    $validDays = ['lunes', 'martes', 'miercoles', 'jueves', 'viernes'];
    if (!in_array($newDay, $validDays)) $newDay = 'lunes';
    $newLapso = (int)ceil($newWeek / 10);

    $pdo = getDB();
    try {
        $stmtAct = $pdo->prepare("SELECT * FROM activities WHERE id = :id");
        $stmtAct->execute(['id' => $activityId]);
        $act = $stmtAct->fetch();
        if (!$act) {
            return ['success' => false, 'message' => 'Actividad no encontrada.'];
        }

        $targetGrade = ($newGrade && in_array((int)$newGrade, [5, 6])) ? (int)$newGrade : (int)$act['grade'];

        // Actualizar actividad
        $stmtUpdate = $pdo->prepare("
            UPDATE activities 
            SET week_number = :w, lapso = :lapso, day_of_week = :d, grade = :g
            WHERE id = :id
        ");
        $stmtUpdate->execute([
            'w' => $newWeek,
            'lapso' => $newLapso,
            'd' => $newDay,
            'g' => $targetGrade,
            'id' => $activityId
        ]);

        // Asegurar que exista plan semanal para ese día y grado
        $stmtPlanCheck = $pdo->prepare("SELECT id FROM weekly_plans WHERE week_number = :w AND grade = :g AND day_of_week = :d");
        $stmtPlanCheck->execute(['w' => $newWeek, 'g' => $targetGrade, 'd' => $newDay]);
        if (!$stmtPlanCheck->fetchColumn()) {
            $stmtInsertPlan = $pdo->prepare("
                INSERT INTO weekly_plans (week_number, lapso, grade, day_of_week, objective, is_active)
                VALUES (:w, :lapso, :g, :d, :obj, 1)
            ");
            $stmtInsertPlan->execute([
                'w' => $newWeek,
                'lapso' => $newLapso,
                'g' => $targetGrade,
                'd' => $newDay,
                'obj' => 'Objetivo adaptado: ' . $act['title']
            ]);
        }

        return [
            'success' => true,
            'message' => "Actividad '{$act['title']}' movida exitosamente a Semana $newWeek (" . ucfirst($newDay) . ")."
        ];
    } catch (Exception $e) {
        return ['success' => false, 'message' => 'Error al mover actividad: ' . $e->getMessage()];
    }
}

function copyWeekAsReinforcement($grade, $sourceWeek, $targetWeek) {
    $grade = (int)$grade;
    $sourceWeek = (int)$sourceWeek;
    $targetWeek = (int)$targetWeek;
    if ($sourceWeek < 1 || $targetWeek < 1 || $sourceWeek === $targetWeek || !in_array($grade, [5, 6])) {
        return ['success' => false, 'message' => 'Parámetros inválidos.'];
    }

    $pdo = getDB();
    $pdo->beginTransaction();
    try {
        $targetLapso = (int)ceil($targetWeek / 10);

        // Copiar planes semanales
        $stmtPlans = $pdo->prepare("SELECT * FROM weekly_plans WHERE week_number = :w AND grade = :g");
        $stmtPlans->execute(['w' => $sourceWeek, 'g' => $grade]);
        $plans = $stmtPlans->fetchAll();

        $isPg = ($pdo->getAttribute(PDO::ATTR_DRIVER_NAME) === 'pgsql');
        $planInsertSql = $isPg
            ? "INSERT INTO weekly_plans (week_number, lapso, grade, day_of_week, objective, is_active)
               VALUES (:w, :lapso, :g, :d, :obj, 1)
               ON CONFLICT (week_number, grade, day_of_week) DO UPDATE SET objective = EXCLUDED.objective, is_active = 1"
            : "INSERT INTO weekly_plans (week_number, lapso, grade, day_of_week, objective, is_active)
               VALUES (:w, :lapso, :g, :d, :obj, 1)
               ON DUPLICATE KEY UPDATE objective = :obj, is_active = 1";

        $stmtInsertPlan = $pdo->prepare($planInsertSql);

        foreach ($plans as $p) {
            $stmtInsertPlan->execute([
                'w' => $targetWeek,
                'lapso' => $targetLapso,
                'g' => $grade,
                'd' => $p['day_of_week'],
                'obj' => '[Refuerzo] ' . $p['objective']
            ]);
        }

        // Copiar actividades y preguntas
        $stmtActs = $pdo->prepare("SELECT * FROM activities WHERE week_number = :w AND grade = :g");
        $stmtActs->execute(['w' => $sourceWeek, 'g' => $grade]);
        $activities = $stmtActs->fetchAll();

        $stmtInsertAct = $pdo->prepare("
            INSERT INTO activities (week_number, lapso, grade, day_of_week, subject_id, title, summary, reading_content, practical_exercise, xp_reward, order_num, is_active)
            VALUES (:w, :lapso, :g, :d, :sub, :title, :summary, :reading, :pract, :xp, :ord, 1)
        ");

        $stmtQs = $pdo->prepare("SELECT * FROM activity_questions WHERE activity_id = :aid");
        $stmtInsertQ = $pdo->prepare("
            INSERT INTO activity_questions (activity_id, question_text, question_type, options_json, correct_answer, explanation, points)
            VALUES (:aid, :text, :type, :opts, :corr, :expl, :pts)
        ");

        foreach ($activities as $act) {
            $stmtInsertAct->execute([
                'w' => $targetWeek,
                'lapso' => $targetLapso,
                'g' => $grade,
                'd' => $act['day_of_week'],
                'sub' => $act['subject_id'],
                'title' => '[Refuerzo] ' . $act['title'],
                'summary' => $act['summary'],
                'reading' => $act['reading_content'],
                'pract' => $act['practical_exercise'],
                'xp' => $act['xp_reward'],
                'ord' => $act['order_num']
            ]);
            $newActId = $pdo->lastInsertId();

            $stmtQs->execute(['aid' => $act['id']]);
            $questions = $stmtQs->fetchAll();
            foreach ($questions as $q) {
                $stmtInsertQ->execute([
                    'aid' => $newActId,
                    'text' => $q['question_text'],
                    'type' => $q['question_type'],
                    'opts' => $q['options_json'],
                    'corr' => $q['correct_answer'],
                    'expl' => $q['explanation'],
                    'pts' => $q['points']
                ]);
            }
        }

        $pdo->commit();
        $studentName = ($grade == 5) ? 'Dimas (5to Grado)' : 'Alexander (6to Grado)';
        return [
            'success' => true,
            'message' => "¡Bloque de la Semana $sourceWeek duplicado exitosamente como semana de refuerzo en Semana $targetWeek para $studentName!"
        ];
    } catch (Exception $e) {
        $pdo->rollBack();
        return ['success' => false, 'message' => 'Error al duplicar semana: ' . $e->getMessage()];
    }
}


