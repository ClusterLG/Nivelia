<?php
/**
 * Configuración de Conexión a Supabase (PostgreSQL en la Nube)
 * 
 * Instrucciones para activar Supabase:
 * 1. Crea tu proyecto en https://supabase.com/dashboard
 * 2. En tu proyecto, ve a: Project Settings -> Database
 * 3. En "Connection parameters", copia tus credenciales:
 *    - Host: db.[tu-proyecto].supabase.co (o pooler: aws-0-[region].pooler.supabase.com)
 *    - Port: 5432 (o 6543)
 *    - User: postgres (o postgres.[tu-proyecto])
 *    - Database: postgres
 *    - Password: tu contraseña definida al crear el proyecto
 * 4. Pega los datos abajo y cambia USE_SUPABASE a true.
 */

// Activa Supabase (true = Conectar a Supabase en la nube / false = MySQL local en XAMPP)
define('USE_SUPABASE', getenv('USE_SUPABASE') !== false ? (getenv('USE_SUPABASE') === 'true') : false);

// Credenciales de conexión a la Base de Datos PostgreSQL de Supabase
define('SUPABASE_HOST', getenv('SUPABASE_HOST') ?: 'db.TU_PROYECTO.supabase.co');
define('SUPABASE_PORT', getenv('SUPABASE_PORT') ?: '5432');
define('SUPABASE_DB',   getenv('SUPABASE_DB')   ?: 'postgres');
define('SUPABASE_USER', getenv('SUPABASE_USER') ?: 'postgres');
define('SUPABASE_PASS', getenv('SUPABASE_PASS') ?: 'TU_PASSWORD_AQUI');
define('SUPABASE_SSL',  getenv('SUPABASE_SSL')  ?: 'require');

// API y Llaves de Supabase (Para cliente REST o llamadas directas)
define('SUPABASE_URL',      getenv('SUPABASE_URL')      ?: 'https://TU_PROYECTO.supabase.co');
define('SUPABASE_ANON_KEY', getenv('SUPABASE_ANON_KEY') ?: 'TU_ANON_KEY_AQUI');
