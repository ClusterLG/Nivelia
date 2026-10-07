<?php
/**
 * Exportador de Base de Datos para Supabase (PostgreSQL)
 * Exporta el esquema y los datos estrictamente del mes de Octubre de 2026
 */
require_once __DIR__ . '/../config/db.php';
$pdo = getDB();

$out = "-- =========================================================================\n";
$out .= "-- BASE DE DATOS NIVELIA (ESTUDIO EN CASA) - CONFIGURACIÓN SUPABASE (POSTGRESQL)\n";
$out .= "-- FECHA: Octubre 2026\n";
$out .= "-- Contenido Pedagógico Limitado Estrictamente al Mes de Octubre 2026 (Semanas 1 a 4)\n";
$out .= "-- =========================================================================\n\n";

// Extensiones útiles
$out .= "CREATE EXTENSION IF NOT EXISTS \"uuid-ossp\";\n";
$out .= "CREATE EXTENSION IF NOT EXISTS \"pgcrypto\";\n\n";

// Crear Tablas
$tables = [
    "users" => "CREATE TABLE IF NOT EXISTS users (
    id SERIAL PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role VARCHAR(20) NOT NULL DEFAULT 'student',
    grade INT DEFAULT NULL,
    avatar VARCHAR(50) DEFAULT 'kid1',
    total_xp INT DEFAULT 0,
    streak_days INT DEFAULT 1,
    created_at TIMESTAMP WITHOUT TIME ZONE DEFAULT NOW()
);",

    "subjects" => "CREATE TABLE IF NOT EXISTS subjects (
    id SERIAL PRIMARY KEY,
    slug VARCHAR(50) NOT NULL UNIQUE,
    name VARCHAR(100) NOT NULL,
    icon VARCHAR(50) NOT NULL,
    color VARCHAR(30) NOT NULL,
    badge VARCHAR(50) NOT NULL,
    description TEXT
);",

    "weekly_plans" => "CREATE TABLE IF NOT EXISTS weekly_plans (
    id SERIAL PRIMARY KEY,
    week_number INT NOT NULL DEFAULT 1,
    lapso SMALLINT NOT NULL DEFAULT 1,
    grade INT NOT NULL,
    day_of_week VARCHAR(20) NOT NULL,
    objective TEXT NOT NULL,
    is_active SMALLINT DEFAULT 1,
    created_at TIMESTAMP WITHOUT TIME ZONE DEFAULT NOW(),
    CONSTRAINT uq_week_grade_day UNIQUE (week_number, grade, day_of_week)
);",

    "activities" => "CREATE TABLE IF NOT EXISTS activities (
    id SERIAL PRIMARY KEY,
    week_number INT NOT NULL DEFAULT 1,
    lapso SMALLINT NOT NULL DEFAULT 1,
    grade INT NOT NULL,
    day_of_week VARCHAR(20) NOT NULL,
    subject_id INT NOT NULL REFERENCES subjects(id) ON DELETE CASCADE,
    title VARCHAR(255) NOT NULL,
    summary VARCHAR(255) NOT NULL,
    reading_content TEXT NOT NULL,
    practical_exercise TEXT NULL,
    xp_reward INT DEFAULT 50,
    order_num INT DEFAULT 1,
    is_active SMALLINT DEFAULT 1,
    created_at TIMESTAMP WITHOUT TIME ZONE DEFAULT NOW()
);",

    "activity_questions" => "CREATE TABLE IF NOT EXISTS activity_questions (
    id SERIAL PRIMARY KEY,
    activity_id INT NOT NULL REFERENCES activities(id) ON DELETE CASCADE,
    question_text TEXT NOT NULL,
    question_type VARCHAR(50) NOT NULL DEFAULT 'multiple_choice',
    options_json TEXT NULL,
    correct_answer TEXT NULL,
    explanation TEXT NULL,
    points INT DEFAULT 10
);",

    "student_submissions" => "CREATE TABLE IF NOT EXISTS student_submissions (
    id SERIAL PRIMARY KEY,
    user_id INT NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    activity_id INT NOT NULL REFERENCES activities(id) ON DELETE CASCADE,
    score INT DEFAULT 0,
    max_score INT DEFAULT 0,
    status VARCHAR(20) DEFAULT 'completed',
    parent_feedback TEXT NULL,
    parent_stars INT DEFAULT 0,
    submitted_at TIMESTAMP WITHOUT TIME ZONE DEFAULT NOW(),
    graded_at TIMESTAMP WITHOUT TIME ZONE NULL,
    CONSTRAINT uq_user_activity UNIQUE (user_id, activity_id)
);",

    "submission_answers" => "CREATE TABLE IF NOT EXISTS submission_answers (
    id SERIAL PRIMARY KEY,
    submission_id INT NOT NULL REFERENCES student_submissions(id) ON DELETE CASCADE,
    question_id INT NOT NULL REFERENCES activity_questions(id) ON DELETE CASCADE,
    answer_text TEXT NOT NULL,
    is_correct SMALLINT NULL,
    points_awarded INT DEFAULT 0
);",

    "achievements" => "CREATE TABLE IF NOT EXISTS achievements (
    id SERIAL PRIMARY KEY,
    slug VARCHAR(50) NOT NULL UNIQUE,
    name VARCHAR(100) NOT NULL,
    description VARCHAR(255) NOT NULL,
    icon VARCHAR(50) NOT NULL,
    xp_bonus INT DEFAULT 100
);",

    "student_achievements" => "CREATE TABLE IF NOT EXISTS student_achievements (
    id SERIAL PRIMARY KEY,
    user_id INT NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    achievement_id INT NOT NULL REFERENCES achievements(id) ON DELETE CASCADE,
    unlocked_at TIMESTAMP WITHOUT TIME ZONE DEFAULT NOW(),
    CONSTRAINT uq_user_achieve UNIQUE (user_id, achievement_id)
);",

    "material_bank" => "CREATE TABLE IF NOT EXISTS material_bank (
    id SERIAL PRIMARY KEY,
    grade INT NOT NULL,
    lapso SMALLINT NOT NULL,
    lapso_week INT NOT NULL,
    absolute_week INT NOT NULL,
    day_of_week VARCHAR(20) NOT NULL,
    subject_id INT NOT NULL REFERENCES subjects(id) ON DELETE CASCADE,
    title VARCHAR(255) NOT NULL,
    summary VARCHAR(255) NOT NULL,
    daily_objective TEXT NOT NULL,
    reading_content TEXT NOT NULL,
    practical_exercise TEXT NULL,
    xp_reward INT DEFAULT 50,
    order_num INT DEFAULT 1,
    is_deployed SMALLINT DEFAULT 1,
    created_at TIMESTAMP WITHOUT TIME ZONE DEFAULT NOW(),
    CONSTRAINT uq_bank_grade_week_day_order UNIQUE (grade, absolute_week, day_of_week, order_num)
);",

    "material_bank_questions" => "CREATE TABLE IF NOT EXISTS material_bank_questions (
    id SERIAL PRIMARY KEY,
    material_id INT NOT NULL REFERENCES material_bank(id) ON DELETE CASCADE,
    question_text TEXT NOT NULL,
    question_type VARCHAR(50) NOT NULL DEFAULT 'multiple_choice',
    options_json TEXT NULL,
    correct_answer TEXT NOT NULL,
    explanation TEXT NULL,
    points INT DEFAULT 10
);",

    "admin_notifications" => "CREATE TABLE IF NOT EXISTS admin_notifications (
    id SERIAL PRIMARY KEY,
    user_id INT NOT NULL,
    type VARCHAR(50) NOT NULL,
    title VARCHAR(255) NOT NULL,
    message TEXT NOT NULL,
    link VARCHAR(255) NULL,
    is_read SMALLINT DEFAULT 0,
    created_at TIMESTAMP WITHOUT TIME ZONE DEFAULT NOW()
);",

    "user_session_logs" => "CREATE TABLE IF NOT EXISTS user_session_logs (
    id SERIAL PRIMARY KEY,
    user_id INT NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    session_token VARCHAR(64) NOT NULL,
    ip_address VARCHAR(45) NOT NULL,
    user_agent VARCHAR(255) NOT NULL,
    login_at TIMESTAMP WITHOUT TIME ZONE DEFAULT NOW(),
    last_activity_at TIMESTAMP WITHOUT TIME ZONE DEFAULT NOW(),
    duration_seconds INT DEFAULT 0,
    status VARCHAR(20) DEFAULT 'active'
);",

    "system_settings" => "CREATE TABLE IF NOT EXISTS system_settings (
    setting_key VARCHAR(100) PRIMARY KEY,
    setting_value TEXT NOT NULL,
    updated_at TIMESTAMP WITHOUT TIME ZONE DEFAULT NOW()
);",

    "user_access_requests" => "CREATE TABLE IF NOT EXISTS user_access_requests (
    id SERIAL PRIMARY KEY,
    student_name VARCHAR(120) NOT NULL,
    guardian_name VARCHAR(120) NULL,
    email VARCHAR(120) NOT NULL,
    whatsapp VARCHAR(50) NOT NULL,
    grade INT NOT NULL,
    message TEXT NULL,
    status VARCHAR(20) NOT NULL DEFAULT 'pending',
    created_at TIMESTAMP WITHOUT TIME ZONE DEFAULT NOW(),
    reviewed_at TIMESTAMP WITHOUT TIME ZONE NULL
);"
];

