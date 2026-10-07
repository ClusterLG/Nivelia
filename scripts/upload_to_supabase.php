<?php
/**
 * Script Automatizado para Subir y Migrar el Proyecto a Supabase (PostgreSQL)
 * Plataforma: Nivelia
 */
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/supabase_config.php';

echo "=========================================================================\n";
echo "🚀 SUBIDA Y MIGRACIÓN AUTOMÁTICA A SUPABASE (POSTGRESQL) - NIVELIA\n";
echo "=========================================================================\n\n";

$sqlFile = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'supabase_schema_and_octubre_2026.sql';

if (!file_exists($sqlFile)) {
    die("❌ Error: No se encontró el archivo SQL en: $sqlFile\n");
}

echo "📄 Archivo SQL listo: " . basename($sqlFile) . " (" . number_format(filesize($sqlFile) / 1024, 2) . " KB)\n";

// Verificar credenciales de Supabase
$isConfigured = (
    defined('SUPABASE_HOST') && 
    strpos(SUPABASE_HOST, 'TU_PROYECTO') === false && 
    strpos(SUPABASE_PASS, 'TU_PASSWORD_AQUI') === false
);

if (!$isConfigured) {
    echo "\n⚠️ ATENCIÓN: Las credenciales de Supabase aún tienen los valores por defecto.\n";
    echo "Para conectarse automáticamente mediante este script, edita 'config/supabase_config.php':\n";
    echo "  - SUPABASE_HOST (ej: db.xyzabcdef.supabase.co o aws-0-us-east-1.pooler.supabase.com)\n";
    echo "  - SUPABASE_PASS (tu contraseña de la base de datos de Supabase)\n\n";
    echo "💡 O TAMBIÉN PUEDES CARGARLO DIRECTAMENTE EN LA WEB DE SUPABASE:\n";
    echo "  1. Entra a tu proyecto en: https://supabase.com/dashboard\n";
    echo "  2. En el menú izquierdo, haz clic en 'SQL Editor'\n";
    echo "  3. Abre y copia todo el contenido de 'supabase_schema_and_octubre_2026.sql'\n";
    echo "  4. Pégalo en el editor y haz clic en 'RUN' (Ejecutar)\n";
    echo "  5. ¡Listo! Todas las tablas, materias, alumnos y misiones de Octubre 2026 quedarán creadas.\n\n";
    exit(0);
}

echo "📡 Conectando a Supabase PostgreSQL en " . SUPABASE_HOST . "...\n";

try {
    $port = defined('SUPABASE_PORT') ? SUPABASE_PORT : '5432';
    $ssl = defined('SUPABASE_SSL') ? SUPABASE_SSL : 'require';
    $dsn = "pgsql:host=" . SUPABASE_HOST . ";port=" . $port . ";dbname=" . SUPABASE_DB . ";sslmode=" . $ssl;
    
    $pgPdo = new PDO($dsn, SUPABASE_USER, SUPABASE_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);

    echo "✅ Conexión exitosa con PostgreSQL de Supabase!\n";
    echo "⏳ Ejecutando migración de esquema y datos de Octubre 2026...\n";

    $sqlContent = file_get_contents($sqlFile);
    $pgPdo->exec($sqlContent);

    echo "🎉 ¡Migración completada con éxito en Supabase!\n\n";

    // Verificar tablas creadas
    echo "📊 Verificación de registros en Supabase:\n";
    $tables = [
        'users', 'subjects', 'weekly_plans', 'activities', 
        'activity_questions', 'student_submissions', 'submission_answers', 
        'achievements', 'student_achievements', 'material_bank', 
        'material_bank_questions', 'admin_notifications', 'system_settings',
        'user_access_requests'
    ];

    foreach ($tables as $t) {
        try {
            $cnt = $pgPdo->query("SELECT COUNT(*) FROM \"$t\"")->fetchColumn();
            echo "  ✓ $t: $cnt registros\n";
        } catch (Exception $e) {
            echo "  ⚠️ $t: " . $e->getMessage() . "\n";
        }
    }

    echo "\n✨ ¡El proyecto está 100% listo y desplegado en Supabase!\n";

} catch (PDOException $e) {
    echo "\n❌ Error al conectar o ejecutar en Supabase: " . $e->getMessage() . "\n\n";
    echo "💡 Puedes ejecutar el archivo 'supabase_schema_and_octubre_2026.sql' directamente en el SQL Editor de supabase.com\n";
}