foreach ($tables as $tName => $sql) {
    $out .= "-- Tabla: $tName\n" . $sql . "\n\n";
}

// Función helper para escapar valores en SQL Postgres
function pgEscape($val) {
    if ($val === null) return 'NULL';
    if (is_int($val) || is_float($val)) return (string)$val;
    $val = str_replace("'", "''", (string)$val);
    return "'" . $val . "'";
}

// Función para exportar datos de una tabla
function exportTableData($pdo, $table, $filter = '') {
    $sql = "SELECT * FROM `$table` $filter";
    $stmt = $pdo->query($sql);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    if (empty($rows)) return "";

    $res = "-- Datos para tabla: $table (" . count($rows) . " registros)\n";
    $columns = array_keys($rows[0]);
    $colsList = implode(', ', array_map(function($c) { return '"' . $c . '"'; }, $columns));

    foreach ($rows as $row) {
        $vals = [];
        foreach ($columns as $c) {
            $vals[] = pgEscape($row[$c]);
        }
        $res .= "INSERT INTO \"$table\" ($colsList) VALUES (" . implode(', ', $vals) . ");\n";
    }

    // Ajustar secuencia si tiene columna id
    if (in_array('id', $columns)) {
        $res .= "SELECT setval(pg_get_serial_sequence('\"$table\"', 'id'), COALESCE((SELECT MAX(id) FROM \"$table\"), 1));\n";
    }
    $res .= "\n";
    return $res;
}

// 1. Materias (subjects)
$out .= exportTableData($pdo, 'subjects');

// 2. Usuarios base (users)
$out .= exportTableData($pdo, 'users');

// 3. Logros / Medallas (achievements)
$out .= exportTableData($pdo, 'achievements');

// 4. Planes semanales (solo semanas 1 a 4 = Octubre 2026)
$out .= exportTableData($pdo, 'weekly_plans', 'WHERE week_number <= 4 ORDER BY week_number ASC, grade ASC');

// 5. Actividades (solo semanas 1 a 4 = Octubre 2026)
$out .= exportTableData($pdo, 'activities', 'WHERE week_number <= 4 ORDER BY week_number ASC, grade ASC');

// 6. Preguntas de actividades (solo para actividades existentes <= 4)
$actIds = $pdo->query("SELECT id FROM activities WHERE week_number <= 4")->fetchAll(PDO::FETCH_COLUMN);
if (!empty($actIds)) {
    $inActs = implode(',', $actIds);
    $out .= exportTableData($pdo, 'activity_questions', "WHERE activity_id IN ($inActs) ORDER BY id ASC");
}

// 7. Entregas de estudiantes
$out .= exportTableData($pdo, 'student_submissions');

// 8. Respuestas de entregas
$out .= exportTableData($pdo, 'submission_answers');

// 9. Medallas desbloqueadas por estudiantes
$out .= exportTableData($pdo, 'student_achievements');

// 10. Banco de Materiales (solo semanas 1 a 4 = Octubre 2026)
$out .= exportTableData($pdo, 'material_bank', 'WHERE absolute_week <= 4 ORDER BY absolute_week ASC, grade ASC');

// 11. Preguntas del banco de materiales
$mbIds = $pdo->query("SELECT id FROM material_bank WHERE absolute_week <= 4")->fetchAll(PDO::FETCH_COLUMN);
if (!empty($mbIds)) {
    $inMB = implode(',', $mbIds);
    $out .= exportTableData($pdo, 'material_bank_questions', "WHERE material_id IN ($inMB) ORDER BY id ASC");
}

// 12. Configuraciones del sistema
$out .= exportTableData($pdo, 'system_settings');

// 13. Solicitudes de acceso
$out .= exportTableData($pdo, 'user_access_requests');

// 14. Habilitar Row Level Security (RLS) opcional o políticas permisivas para que la app funcione sin bloqueos
$out .= "-- =========================================================================\n";
$out .= "-- POLÍTICAS DE ACCESO PÚBLICO / ACCESO A LA APLICACIÓN\n";
$out .= "-- Permite que la API de Supabase y la aplicación PHP lean y escriban sin trabas\n";
$out .= "-- =========================================================================\n\n";
foreach (array_keys($tables) as $t) {
    $out .= "ALTER TABLE \"$t\" ENABLE ROW LEVEL SECURITY;\n";
    $out .= "DO $$ BEGIN\n";
    $out .= "  IF NOT EXISTS (SELECT 1 FROM pg_policies WHERE tablename = '$t' AND policyname = 'allow_all_$t') THEN\n";
    $out .= "    CREATE POLICY allow_all_$t ON \"$t\" FOR ALL USING (true) WITH CHECK (true);\n";
    $out .= "  END IF;\n";
    $out .= "END $$;\n\n";
}

$targetFile = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'supabase_schema_and_octubre_2026.sql';
file_put_contents($targetFile, $out);
echo "Archivo SQL generado con éxito en: $targetFile\nTamaño: " . strlen($out) . " bytes\n";
