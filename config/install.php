<?php
/**
 * Instalador y Sembrador de Base de Datos
 * Crea las tablas y siembra todo el contenido de la Semana 1
 */

require_once __DIR__ . '/db.php';

$pdo = getDB();

echo "<pre style='font-family:sans-serif;background:#f0f4f8;padding:20px;border-radius:8px;'>";
echo "🚀 Iniciando instalación de 'Nivelia'...\n";

// 1. Creación de Tablas
$tables = [
    "CREATE TABLE IF NOT EXISTS users (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(100) NOT NULL,
        email VARCHAR(100) NOT NULL UNIQUE,
        password VARCHAR(255) NOT NULL,
        role ENUM('admin', 'student') NOT NULL DEFAULT 'student',
        grade INT DEFAULT NULL,
        avatar VARCHAR(50) DEFAULT 'kid1',
        total_xp INT DEFAULT 0,
        streak_days INT DEFAULT 1,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;",

    "CREATE TABLE IF NOT EXISTS subjects (
        id INT AUTO_INCREMENT PRIMARY KEY,
        slug VARCHAR(50) NOT NULL UNIQUE,
        name VARCHAR(100) NOT NULL,
        icon VARCHAR(50) NOT NULL,
        color VARCHAR(30) NOT NULL,
        badge VARCHAR(50) NOT NULL,
        description TEXT
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;",

    "CREATE TABLE IF NOT EXISTS weekly_plans (
        id INT AUTO_INCREMENT PRIMARY KEY,
        week_number INT NOT NULL DEFAULT 1,
        grade INT NOT NULL,
        day_of_week ENUM('lunes', 'martes', 'miercoles', 'jueves', 'viernes', 'sabado', 'domingo') NOT NULL,
        objective TEXT NOT NULL,
        is_active TINYINT(1) DEFAULT 1,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY uq_week_grade_day (week_number, grade, day_of_week)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;",

    "CREATE TABLE IF NOT EXISTS activities (
        id INT AUTO_INCREMENT PRIMARY KEY,
        week_number INT NOT NULL DEFAULT 1,
        grade INT NOT NULL,
        day_of_week ENUM('lunes', 'martes', 'miercoles', 'jueves', 'viernes', 'sabado', 'domingo') NOT NULL,
        subject_id INT NOT NULL,
        title VARCHAR(255) NOT NULL,
        summary VARCHAR(255) NOT NULL,
        reading_content LONGTEXT NOT NULL,
        practical_exercise TEXT NULL,
        xp_reward INT DEFAULT 50,
        order_num INT DEFAULT 1,
        is_active TINYINT(1) DEFAULT 1,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (subject_id) REFERENCES subjects(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;",

    "CREATE TABLE IF NOT EXISTS activity_questions (
        id INT AUTO_INCREMENT PRIMARY KEY,
        activity_id INT NOT NULL,
        question_text TEXT NOT NULL,
        question_type ENUM('multiple_choice', 'true_false', 'short_answer', 'reflexive') NOT NULL DEFAULT 'multiple_choice',
        options_json JSON NULL,
        correct_answer TEXT NULL,
        explanation TEXT NULL,
        points INT DEFAULT 10,
        FOREIGN KEY (activity_id) REFERENCES activities(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;",

    "CREATE TABLE IF NOT EXISTS student_submissions (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        activity_id INT NOT NULL,
        score INT DEFAULT 0,
        max_score INT DEFAULT 0,
        status ENUM('completed', 'reviewed') DEFAULT 'completed',
        parent_feedback TEXT NULL,
        parent_stars INT DEFAULT 0,
        submitted_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        graded_at DATETIME NULL,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
        FOREIGN KEY (activity_id) REFERENCES activities(id) ON DELETE CASCADE,
        UNIQUE KEY uq_user_activity (user_id, activity_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;",

    "CREATE TABLE IF NOT EXISTS submission_answers (
        id INT AUTO_INCREMENT PRIMARY KEY,
        submission_id INT NOT NULL,
        question_id INT NOT NULL,
        answer_text TEXT NOT NULL,
        is_correct TINYINT(1) NULL,
        points_awarded INT DEFAULT 0,
        FOREIGN KEY (submission_id) REFERENCES student_submissions(id) ON DELETE CASCADE,
        FOREIGN KEY (question_id) REFERENCES activity_questions(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;",

    "CREATE TABLE IF NOT EXISTS achievements (
        id INT AUTO_INCREMENT PRIMARY KEY,
        slug VARCHAR(50) NOT NULL UNIQUE,
        name VARCHAR(100) NOT NULL,
        description VARCHAR(255) NOT NULL,
        icon VARCHAR(50) NOT NULL,
        xp_bonus INT DEFAULT 100
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;",

    "CREATE TABLE IF NOT EXISTS student_achievements (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        achievement_id INT NOT NULL,
        unlocked_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
        FOREIGN KEY (achievement_id) REFERENCES achievements(id) ON DELETE CASCADE,
        UNIQUE KEY uq_user_achieve (user_id, achievement_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;"
];

foreach ($tables as $sql) {
    $pdo->exec($sql);
}
echo "✅ Estructura de tablas verificada y creada correctamente.\n";

// 2. Insertar Materias
$subjectsData = [
    [
        'slug' => 'lectura',
        'name' => 'Lectura y Lengua',
        'icon' => '📖',
        'color' => '#3b82f6',
        'badge' => 'Lector Estrella',
        'description' => 'Comprensión lectora, análisis de textos, redacción y literatura.'
    ],
    [
        'slug' => 'matematicas',
        'name' => 'Matemáticas',
        'icon' => '🔢',
        'color' => '#10b981',
        'badge' => 'Genio Matemático',
        'description' => 'Números decimales, fracciones, porcentajes y problemas cotidianos.'
    ],
    [
        'slug' => 'algebra',
        'name' => 'Álgebra y Lógica',
        'icon' => '🧩',
        'color' => '#8b5cf6',
        'badge' => 'Mente Lógica',
        'description' => 'Ecuaciones visuales, incógnitas, balanzas y lenguaje algebraico.'
    ],
    [
        'slug' => 'historia',
        'name' => 'Historia de Venezuela',
        'icon' => '🇻🇪',
        'color' => '#f59e0b',
        'badge' => 'Historiador Patriota',
        'description' => 'Nuestras raíces, próceres, batallas y evolución de la nación.'
    ],
    [
        'slug' => 'ciencias',
        'name' => 'Conocimiento General y Ciencias',
        'icon' => '🔬',
        'color' => '#06b6d4',
        'badge' => 'Pequeño Científico',
        'description' => 'La Tierra, el universo, la materia, el cuerpo humano y la naturaleza.'
    ],
    [
        'slug' => 'riesgo_laguaira',
        'name' => 'Defensores de La Guaira (Gestión de Riesgos)',
        'icon' => '🛡️',
        'color' => '#ef4444',
        'badge' => 'Guardián de La Guaira',
        'description' => 'Preparación ante sismos, aludes, deslizamientos, crecidas de ríos y tsunamis en La Guaira.'
    ],
    [
        'slug' => 'biologia',
        'name' => 'Biología y Ecosistemas',
        'icon' => '🌿',
        'color' => '#16a34a',
        'badge' => 'Biólogo Explorador',
        'description' => 'Los seres vivos, las plantas, animales, el cuerpo humano y la biodiversidad venezolana.'
    ],
    [
        'slug' => 'ciudadania',
        'name' => 'Formación Ciudadana y Valores',
        'icon' => '🤝',
        'color' => '#0284c7',
        'badge' => 'Ciudadano Ejemplar',
        'description' => 'Valores de convivencia, empatía, respeto familiar, derechos y deberes cívicos.'
    ],
];

$stmtSub = $pdo->prepare("INSERT INTO subjects (slug, name, icon, color, badge, description) 
    VALUES (:slug, :name, :icon, :color, :badge, :description)
    ON DUPLICATE KEY UPDATE name = VALUES(name), icon = VALUES(icon), color = VALUES(color), badge = VALUES(badge), description = VALUES(description)");

foreach ($subjectsData as $s) {
    $stmtSub->execute($s);
}
echo "✅ Materias registradas con éxito (incluyendo Defensores de La Guaira).\n";

// Obtener IDs de las materias para vincular
$subjMap = [];
foreach ($pdo->query("SELECT id, slug FROM subjects")->fetchAll() as $row) {
    $subjMap[$row['slug']] = $row['id'];
}

// 3. Insertar Usuarios Iniciales con Contraseñas Distintas
$usersData = [
    [
        'name' => 'Papá Administrador',
        'email' => 'papa@estudio.local',
        'password' => password_hash('papa123', PASSWORD_BCRYPT),
        'role' => 'admin',
        'grade' => null,
        'avatar' => '👨‍🏫',
        'total_xp' => 1500
    ],
    [
        'name' => 'Alexander',
        'email' => 'alexander@estudio.local',
        'password' => password_hash('alex123', PASSWORD_BCRYPT),
        'role' => 'student',
        'grade' => 6,
        'avatar' => '🚀',
        'total_xp' => 120
    ],
    [
        'name' => 'Dimas',
        'email' => 'moralesponcedimasadrian@gmail.com',
        'password' => password_hash('adrian3108', PASSWORD_BCRYPT),
        'role' => 'student',
        'grade' => 5,
        'avatar' => '⚡',
        'total_xp' => 120
    ],
];

$stmtUser = $pdo->prepare("INSERT INTO users (name, email, password, role, grade, avatar, total_xp) 
    VALUES (:name, :email, :password, :role, :grade, :avatar, :total_xp)
    ON DUPLICATE KEY UPDATE name = VALUES(name), role = VALUES(role), grade = VALUES(grade), avatar = VALUES(avatar)");

foreach ($usersData as $u) {
    $stmtUser->execute($u);
}
echo "✅ Usuarios base creados: Papá (Admin), Alexander (6to Grado) y Dimas (5to Grado).\n";

// 4. Insertar Medallas / Logros
$achievementsData = [
    ['slug' => 'primer_paso', 'name' => 'Primer Gran Paso', 'description' => 'Completaste tu primera actividad en la plataforma.', 'icon' => '🌟', 'xp_bonus' => 50],
    ['slug' => 'guardian_guaira', 'name' => 'Guardián de La Guaira', 'description' => 'Aprendiste los protocolos de seguridad y prevención costera.', 'icon' => '🛡️', 'xp_bonus' => 150],
    ['slug' => 'cerebro_matematico', 'name' => 'Cerebro Matemático', 'description' => 'Resolviste desafíos con números y fracciones sin dudar.', 'icon' => '🧮', 'xp_bonus' => 100],
    ['slug' => 'ojo_critico', 'name' => 'Lector Sagaz', 'description' => 'Comprendiste a la perfección una lectura reflexiva.', 'icon' => '📚', 'xp_bonus' => 100],
    ['slug' => 'mente_logica', 'name' => 'Mago de las Ecuaciones', 'description' => 'Descubriste el valor de las incógnitas secretas.', 'icon' => '🔍', 'xp_bonus' => 100],
    ['slug' => 'semana_completa', 'name' => 'Semana de Campeón', 'description' => 'Completaste todas las misiones planificadas de la semana.', 'icon' => '🏆', 'xp_bonus' => 250],
    ['slug' => 'guardian_biodiversidad', 'name' => 'Guardián de la Biodiversidad', 'description' => 'Completaste tu primera misión de Biología explorando los secretos de los seres vivos.', 'icon' => '🌿', 'xp_bonus' => 120],
    ['slug' => 'ciudadano_ejemplar', 'name' => 'Corazón Ciudadano', 'description' => 'Demostraste comprensión y compromiso con los valores de convivencia y respeto.', 'icon' => '🤝', 'xp_bonus' => 120],
];

$stmtAch = $pdo->prepare("INSERT INTO achievements (slug, name, description, icon, xp_bonus)
    VALUES (:slug, :name, :description, :icon, :xp_bonus)
    ON DUPLICATE KEY UPDATE name = VALUES(name), description = VALUES(description), icon = VALUES(icon)");

foreach ($achievementsData as $ach) {
    $stmtAch->execute($ach);
}
echo "✅ Medallas y logros del sistema registrados.\n";

// 5. Cargar Plan Semanal y Banco de Actividades de la Semana 1
// Limpiamos actividades anteriores de la semana 1 para asegurar contenido fresco y ordenado
$pdo->exec("DELETE FROM weekly_plans WHERE week_number = 1");
$pdo->exec("DELETE FROM activities WHERE week_number = 1");

// PLANES SEMANALES (Objetivos para la tabla del Padre)
$plansData = [
    // 5to Grado - Dimas
    ['week_number' => 1, 'grade' => 5, 'day_of_week' => 'lunes', 'objective' => 'Desarrollar comprensión de textos míticos pemones, operaciones con decimales y conocer la geografía singular de La Guaira entre el mar y el Ávila.'],
    ['week_number' => 1, 'grade' => 5, 'day_of_week' => 'martes', 'objective' => 'Iniciarse en el pensamiento algebraico mediante balanzas visuales, conocer los primeros habitantes de Venezuela y experimentar con los tres estados de la materia.'],
    ['week_number' => 1, 'grade' => 5, 'day_of_week' => 'miercoles', 'objective' => 'Comprender la multiplicación rápida de decimales, alertas tempranas en ríos del Waraira Repano y descubrir la fotosíntesis como fábrica biológica de oxígeno.'],
    ['week_number' => 1, 'grade' => 5, 'day_of_week' => 'jueves', 'objective' => 'Descubrir el número desconocido en ecuaciones aditivas, honrar la conspiración patriota de Gual y España, y cultivar la empatía y la convivencia ciudadana.'],
    ['week_number' => 1, 'grade' => 5, 'day_of_week' => 'viernes', 'objective' => 'Armar la Mochila de las 72 Horas, resolver el Gran Desafío Matemático Semanal y explorar las cadenas tróficas y la fauna emblemática de Venezuela.'],

    // 6to Grado - Alexander
    ['week_number' => 1, 'grade' => 6, 'day_of_week' => 'lunes', 'objective' => 'Analizar la historia de las telecomunicaciones en el puerto de La Guaira, dominar el mínimo común múltiplo (m.c.m.) en fracciones y estudiar la Falla de San Sebastián y la tectónica de placas costera.'],
    ['week_number' => 1, 'grade' => 6, 'day_of_week' => 'martes', 'objective' => 'Traducir frases en español al lenguaje algebraico formal, examinar los acontecimientos y debate patriota del 19 de Abril de 1810 y explorar la estructura microscópica de la célula.'],
    ['week_number' => 1, 'grade' => 6, 'day_of_week' => 'miercoles', 'objective' => 'Dominar cálculo de porcentajes y descuentos prácticos, analizar aludes torrenciales y flujos de detritos, y clasificar los reinos biológicos en la megabiodiversidad de Venezuela.'],
    ['week_number' => 1, 'grade' => 6, 'day_of_week' => 'jueves', 'objective' => 'Resolver ecuaciones lineales de primer grado, estudiar la estrategia de la Batalla de Carabobo (1821), y analizar la relación entre derechos humanos y deberes cívicos.'],
    ['week_number' => 1, 'grade' => 6, 'day_of_week' => 'viernes', 'objective' => 'Comprender el origen geológico de sismos marinos y tsunamis en el litoral, aplicar la regla de tres simple, y analizar el funcionamiento del sistema inmunológico humano.']
];

$stmtPlan = $pdo->prepare("INSERT INTO weekly_plans (week_number, grade, day_of_week, objective, is_active)
    VALUES (:week_number, :grade, :day_of_week, :objective, 1)");

foreach ($plansData as $plan) {
    $stmtPlan->execute($plan);
}
echo "✅ Objetivos semanales para 5to y 6to grado insertados en el Planificador.\n";

// FUNCION HELPER PARA INSERTAR ACTIVIDAD Y PREGUNTAS
function insertActivityWithQuestions($pdo, $actData, $questionsData) {
    $stmt = $pdo->prepare("INSERT INTO activities 
        (week_number, grade, day_of_week, subject_id, title, summary, reading_content, practical_exercise, xp_reward, order_num, is_active)
        VALUES (:week_number, :grade, :day_of_week, :subject_id, :title, :summary, :reading_content, :practical_exercise, :xp_reward, :order_num, 1)");
    $stmt->execute($actData);
    $activityId = $pdo->lastInsertId();

    $stmtQ = $pdo->prepare("INSERT INTO activity_questions 
        (activity_id, question_text, question_type, options_json, correct_answer, explanation, points)
        VALUES (:activity_id, :question_text, :question_type, :options_json, :correct_answer, :explanation, :points)");

    foreach ($questionsData as $q) {
        $optionsJson = null;
        if (isset($q['options']) && is_array($q['options'])) {
            $opts = $q['options'];
            if (($q['question_type'] ?? '') === 'multiple_choice') {
                shuffle($opts);
            }
            $optionsJson = json_encode($opts, JSON_UNESCAPED_UNICODE);
        }
        $stmtQ->execute([
            'activity_id' => $activityId,
            'question_text' => $q['question_text'],
            'question_type' => $q['question_type'],
            'options_json' => $optionsJson,
            'correct_answer' => $q['correct_answer'],
            'explanation' => $q['explanation'] ?? '',
            'points' => $q['points'] ?? 10
        ]);
    }
}

// =========================================================================
// ACTIVIDADES 5TO GRADO (DIMAS - 10 AÑOS)
// =========================================================================

// --- LUNES 5TO ---
// 1. Lectura
insertActivityWithQuestions($pdo, [
    'week_number' => 1,
    'grade' => 5,
    'day_of_week' => 'lunes',
    'subject_id' => $subjMap['lectura'],
    'title' => 'El Salto Ángel y el Secreto de los Tepuyes',
    'summary' => 'Descubre la magia del Churún Merú y la sabiduría ancestral del pueblo Pemón.',
    'reading_content' => "En el corazón del estado Bolívar se alza el Auyantepuy, una colosal montaña de roca plana que los indígenas Pemones consideran sagrada. Desde su cumbre desciende el Salto Ángel (conocido en lengua indígena como Kerepakupai Vená o Churún Merú), la caída de agua ininterrumpida más alta del planeta Tierra, con casi un kilómetro de altura libre (979 metros).\n\nPara los antiguos pemones, estas majestuosas mesetas no son montañas comunes: son guardianes del tiempo que tocan el cielo. Cuentan que cuando el agua cae y se transforma en una neblina dorada al chocar con el viento, la selva canta agradeciendo la vida que nutre a los ríos Churún y Caroní.\n\nComprender este texto nos enseña que Venezuela posee maravillas geográficas únicas en el mundo y que nuestros pueblos originarios tenían una relación de profundo respeto y cuidado hacia la naturaleza.",
    'practical_exercise' => 'Identifica la idea principal del texto y explica por qué los pemones consideraban sagrado al Auyantepuy.',
    'xp_reward' => 50,
    'order_num' => 1
], [
    [
        'question_text' => '¿Cuál es el nombre del Salto Ángel en lengua indígena pemón según el texto?',
        'question_type' => 'multiple_choice',
        'options' => ['Kerepakupai Vená', 'Waraira Repano', 'Auyantepuy Mayor', 'Orinoco Dorado'],
        'correct_answer' => 'Kerepakupai Vená',
        'explanation' => 'Excelente. Los pemones llaman a esta gran catarata Kerepakupai Vená o Churún Merú.'
    ],
    [
        'question_text' => '¿Aproximadamente cuántos metros de altura tiene la caída del Salto Ángel?',
        'question_type' => 'multiple_choice',
        'options' => ['500 metros', '979 metros', '2.000 metros', '150 metros'],
        'correct_answer' => '979 metros',
        'explanation' => '¡Muy bien! Son 979 metros de caída, lo que la convierte en la más alta del mundo.'
    ],
    [
        'question_text' => '¿Qué enseñanza sobre el cuidado de la naturaleza nos deja el pueblo Pemón?',
        'question_type' => 'reflexive',
        'correct_answer' => '',
        'explanation' => 'Escribe con tus propias palabras qué aprendes del respeto hacia la selva y las aguas.'
    ]
]);

// 2. Matemáticas
insertActivityWithQuestions($pdo, [
    'week_number' => 1,
    'grade' => 5,
    'day_of_week' => 'lunes',
    'subject_id' => $subjMap['matematicas'],
    'title' => 'Los Decimales en las Compras Cotidianas',
    'summary' => 'Aprende a sumar y restar números decimales resolviendo compras prácticas.',
    'reading_content' => "Los números decimales están presentes todos los días: cuando medimos nuestra estatura (1,45 m), cuando compramos frutas en kilogramos (2,5 kg) o al pagar productos.\n\nUn número decimal tiene dos partes separadas por una coma:\n• La parte entera (a la izquierda de la coma): 12 unidades.\n• La parte decimal (a la derecha de la coma): décimas, centésimas y milésimas (ej: 0,75).\n\nREGLA DE ORO PARA SUMAR Y RESTAR DECIMALES:\nPara sumar o restar, coloca siempre la coma debajo de la coma, alineando unidades con unidades, décimas con décimas y centésimas con centésimas.\n\nEjemplo:\n  1 5 , 4 0  (Kilo de queso)\n+  8 , 2 5  (Litro de leche)\n──────────\n  2 3 , 6 5  en total.",
    'practical_exercise' => 'Si compras un cuaderno por 4,50 y un lápiz por 1,75, ¿cuánto gastas en total? ¿Cuánto te sobra si pagas con un billete de 10?',
    'xp_reward' => 50,
    'order_num' => 2
], [
    [
        'question_text' => '¿Cuál es el resultado exacto de sumar 14,30 + 5,25?',
        'question_type' => 'multiple_choice',
        'options' => ['19,55', '19,05', '20,55', '18,50'],
        'correct_answer' => '19,55',
        'explanation' => '¡Correcto! 14 + 5 = 19 y 0,30 + 0,25 = 0,55, sumando 19,55.'
    ],
    [
        'question_text' => 'Si tienes 20,00 y compras frutas por 12,50, ¿cuánto te queda de cambio?',
        'question_type' => 'multiple_choice',
        'options' => ['8,50', '7,50', '7,00', '8,00'],
        'correct_answer' => '7,50',
        'explanation' => '¡Exacto! 20,00 - 12,50 = 7,50.'
    ]
]);

// 3. Gestión de Riesgos La Guaira
insertActivityWithQuestions($pdo, [
    'week_number' => 1,
    'grade' => 5,
    'day_of_week' => 'lunes',
    'subject_id' => $subjMap['riesgo_laguaira'],
    'title' => 'Nuestra Guaira: Entre el Mar y la Montaña',
    'summary' => 'Comprende la geografía especial de nuestro estado y por qué debemos estar preparados.',
    'reading_content' => "El estado La Guaira tiene una de las geografías más hermosas y singulares del mundo. Es una franja de tierra angosta ubicada justo entre dos gigantes:\n1. Al norte: El inmenso Mar Caribe.\n2. Al sur: La imponente cordillera de la Costa (nuestro Parque Nacional Waraira Repano), con montañas muy empinadas de más de 2.000 metros de altura.\n\n¿Por qué es importante conocer esto?\nPorque en La Guaira convivimos con dos fuerzas de la naturaleza:\n• Los ríos y quebradas que nacen en la cumbre de la montaña y descienden con gran velocidad hacia el mar.\n• El contacto entre la tierra y el mar.\n\nEstar preparados no significa tener miedo: ¡significa ser inteligentes y conocer nuestro hogar! Cuando conocemos cómo funciona la montaña, los ríos y el mar, aprendemos a cuidar nuestras casas, no botar basura en los cauces y saber cómo actuar en equipo con la familia.",
    'practical_exercise' => 'Dibuja en tu mente o en un papel cómo se ubica La Guaira entre el Ávila y el mar. Menciona dos ríos o quebradas que conozcas.',
    'xp_reward' => 60,
    'order_num' => 3
], [
    [
        'question_text' => '¿Cuáles son los dos gigantes geográficos entre los que se encuentra ubicado el estado La Guaira?',
        'question_type' => 'multiple_choice',
        'options' => ['El Mar Caribe y la Cordillera del Waraira Repano', 'El Río Orinoco y la Selva Amazónica', 'Los Andes y el Lago de Maracaibo', 'El Desierto y los Llanos'],
        'correct_answer' => 'El Mar Caribe y la Cordillera del Waraira Repano',
        'explanation' => '¡Perfecto! Vivimos en una estrecha franja costera entre el mar y la montaña.'
    ],
    [
        'question_text' => '¿Por qué nunca debemos arrojar basura en las quebradas y ríos que bajan de la montaña?',
        'question_type' => 'multiple_choice',
        'options' => ['Porque la basura bloquea el cauce y provoca desbordamientos', 'Porque se ve feo solamente', 'Porque los peces se la comen', 'No tiene ninguna importancia'],
        'correct_answer' => 'Porque la basura bloquea el cauce y provoca desbordamientos',
        'explanation' => '¡Exactamente! Mantener los cauces limpios salva vidas y previene inundaciones.'
    ],
    [
        'question_text' => 'Menciona el nombre de una quebrada o sector cercano a tu casa en La Guaira y describe cómo es el terreno.',
        'question_type' => 'short_answer',
        'correct_answer' => '',
        'explanation' => 'Tu papá leerá tu respuesta para evaluar tu conocimiento del entorno.'
    ]
]);

// --- MARTES 5TO ---
// 1. Álgebra
insertActivityWithQuestions($pdo, [
    'week_number' => 1,
    'grade' => 5,
    'day_of_week' => 'martes',
    'subject_id' => $subjMap['algebra'],
    'title' => 'El Misterio de las Balanzas en Equilibrio',
    'summary' => 'Iniciación al álgebra visual descubriendo pesos y valores ocultos.',
    'reading_content' => "El álgebra es como un juego de detectives donde descubrimos números secretos.\n\nImagina una balanza antigua de dos platos que está perfectamente equilibrada (ambos lados pesan exactamente lo mismo).\n\nEn el plato izquierdo hay: 1 Piña + 2 Kilos de pesas.\nEn el plato derecho hay: 7 Kilos de pesas.\n\nComo la balanza está en equilibrio, sabemos que:\nPiña + 2 kg = 7 kg\n\n¿Cuánto pesa la piña?\nSi quitamos 2 kg de ambos lados para no perder el equilibrio:\nPiña = 7 - 2 = 5 kg.\n\n¡Felicidades! Acabas de resolver tu primera ecuación de manera intuitiva. En matemáticas, a esa 'piña' desconocida la solemos llamar con una letra, como la letra X.",
    'practical_exercise' => 'Si una caja misteriosa + 6 monedas = 15 monedas, ¿cuántas monedas hay dentro de la caja misteriosa?',
    'xp_reward' => 50,
    'order_num' => 1
], [
    [
        'question_text' => 'Si una Balanza tiene en un plato (Caja Secreta + 4 kg) y en el otro plato (10 kg), ¿cuánto pesa la Caja Secreta?',
        'question_type' => 'multiple_choice',
        'options' => ['6 kg', '14 kg', '4 kg', '10 kg'],
        'correct_answer' => '6 kg',
        'explanation' => '¡Muy bien! 10 - 4 = 6 kg. El valor desconocido es 6.'
    ],
    [
        'question_text' => 'Si 2 Pelotas pesan lo mismo que 8 kilos en total, ¿cuánto pesa una sola pelota?',
        'question_type' => 'multiple_choice',
        'options' => ['4 kg', '6 kg', '16 kg', '2 kg'],
        'correct_answer' => '4 kg',
        'explanation' => '¡Excelente deducción! Si 2 pelotas iguales pesan 8 kg, cada una pesa 8 / 2 = 4 kg.'
    ]
]);

// 2. Historia de Venezuela
insertActivityWithQuestions($pdo, [
    'week_number' => 1,
    'grade' => 5,
    'day_of_week' => 'martes',
    'subject_id' => $subjMap['historia'],
    'title' => 'Los Primeros Pobladores y la Costa Venezolana',
    'summary' => 'Conoce cómo vivían los pueblos originarios indígenas en las costas e islas.',
    'reading_content' => "Mucho antes de la llegada de los barcos españoles, las tierras venezolanas estaban habitadas por diversas comunidades indígenas con culturas asombrosas.\n\nEn la región costera central y oriental vivían principalmente los pueblos Caribes y Arawacos:\n• Eran navegantes expertos que construían canoas resistentes para pescar en el mar y comerciar entre islas.\n• Se alimentaban de pescado fresco, mariscos, yuca con la que preparaban el casabe (un pan crujiente y duradero que hoy seguimos comiendo), maíz y frutas silvestres.\n• Vivían en chozas construidas con palmas y madera adaptadas al clima tropical fresco.\n\nEl casabe que hoy compramos en el mercado tiene más de mil años de tradición gracias a nuestros antepasados indígenas.",
    'practical_exercise' => 'Nombra tres alimentos que consumían los pueblos indígenas en la costa venezolana.',
    'xp_reward' => 50,
    'order_num' => 2
], [
    [
        'question_text' => '¿De qué raíz vegetal elaboraban los pueblos indígenas el tradicional casabe?',
        'question_type' => 'multiple_choice',
        'options' => ['De la yuca', 'De la papa', 'Del trigo', 'Del plátano'],
        'correct_answer' => 'De la yuca',
        'explanation' => '¡Correcto! El casabe se elabora a partir de la yuca amarga, procesada magistralmente en el sebucán.'
    ],
    [
        'question_text' => 'Los indígenas que habitaban la costa destacaban por ser excelentes navegantes y pescadores.',
        'question_type' => 'true_false',
        'options' => ['Verdadero', 'Falso'],
        'correct_answer' => 'Verdadero',
        'explanation' => '¡Verdadero! Navegaban con gran destreza en piraguas y canoas.'
    ]
]);

// 3. Ciencias
insertActivityWithQuestions($pdo, [
    'week_number' => 1,
    'grade' => 5,
    'day_of_week' => 'martes',
    'subject_id' => $subjMap['ciencias'],
    'title' => 'Los Tres Estados de la Materia en Casa',
    'summary' => 'Sólido, líquido y gaseoso explicados a través de experiencias en la cocina.',
    'reading_content' => "Todo lo que nos rodea en el universo está hecho de MATERIA: el aire que respiras, el agua que bebes y la pantalla de tu celular.\n\nLa materia se presenta comúnmente en tres estados fundamentales:\n\n1. SÓLIDO: Tiene forma fija y volumen definido. Las partículas están muy juntas y ordenadas. Ejemplo: Un cubito de hielo, una roca, un lápiz.\n2. LÍQUIDO: Tiene volumen definido pero adopta la forma del recipiente que lo contiene. Las partículas se mueven con libertad. Ejemplo: El agua en un vaso, la leche, el aceite.\n3. GASEOSO: No tiene forma ni volumen fijos; se expande ocupando todo el espacio disponible. Las partículas están muy separadas. Ejemplo: El vapor de agua que sale de la olla al hervir, el aire dentro de un globo.",
    'practical_exercise' => 'Observa un cubito de hielo derritiéndose. ¿De qué estado a qué estado cambió la materia?',
    'xp_reward' => 50,
    'order_num' => 3
], [
    [
        'question_text' => 'Cuando el hielo se derrite y se convierte en agua líquida, ¿cómo se llama ese cambio?',
        'question_type' => 'multiple_choice',
        'options' => ['Fusión', 'Solidificación', 'Evaporación', 'Congelación'],
        'correct_answer' => 'Fusión',
        'explanation' => '¡Muy bien! El paso de sólido a líquido se denomina fusión.'
    ],
    [
        'question_text' => '¿Cuál de los estados de la materia no tiene forma fija y se expande ocupando todo el espacio disponible?',
        'question_type' => 'multiple_choice',
        'options' => ['Gaseoso', 'Sólido', 'Líquido', 'Metálico'],
        'correct_answer' => 'Gaseoso',
        'explanation' => '¡Correcto! Los gases se expanden por toda la habitación o recipiente.'
    ]
]);

// --- MIERCOLES 5TO ---
// 1. Matemáticas
insertActivityWithQuestions($pdo, [
    'week_number' => 1,
    'grade' => 5,
    'day_of_week' => 'miercoles',
    'subject_id' => $subjMap['matematicas'],
    'title' => 'Multiplicar Decimales por 10, 100 y 1.000 (El Truco de la Coma)',
    'summary' => 'Aprende el método ultra-rápido de correr la coma a la derecha sin hacer cuentas largas.',
    'reading_content' => "¿Sabías que existe un truco mágico para multiplicar cualquier número decimal por 10, 100 o 1.000 en un segundo?\n\n¡No necesitas hacer una multiplicación larga en papel! Solo debes CORRER LA COMA hacia la DERECHA tantos lugares como ceros tenga el número:\n\n• Multiplicar por 10 (tiene 1 cero): Ruedas la coma 1 lugar a la derecha.\n  Ejemplo: 3,45 × 10 = 34,5\n\n• Multiplicar por 100 (tiene 2 ceros): Ruedas la coma 2 lugares a la derecha.\n  Ejemplo: 3,45 × 100 = 345\n\n• Multiplicar por 1.000 (tiene 3 ceros): Ruedas la coma 3 lugares a la derecha (si faltan números, agregas ceros).\n  Ejemplo: 3,45 × 1.000 = 3.450",
    'practical_exercise' => 'Aplica el truco con 8,72 × 10 y con 0,5 × 100.',
    'xp_reward' => 50,
    'order_num' => 1
], [
    [
        'question_text' => '¿Cuánto da 4,25 × 10 aplicando el truco de rodar la coma?',
        'question_type' => 'multiple_choice',
        'options' => ['42,5', '425', '0,425', '4,250'],
        'correct_answer' => '42,5',
        'explanation' => '¡Excelente! Al multiplicar por 10, la coma corre 1 lugar a la derecha.'
    ],
    [
        'question_text' => '¿Cuánto es 0,7 × 100?',
        'question_type' => 'multiple_choice',
        'options' => ['70', '7', '700', '0,07'],
        'correct_answer' => '70',
        'explanation' => '¡Bien jugado! Corres 1 lugar para el 7 y como falta otro lugar, agregas un cero: 70.'
    ]
]);

// 2. Gestión de Riesgos La Guaira
insertActivityWithQuestions($pdo, [
    'week_number' => 1,
    'grade' => 5,
    'day_of_week' => 'miercoles',
    'subject_id' => $subjMap['riesgo_laguaira'],
    'title' => 'Crecidas de Ríos y Quebradas: Alertas en la Montaña',
    'summary' => 'Aprende a reconocer las señales naturales antes de que una quebrada o río crezca.',
    'reading_content' => "En La Guaira, las cuencas de los ríos nacen muy alto en el Parque Nacional Waraira Repano y bajan por pendientes pronunciadas.\n\nA veces, en la costa (donde están nuestras casas) puede estar lloviendo suavemente o incluso hacer sol, pero en la cumbre de la montaña puede estar cayendo un aguacero torrencial prolongado.\n\n¿Cuáles son las SEÑALES DE ALERTA TEMPRANA de una crecida?\n1. CAMBIO DE COLOR DEL AGUA: Si el agua del río o quebrada se vuelve repentinamente marrón oscura o chocolatosa con mucho barro, significa que la montaña está drenando sedimentos.\n2. RUIDO SORDO O RETUMBO: Un sonido grave similar a un camión pesado que viene desde la parte alta de la montaña (provocado por piedras que chocan bajo el agua).\n3. TRONCOS O RAMAS FLOTANDO: Aparición repentina de vegetación arrastrada por la corriente.\n\n¿QUÉ HACER DE INMEDIATO?\n• Nunca cruzar un río o quebrada crecida a pie ni en vehículo.\n• Alejarse de las orillas del cauce y dirigirse a zonas altas y seguras de inmediato.\n• Avisar a los adultos sin perder tiempo intentando rescatar objetos materiales.",
    'practical_exercise' => 'Pregunta a tus padres qué quebrada o cauce queda más cerca de su hogar y cuál es la calle o zona más alta y segura para resguardarse.',
    'xp_reward' => 60,
    'order_num' => 2
], [
    [
        'question_text' => 'Si el agua de un río en La Guaira cambia súbitamente de transparente a color marrón oscuro y espeso, ¿qué indica?',
        'question_type' => 'multiple_choice',
        'options' => ['Alerta de crecida inminente en la cabecera de la montaña', 'Que el agua está lista para bañarse', 'Que el mar está subiendo', 'Que no pasa nada especial'],
        'correct_answer' => 'Alerta de crecida inminente en la cabecera de la montaña',
        'explanation' => '¡Exacto! El agua con sedimentos indica que en la cumbre está lloviendo fuerte y viene una crecida.'
    ],
    [
        'question_text' => '¿Es seguro cruzar un cauce crecido si el agua solo llega a los tobillos?',
        'question_type' => 'multiple_choice',
        'options' => ['No, porque la fuerza del agua con barro puede arrastrar a una persona fácilmente', 'Sí, siempre que se cruce corriendo', 'Sí, si es de día', 'Sí, con botas de plástico'],
        'correct_answer' => 'No, porque la fuerza del agua con barro puede arrastrar a una persona fácilmente',
        'explanation' => '¡Nunca se debe cruzar! Tan solo 15 centímetros de agua en movimiento con sedimentos pueden derribar a un adulto.'
    ],
    [
        'question_text' => 'Explica por qué la vida humana siempre es más valiosa que cualquier juguete u objeto material durante una evacuación.',
        'question_type' => 'reflexive',
        'correct_answer' => '',
        'explanation' => 'Tu papá evaluará tu reflexión sobre el valor de la seguridad familiar.'
    ]
]);

// 3. Biología y Ecosistemas
insertActivityWithQuestions($pdo, [
    'week_number' => 1,
    'grade' => 5,
    'day_of_week' => 'miercoles',
    'subject_id' => $subjMap['biologia'],
    'title' => 'La Fotosíntesis: La Gran Fábrica Verde de Oxígeno',
    'summary' => 'Descubre cómo las plantas transforman la luz del sol, el agua y el aire en vida y oxígeno puro.',
    'reading_content' => "Las plantas son seres vivos extraordinarios y los grandes pulmones de nuestro planeta Tierra.\n\nA diferencia de los animales y los seres humanos, que tenemos que buscar comida, las plantas tienen una capacidad asombrosa: fabrican su propio alimento mediante un proceso biológico llamado FOTOSÍNTESIS.\n\n¿CÓMO FUNCIONA ESTA FÁBRICA VERDE?\n1. Raíces: Absorben agua y minerales del suelo.\n2. Hojas: Tienen un pigmento verde llamado CLOROFILA, que funciona como un pequeño panel solar que atrapa la luz del Sol.\n3. Aire: A través de poros diminutos en las hojas (estomas), la planta absorbe Dióxido de Carbono (CO2).\n\nCon luz solar, agua y CO2, la planta elabora GLUCOSA (su energía) y libera al aire OXÍGENO puro (O2), que es el gas que todos los seres humanos necesitamos para respirar.\n\n¡Por eso cuidar los árboles en nuestras comunidades y en el Parque Nacional Waraira Repano es vital para respirar aire limpio todos los días!",
    'practical_exercise' => 'Observa una planta de tu casa o cerca de tu ventana. Toca suavemente una de sus hojas e identifica cómo le llega la luz del sol.',
    'xp_reward' => 50,
    'order_num' => 3
], [
    [
        'question_text' => '¿Cuál es el nombre del pigmento verde que permite a las plantas atrapar la luz del sol?',
        'question_type' => 'multiple_choice',
        'options' => ['Clorofila', 'Glucosa', 'Oxígeno', 'Hemoglobina'],
        'correct_answer' => 'Clorofila',
        'explanation' => '¡Exacto! La clorofila da el color verde y absorbe la energía de la luz solar.'
    ],
    [
        'question_text' => '¿Qué gas indispensable para la vida humana liberan las plantas durante la fotosíntesis?',
        'question_type' => 'multiple_choice',
        'options' => ['Oxígeno', 'Dióxido de carbono', 'Nitrógeno', 'Humo'],
        'correct_answer' => 'Oxígeno',
        'explanation' => '¡Muy bien! Las plantas limpian el aire y liberan oxígeno para nosotros.'
    ],
    [
        'question_text' => 'Explica con tus propias palabras por qué es importante sembrar y cuidar árboles en nuestra comunidad.',
        'question_type' => 'reflexive',
        'correct_answer' => '',
        'explanation' => 'Tu papá evaluará tu reflexión sobre el cuidado de la naturaleza y los árboles.'
    ]
]);

// --- JUEVES 5TO ---
// 1. Álgebra
insertActivityWithQuestions($pdo, [
    'week_number' => 1,
    'grade' => 5,
    'day_of_week' => 'jueves',
    'subject_id' => $subjMap['algebra'],
    'title' => 'Descubriendo el Número Perdido (Ecuaciones Aditivas)',
    'summary' => 'Aprende a despejar incógnitas usando la operación contraria.',
    'reading_content' => "Ayer descubrimos que una incógnita es un número escondido detrás de una letra o símbolo.\n\nHoy aprenderemos la regla de los matemáticos para despejar ese número secreto:\n¡LA OPERACIÓN INVERSA!\n\nSi un número está SUMANDO al lado de la incógnita, pasa al otro lado de la igualdad RESTANDO.\n\nEjemplo:\n  X + 15 = 40\n\n¿Cómo dejamos a la X completamente sola?\nEl 15 que está sumando se va al otro lado a restar:\n  X = 40 - 15\n  X = 25\n\n¡Comprobémoslo!\n25 + 15 = 40. ¡Funciona perfecto!",
    'practical_exercise' => 'Resuelve: X + 23 = 50. ¿Cuánto vale X?',
    'xp_reward' => 50,
    'order_num' => 1
], [
    [
        'question_text' => 'En la ecuación: X + 12 = 30, ¿cuál es el valor de X?',
        'question_type' => 'multiple_choice',
        'options' => ['18', '42', '12', '20'],
        'correct_answer' => '18',
        'explanation' => '¡Brillante! X = 30 - 12 = 18.'
    ],
    [
        'question_text' => 'Si X - 5 = 20, para pasar el 5 al otro lado debemos pasarlo sumando. ¿Cuánto vale X?',
        'question_type' => 'multiple_choice',
        'options' => ['25', '15', '100', '4'],
        'correct_answer' => '25',
        'explanation' => '¡Exacto! Lo contrario de restar es sumar: X = 20 + 5 = 25.'
    ]
]);

// 2. Historia
insertActivityWithQuestions($pdo, [
    'week_number' => 1,
    'grade' => 5,
    'day_of_week' => 'jueves',
    'subject_id' => $subjMap['historia'],
    'title' => 'Gual y España: Los Héroes Patriotas de La Guaira (1797)',
    'summary' => 'El primer gran movimiento revolucionario por la igualdad y la libertad nació en nuestro puerto.',
    'reading_content' => "¿Sabías que La Guaira fue el escenario del primer gran grito revolucionario por la independencia de Venezuela?\n\nEn 1797, dos valientes hombres de nuestra tierra:\n• Manuel Gual (militar de La Guaira) y\n• José María España (lugarteniente de Macuto)\n\njunto a otros vecinos, organizaron una conspiración histórica contra el dominio colonial español.\n\nSu proyecto fue sumamente avanzado para su época porque proclamaba:\n1. La abolición total de la esclavitud.\n2. La igualdad absoluta entre blancos, pardos, indígenas y negros.\n3. La libertad de comercio y el fin de los impuestos opresivos.\n4. Diseñaron además una hermosa bandera de cuatro colores (blanco, azul, amarillo y rojo) con 4 estrellas que representaban las provincias.\n\nAunque el movimiento fue descubierto y enfrentó duras represalias, sembró para siempre la semilla de la patria soberana en el corazón de los guaireños.",
    'practical_exercise' => 'Investiga qué monumento o plaza en La Guaira recuerda a José María España.',
    'xp_reward' => 50,
    'order_num' => 2
], [
    [
        'question_text' => '¿En qué año se llevó a cabo la histórica conspiración de Gual y España en La Guaira?',
        'question_type' => 'multiple_choice',
        'options' => ['1797', '1810', '1900', '1492'],
        'correct_answer' => '1797',
        'explanation' => '¡Correcto! En 1797, 13 años antes del 19 de abril de 1810.'
    ],
    [
        'question_text' => 'Uno de los grandes principios de Gual y España era la abolición de la esclavitud y la igualdad de todos los seres humanos.',
        'question_type' => 'true_false',
        'options' => ['Verdadero', 'Falso'],
        'correct_answer' => 'Verdadero',
        'explanation' => '¡Totalmente verdadero! Eran defensores apasionados de la justicia social.'
    ]
]);

// 3. Formación Ciudadana y Valores
insertActivityWithQuestions($pdo, [
    'week_number' => 1,
    'grade' => 5,
    'day_of_week' => 'jueves',
    'subject_id' => $subjMap['ciudadania'],
    'title' => 'El Poder de la Empatía y el Respeto en Familia',
    'summary' => 'Aprende qué es ser un buen ciudadano empezando desde el hogar, la escuela y la comunidad.',
    'reading_content' => "Ser ciudadano no es solo tener un documento de identidad o vivir en una ciudad. Ser un buen ciudadano es una actitud diaria de respeto, colaboración y empatía con las personas que nos rodean.\n\n¿QUÉ ES LA EMPATÍA?\nLa empatía es la capacidad de ponernos en los zapatos de la otra persona, intentando comprender lo que siente y lo que necesita antes de juzgar o molestarnos. Por ejemplo:\n• Si tu hermano o un compañero está triste o cansado, preguntarle con cariño cómo se siente en lugar de burlarse.\n• Si papá o mamá están ocupados trabajando, colaborar recogiendo nuestro plato o manteniendo el cuarto ordenado.\n\nREGLAS DE ORO DE LA BUENA CONVIVENCIA:\n1. Escuchar activamente cuando los demás nos hablan.\n2. Usar palabras mágicas: 'Por favor', 'Gracias', 'Disculpa' y 'Buenos días'.\n3. Resolver los desacuerdos conversando con calma, sin gritar ni agredir.\n\nCuando practicamos estos valores en casa, nos convertimos en ciudadanos valiosos que construyen una sociedad pacífica y solidaria.",
    'practical_exercise' => 'El Reto de la Amabilidad: Realiza hoy una acción servicial en casa sin que nadie te la pida (por ejemplo, acomodar tus libros o ayudar a limpiar).',
    'xp_reward' => 50,
    'order_num' => 3
], [
    [
        'question_text' => '¿Qué significa la palabra EMPATÍA según lo aprendido en la lectura?',
        'question_type' => 'multiple_choice',
        'options' => ['Ponerse en el lugar de otra persona para entender lo que siente', 'Tener siempre la razón en una discusión', 'Quedarse en silencio sin ayudar a nadie', 'Hacer tareas solamente cuando nos obligan'],
        'correct_answer' => 'Ponerse en el lugar de otra persona para entender lo que siente',
        'explanation' => '¡Excelente! La empatía nos permite sentir con el corazón de los demás.'
    ],
    [
        'question_text' => '¿Cuál es la mejor manera de resolver un desacuerdo con tu hermano o un amigo?',
        'question_type' => 'multiple_choice',
        'options' => ['Conversando con calma y escuchando respetuosamente', 'Gritando más fuerte para ganar', 'Ignorar a la otra persona para siempre', 'Pelear físicamente'],
        'correct_answer' => 'Conversando con calma y escuchando respetuosamente',
        'explanation' => '¡Así es! El diálogo y la escucha pacífica resuelven cualquier diferencia.'
    ],
    [
        'question_text' => '¿Qué acción de bondad o colaboración realizaste o piensas realizar hoy en tu hogar?',
        'question_type' => 'reflexive',
        'correct_answer' => '',
        'explanation' => 'Tu papá leerá tu compromiso cívico y de convivencia familiar.'
    ]
]);

// --- VIERNES 5TO ---
// 1. Gestión de Riesgos La Guaira (Mochila de Emergencia)
insertActivityWithQuestions($pdo, [
    'week_number' => 1,
    'grade' => 5,
    'day_of_week' => 'viernes',
    'subject_id' => $subjMap['riesgo_laguaira'],
    'title' => 'Misión Familiar: La Mochila de las 72 Horas',
    'summary' => 'Aprende qué objetos son vitales en una emergencia y cuáles no debemos cargar.',
    'reading_content' => "En cualquier hogar de La Guaira, ante lluvias intensas, movimientos sísmicos o emergencias, una familia preparada debe tener lista su 'Mochila de las 72 Horas'.\n\n¿Por qué 72 horas? Porque son las primeras 3 jornadas en las que los servicios de agua, luz o rescate pueden tardar en restablecerse mientras las autoridades atienden la zona.\n\nELEMENTOS VITALES QUE SÍ DEBEN IR:\n1. Agua potable embotellada (mínimo 2 litros por persona).\n2. Linterna resistente y pilas de repuesto.\n3. Radio portátil a baterías (para escuchar comunicados oficiales sin depender de internet).\n4. Botiquín de primeros auxilios (alcohol, gasas, curitas, medicamentos familiares).\n5. Alimentos no perecederos enlatados (atún, sardinas, galletas) que abran sin abrelatas.\n6. Copias de documentos importantes (cédulas, títulos) dentro de una bolsa plástica hermética zíploc.\n7. Silbato o pito (hace mucho más ruido que gritar si se necesita auxilio).\n\nLO QUE NO DEBE IR:\n• Objetos pesados, juguetes grandes o televisores que quiten agilidad o causen tropiezos.",
    'practical_exercise' => 'Revisa con tus padres qué elementos de esta lista ya tienen en casa y en qué lugar seguro guardarán la mochila.',
    'xp_reward' => 70,
    'order_num' => 1
], [
    [
        'question_text' => '¿Por qué es indispensable guardar las copias de los documentos familiares en bolsas plásticas herméticas?',
        'question_type' => 'multiple_choice',
        'options' => ['Para protegerlos del agua y la humedad en caso de lluvia', 'Para que se vean más bonitos', 'Para que pesen más', 'No hace falta protegerlos'],
        'correct_answer' => 'Para protegerlos del agua y la humedad en caso de lluvia',
        'explanation' => '¡Exacto! El agua destruye el papel rápidamente si no está impermeabilizado.'
    ],
    [
        'question_text' => '¿Cuál de los siguientes objetos es una herramienta auditiva clave para pedir auxilio sin gastar la voz?',
        'question_type' => 'multiple_choice',
        'options' => ['Un silbato o pito', 'Un parlante Bluetooth', 'Una campana de bronce', 'Un tambor grande'],
        'correct_answer' => 'Un silbato o pito',
        'explanation' => '¡Muy bien! El silbato tiene un sonido agudo que viaja lejos y no cansa tus cuerdas vocales.'
    ],
    [
        'question_text' => 'Escribe una lista de 4 cosas que tú personalmente empacarías en la mochila familiar de emergencia hoy mismo.',
        'question_type' => 'short_answer',
        'correct_answer' => '',
        'explanation' => 'Tu papá revisará tu selección de artículos esenciales.'
    ]
]);

// 2. Desafío Matemático Semanal
insertActivityWithQuestions($pdo, [
    'week_number' => 1,
    'grade' => 5,
    'day_of_week' => 'viernes',
    'subject_id' => $subjMap['matematicas'],
    'title' => 'El Gran Reto Matemático del Fin de Semana',
    'summary' => 'Pon a prueba todo lo aprendido en la semana y gana el trofeo de puntos.',
    'reading_content' => "¡Llegamos al final de la semana, Dimas!\n\nHas demostrado mucha perseverancia estudiando desde casa. En este desafío final pondrás a prueba:\n• Sumas y restas con decimales.\n• Multiplicación rápida por potencias de 10.\n• Descubrir números incógnitas.\n\nLee cada pregunta con calma, ten a mano un lápiz y una hoja de papel borrador para hacer tus anotaciones, ¡y a triunfar!",
    'practical_exercise' => 'Haz los cálculos en tu cuaderno borrador antes de marcar tu respuesta final.',
    'xp_reward' => 60,
    'order_num' => 2
], [
    [
        'question_text' => 'Si compras un paquete de harina por 1,20 y un kilo de caraotas por 2,80, ¿cuánto pagas en total?',
        'question_type' => 'multiple_choice',
        'options' => ['4,00', '3,90', '4,20', '3,50'],
        'correct_answer' => '4,00',
        'explanation' => '¡Perfecto! 1,20 + 2,80 = 4,00 exactos.'
    ],
    [
        'question_text' => 'Si multiplicas 15,6 por 100, ¿cuál es el resultado?',
        'question_type' => 'multiple_choice',
        'options' => ['1.560', '156', '15.600', '156,0'],
        'correct_answer' => '1.560',
        'explanation' => '¡Excelente! Ruedas la coma dos lugares: uno para el 6 y agregas un cero = 1.560.'
    ]
]);

// 3. Biología y Ecosistemas
insertActivityWithQuestions($pdo, [
    'week_number' => 1,
    'grade' => 5,
    'day_of_week' => 'viernes',
    'subject_id' => $subjMap['biologia'],
    'title' => 'Fauna Venezolana y Cadenas Alimenticias',
    'summary' => 'Conoce cómo los animales y plantas interactúan en los ecosistemas de Venezuela.',
    'reading_content' => "Venezuela es uno de los países con mayor variedad de animales en todo el mundo. En nuestra tierra conviven aves de colores brillantes, mamíferos únicos y peces asombrosos.\n\n¿QUÉ ES UNA CADENA ALIMENTICIA?\nEn la naturaleza, todos los seres vivos están conectados a través de la alimentación:\n\n1. Productores: Las plantas y algas, que fabrican su propio alimento con la luz del sol.\n2. Consumidores Primarios (Herbívoros): Animales que comen plantas. Por ejemplo, el Chigüire (el roedor más grande del mundo) comiendo hierbas en los llanos.\n3. Consumidores Secundarios (Carnívoros): Depredadores que cazan otros animales para alimentarse, como el imponente Jaguar o el Águila Harpía.\n4. Descomponedores: Hongos y bacterias del suelo que transforman los restos orgánicos en nutrientes para que nuevas plantas crezcan.\n\nSi cuidamos a cada especie y protegemos sus hábitats naturales, mantenemos el equilibrio ecológico que permite la vida de todos.",
    'practical_exercise' => 'Nombra tres animales que vivan en Venezuela y clasifícalos según lo que comen (herbívoro, carnívoro u omnívoro).',
    'xp_reward' => 50,
    'order_num' => 3
], [
    [
        'question_text' => '¿Cómo se le llama a los animales que se alimentan exclusivamente de plantas en una cadena alimenticia?',
        'question_type' => 'multiple_choice',
        'options' => ['Herbívoros (Consumidores Primarios)', 'Carnívoros', 'Descomponedores', 'Productores'],
        'correct_answer' => 'Herbívoros (Consumidores Primarios)',
        'explanation' => '¡Correcto! Los herbívoros comen hojas, hierbas y frutos.'
    ],
    [
        'question_text' => '¿Cuál de los siguientes es el roedor más grande del mundo y habita en los llanos y ríos de Venezuela?',
        'question_type' => 'multiple_choice',
        'options' => ['El Chigüire (Capibara)', 'El Ratón de campo', 'La Ardilla del Ávila', 'El Rabipelado'],
        'correct_answer' => 'El Chigüire (Capibara)',
        'explanation' => '¡Perfecto! El chigüire es un símbolo de la fauna venezolana.'
    ],
    [
        'question_text' => '¿Por qué crees que es importante proteger a los depredadores como el jaguar para el equilibrio de la naturaleza?',
        'question_type' => 'reflexive',
        'correct_answer' => '',
        'explanation' => 'Tu papá leerá tu análisis sobre el equilibrio de los ecosistemas.'
    ]
]);

// =========================================================================
// ACTIVIDADES 6TO GRADO (ALEXANDER - 11 AÑOS)
// =========================================================================

// --- LUNES 6TO ---
// 1. Matemáticas
insertActivityWithQuestions($pdo, [
    'week_number' => 1,
    'grade' => 6,
    'day_of_week' => 'lunes',
    'subject_id' => $subjMap['matematicas'],
    'title' => 'Mínimo Común Múltiplo (m.c.m.) y Fracciones con Distinto Denominador',
    'summary' => 'Aprende a sumar y restar fracciones heterogéneas encontrando el denominador común.',
    'reading_content' => "En 6to grado las fracciones se vuelven una herramienta poderosa. Cuando sumamos fracciones con el mismo denominador (como 1/4 + 2/4 = 3/4) es muy sencillo.\n\nPero, ¿qué ocurre cuando tienen distinto denominador? (Por ejemplo: 1/3 + 2/5).\n¡No podemos sumar directamente los de abajo! Necesitamos encontrar el Mínimo Común Múltiplo (m.c.m.).\n\nPASOS PARA SUMAR FRACCIONES HETEROGÉNEAS:\n1. Descomponer los denominadores en factores primos o buscar el menor múltiplo común.\n   Para 3 y 5: Múltiplos de 3 (3, 6, 9, 12, 15...). Múltiplos de 5 (5, 10, 15...).\n   El m.c.m. es 15.\n\n2. Convertir cada fracción a denominador 15:\n   • (15 ÷ 3) × 1 = 5  -> 5/15\n   • (15 ÷ 5) × 2 = 6  -> 6/15\n\n3. Ahora sí sumamos los numeradores:\n   5/15 + 6/15 = 11/15.\n\n¡Listo! Esa es la técnica maestra de los matemáticos.",
    'practical_exercise' => 'Calcula en papel la suma de 1/2 + 1/4 usando el m.c.m.',
    'xp_reward' => 50,
    'order_num' => 1
], [
    [
        'question_text' => '¿Cuál es el Mínimo Común Múltiplo (m.c.m.) entre 4 y 6?',
        'question_type' => 'multiple_choice',
        'options' => ['12', '24', '10', '6'],
        'correct_answer' => '12',
        'explanation' => '¡Exacto! 12 es el menor número positivo que es divisible tanto entre 4 como entre 6.'
    ],
    [
        'question_text' => 'Al sumar 1/2 + 1/4, ¿cuál es el resultado simplificado?',
        'question_type' => 'multiple_choice',
        'options' => ['3/4', '2/6', '1/6', '4/4'],
        'correct_answer' => '3/4',
        'explanation' => '¡Muy bien! 1/2 equivale a 2/4. Luego 2/4 + 1/4 = 3/4.'
    ]
]);

// 2. Gestión de Riesgos La Guaira
insertActivityWithQuestions($pdo, [
    'week_number' => 1,
    'grade' => 6,
    'day_of_week' => 'lunes',
    'subject_id' => $subjMap['riesgo_laguaira'],
    'title' => 'La Falla de San Sebastián y la Tectónica en La Guaira',
    'summary' => 'Comprende científicamente por qué tiembla en nuestro litoral y derriba mitos sobre sismos.',
    'reading_content' => "Alexander, el reciente doblete sísmico que sentimos en casa no ocurrió por casualidad: tiene una explicación científica fascinante que todo estudiante de 6to grado debe dominar.\n\nLa corteza terrestre no es una sola cáscara sólida, sino un rompecabezas de gigantescos bloques en constante movimiento llamados PLACAS TECTÓNICAS.\n\nVenezuela se encuentra justo en el límite de choque y roce entre dos placas colosales:\n1. La Placa del Caribe (al norte, bajo el mar).\n2. La Placa Suramericana (al sur, donde está nuestro continente).\n\nEn la franja del estado La Guaira existe una de las fallas geológicas activas más importantes del país: LA FALLA DE SAN SEBASTIÁN, que corre sumergida paralelamente a nuestra costa.\n\nCuando estas placas rozan y liberan energía acumulada, la corteza vibra: ese es el temblor o sismo.\n\nPROTOCOLO CIENTÍFICO ANTE UN SISMO:\n• NUNCA CORRER desesperadamente por escaleras durante la vibración fuerte (es la principal causa de fracturas).\n• APLICAR: 'Agáchate, cúbrete y agárrate' bajo una mesa resistente o junto a un pilar estructural.\n• ALEJARSE de ventanas de vidrio, cables eléctricos y objetos colgantes.\n• Una vez que cese el movimiento, evacuar calmadamente hacia espacios abiertos despejados.",
    'practical_exercise' => 'Identifica en tu casa los 2 lugares más seguros (lejos de vidrios y estantes pesados) para resguardarte en caso de un sismo.',
    'xp_reward' => 60,
    'order_num' => 2
], [
    [
        'question_text' => '¿Cuáles son las dos grandes placas tectónicas cuyo contacto genera la sismicidad en el norte de Venezuela?',
        'question_type' => 'multiple_choice',
        'options' => ['La Placa del Caribe y la Placa Suramericana', 'La Placa del Pacífico y la Placa Africana', 'La Placa Euroasiática y la Placa de Nazca', 'La Placa Antártica y la Placa Norteamericana'],
        'correct_answer' => 'La Placa del Caribe y la Placa Suramericana',
        'explanation' => '¡Excelente! El límite de interacción entre la placa del Caribe y la Suramericana es la causa directa de nuestras fallas geológicas.'
    ],
    [
        'question_text' => '¿Cuál es la falla geológica submarina principal que corre paralela a la costa del estado La Guaira?',
        'question_type' => 'multiple_choice',
        'options' => ['Falla de San Sebastián', 'Falla de San Andrés', 'Falla del Nilo', 'Falla de Boconó'],
        'correct_answer' => 'Falla de San Sebastián',
        'explanation' => '¡Exacto! La Falla de San Sebastián es el sistema transcurrente que bordea la costa norte central venezolana.'
    ],
    [
        'question_text' => 'Explica por qué durante un sismo fuerte se recomienda agacharse, cubrirse y agarrarse en lugar de salir corriendo a ciegas por escaleras.',
        'question_type' => 'reflexive',
        'correct_answer' => '',
        'explanation' => 'Escribe una explicación detallada; tu papá la leerá y evaluará tu criterio de seguridad.'
    ]
]);

// 3. Lectura
insertActivityWithQuestions($pdo, [
    'week_number' => 1,
    'grade' => 6,
    'day_of_week' => 'lunes',
    'subject_id' => $subjMap['lectura'],
    'title' => 'El Primer Cable Submarino y la Conexión de La Guaira con el Mundo',
    'summary' => 'Lectura histórica sobre cómo el litoral central fue la puerta de entrada de las comunicaciones globales.',
    'reading_content' => "A finales del siglo XIX, las cartas enviadas en barcos de vapor tardaban semanas e incluso meses en cruzar el océano Atlántico desde Europa o América del Norte hasta Venezuela.\n\nTodo cambió radicalmente en 1888, cuando el puerto de La Guaira se convirtió en el punto de anclaje del primer cable telegráfico submarino intercontinental de Venezuela. Un gigantesco cordón de cobre aislado con caucho natural fue depositado en el fondo del lecho marino caribeño, conectando La Guaira con las Antillas y con las redes mundiales.\n\nPor primera vez en la historia de la nación, una noticia sucedida en París, Londres o Nueva York podía ser recibida en La Guaira en cuestión de minutos gracias al código Morse y a los impulsos eléctricos.\n\nEste hito transformó a La Guaira no solo en el puerto marítimo principal del país, sino en la capital de las telecomunicaciones internacionales, demostrando el papel estratégico que nuestro litoral ha jugado en todas las épocas.",
    'practical_exercise' => 'Subraya las palabras desconocidas y resume en tres oraciones la importancia del cable submarino.',
    'xp_reward' => 50,
    'order_num' => 3
], [
    [
        'question_text' => '¿En qué año se ancló el primer cable telegráfico submarino en La Guaira?',
        'question_type' => 'multiple_choice',
        'options' => ['1888', '1950', '1810', '1999'],
        'correct_answer' => '1888',
        'explanation' => '¡Muy bien! Fue en 1888 durante la era de modernización de finales del siglo XIX.'
    ],
    [
        'question_text' => '¿Qué sistema de puntos y rayas mediante pulsos eléctricos se utilizaba para transmitir mensajes por el cable telegráfico?',
        'question_type' => 'multiple_choice',
        'options' => ['Código Morse', 'Código Binario', 'Alfabeto Griego', 'Señales de Humo'],
        'correct_answer' => 'Código Morse',
        'explanation' => '¡Correcto! El código Morse permitía transmitir letras y números a través de la corriente eléctrica.'
    ]
]);

// --- MARTES 6TO ---
// 1. Álgebra
insertActivityWithQuestions($pdo, [
    'week_number' => 1,
    'grade' => 6,
    'day_of_week' => 'martes',
    'subject_id' => $subjMap['algebra'],
    'title' => 'Del Español al Álgebra: Traduciendo Palabras a Números y Letras',
    'summary' => 'Aprende a formular expresiones algebraicas a partir de enunciados de la vida real.',
    'reading_content' => "El lenguaje algebraico es como traducir un idioma: convertimos oraciones comunes en fórmulas matemáticas concisas.\n\nGUÍA DE TRADUCCIÓN BÁSICA:\n• 'Un número cualquiera': Lo representamos con una letra, como \$x o \$y.\n• 'El doble de un número': 2x\n• 'El triple de un número': 3x\n• 'La mitad de un número': \$x / 2 o x/2\n• 'Un número aumentado en 7': \$x + 7\n• 'Un número disminuido en 4': \$x - 4\n• 'El doble de un número más 5': 2x + 5\n\nEjemplo práctico:\nSi decimos: 'La edad de Alex aumentada en 3 años es igual a 14'.\nTraducción: \$x + 3 = 14.\n¿Cuántos años tiene Alex? Despejamos: \$x = 14 - 3 = 11 años. ¡Exacto!",
    'practical_exercise' => 'Traduce a lenguaje algebraico: "El triple de un número disminuido en 8".',
    'xp_reward' => 50,
    'order_num' => 1
], [
    [
        'question_text' => '¿Cómo se escribe en lenguaje algebraico: "El doble de un número aumentado en 10"?',
        'question_type' => 'multiple_choice',
        'options' => ['2x + 10', 'x + 20', '2 + 10x', 'x / 2 + 10'],
        'correct_answer' => '2x + 10',
        'explanation' => '¡Exactamente! El doble de un número es 2x y aumentado en 10 es + 10.'
    ],
    [
        'question_text' => 'Si el enunciado dice "La mitad de un número es igual a 8", ¿cuánto vale ese número?',
        'question_type' => 'multiple_choice',
        'options' => ['16', '4', '8', '24'],
        'correct_answer' => '16',
        'explanation' => '¡Brillante! Si x / 2 = 8, multiplicamos 8 × 2 = 16.'
    ]
]);

// 2. Historia de Venezuela
insertActivityWithQuestions($pdo, [
    'week_number' => 1,
    'grade' => 6,
    'day_of_week' => 'martes',
    'subject_id' => $subjMap['historia'],
    'title' => 'El 19 de Abril de 1810: El Nacimiento de la Soberanía',
    'summary' => 'Análisis del Cabildo de Caracas y la decisión popular que inició la emancipación.',
    'reading_content' => "El 19 de abril de 1810 marcó un punto de inflexión definitivo en la historia de Venezuela.\n\nAprovechando que España había sido invadida por las tropas francesas de Napoleón Bonaparte y el rey Fernando VII estaba cautivo, los criollos caraqueños se reunieron en la Plaza Mayor de Caracas (frente a la Catedral) para discutir el destino político de la Capitanía General.\n\nEl Capitán General español, Vicente Emparan, intentó mantener su autoridad absolutista. Sin embargo, el canónigo José Cortés de Madariaga se colocó detrás de Emparan en el balcón del Ayuntamiento e hizo señas al pueblo congregado abajo en la plaza para que dijeran '¡NO!'.\n\nAl preguntar Emparan: '¿Deseáis que siga gobernando?', la multitud gritó unánimemente: '¡No lo queremos!'. Con dignidad, Emparan respondió: 'Pues yo tampoco quiero mando', renunciando a su cargo.\n\nEse día se formó la Suprema Junta Conservadora de los Derechos de Fernando VII, el primer gobierno autónomo electo en Venezuela, que allanó el camino para la firma del Acta de Independencia el 5 de julio de 1811.",
    'practical_exercise' => 'Explica con tus palabras la diferencia entre el 19 de abril de 1810 y el 5 de julio de 1811.',
    'xp_reward' => 50,
    'order_num' => 2
], [
    [
        'question_text' => '¿Quién era el Capitán General español que renunció al mando tras el reclamo del pueblo el 19 de abril de 1810?',
        'question_type' => 'multiple_choice',
        'options' => ['Vicente Emparan', 'Pablo Morillo', 'Francisco de Miranda', 'Juan Domingo Monteverde'],
        'correct_answer' => 'Vicente Emparan',
        'explanation' => '¡Correcto! Vicente Emparan era la máxima autoridad española en ese momento.'
    ],
    [
        'question_text' => '¿Qué personaje histórico se ubicó tras Emparan para animar al pueblo a rechazar su mandato?',
        'question_type' => 'multiple_choice',
        'options' => ['José Cortés de Madariaga', 'Simón Bolívar', 'Antonio José de Sucre', 'Andrés Bello'],
        'correct_answer' => 'José Cortés de Madariaga',
        'explanation' => '¡Muy bien! El canónigo chileno Madariaga fue clave haciendo las señas desde el balcón.'
    ]
]);

// 3. Ciencias
insertActivityWithQuestions($pdo, [
    'week_number' => 1,
    'grade' => 6,
    'day_of_week' => 'martes',
    'subject_id' => $subjMap['ciencias'],
    'title' => 'La Célula: Fábrica Microscópica de la Vida',
    'summary' => 'Explora los organelos celulares y comprende las diferencias entre células animales y vegetales.',
    'reading_content' => "Todos los seres vivos —desde las bacterias más diminutas hasta las gigantescas ballenas y los árboles del Waraira Repano— están formados por CÉLULAS. La célula es la unidad anatómica, funcional y de origen de toda la vida.\n\nPARTES BÁSICAS DE UNA CÉLULA EUCARIOTA:\n1. Membrana Plasmática: La pared protectora que regula qué sustancias entran y salen.\n2. Citoplasma: Líquido gelatinoso donde flotan los organelos.\n3. Núcleo: El centro de mando donde se almacena el ADN (instrucciones genéticas).\n4. Mitocondrias: Las plantas generadoras de energía que respiran y producen combustible para la célula.\n\n¿QUÉ TIENEN LAS CÉLULAS VEGETALES QUE NO TIENEN LAS ANIMALES?\n• Pared celular rígida (de celulosa) que les da firmeza.\n• Cloroplastos con clorofila, encargados de realizar la fotosíntesis para fabricar su propio alimento usando la luz solar.",
    'practical_exercise' => 'Menciona los dos organelos presentes exclusivamente en las células vegetales.',
    'xp_reward' => 50,
    'order_num' => 3
], [
    [
        'question_text' => '¿Cuál es el organelo encargado de contener el ADN y dirigir todas las funciones de la célula?',
        'question_type' => 'multiple_choice',
        'options' => ['El Núcleo', 'La Mitocondria', 'El Ribosoma', 'El Citoplasma'],
        'correct_answer' => 'El Núcleo',
        'explanation' => '¡Exacto! El núcleo es el cerebro o centro de control celular.'
    ],
    [
        'question_text' => 'Las células vegetales realizan fotosíntesis gracias a la presencia de:',
        'question_type' => 'multiple_choice',
        'options' => ['Cloroplastos', 'Centríolos', 'Lisosomas', 'Hemoglobina'],
        'correct_answer' => 'Cloroplastos',
        'explanation' => '¡Correcto! Los cloroplastos capturan la energía de la luz solar.'
    ]
]);

// --- MIERCOLES 6TO ---
// 1. Matemáticas
insertActivityWithQuestions($pdo, [
    'week_number' => 1,
    'grade' => 6,
    'day_of_week' => 'miercoles',
    'subject_id' => $subjMap['matematicas'],
    'title' => 'Porcentajes y Descuentos en la Vida Real',
    'summary' => 'Aprende a calcular el 10%, 25% y 50% mentalmente para aplicar en compras y finanzas.',
    'reading_content' => "El porcentaje (%) representa una fracción de 100 partes. Cuando vemos '20%', significa literalmente '20 de cada 100'.\n\nTRUCOS DE CÁLCULO MENTAL RÁPIDO:\n• 50%: Es exactamente la MITAD del número (dividir entre 2).\n  Ejemplo: El 50% de 80 es 40.\n• 25%: Es la CUARTA PARTE del número (la mitad de la mitad, dividir entre 4).\n  Ejemplo: El 25% de 80 es 20.\n• 10%: Es dividir el número entre 10 (correr la coma un lugar a la izquierda).\n  Ejemplo: El 10% de 120 es 12.\n• 20%: Calculas el 10% y lo multiplicas por 2.\n  Ejemplo: Si el 10% de 120 es 12, el 20% es 24.\n\nCÁLCULO CON FÓRMULA GENERAL:\nPara cualquier porcentaje: (Cantidad × Porcentaje) ÷ 100.\nEjemplo: El 15% de 200 = (200 × 15) ÷ 100 = 3.000 ÷ 100 = 30.",
    'practical_exercise' => 'Una camisa cuesta 40 dólares y tiene un descuento del 25%. ¿Cuánto dinero te descuentan y cuánto pagas al final?',
    'xp_reward' => 50,
    'order_num' => 1
], [
    [
        'question_text' => '¿Cuál es el 50% de 180?',
        'question_type' => 'multiple_choice',
        'options' => ['90', '45', '18', '100'],
        'correct_answer' => '90',
        'explanation' => '¡Exacto! El 50% es la mitad: 180 ÷ 2 = 90.'
    ],
    [
        'question_text' => 'Si unos zapatos de 60$ tienen un 10% de descuento, ¿cuántos dólares te descuentan?',
        'question_type' => 'multiple_choice',
        'options' => ['6$', '10$', '12$', '60$'],
        'correct_answer' => '6$',
        'explanation' => '¡Correcto! El 10% de 60 es 60 ÷ 10 = 6$.'
    ]
]);

// 2. Gestión de Riesgos La Guaira
insertActivityWithQuestions($pdo, [
    'week_number' => 1,
    'grade' => 6,
    'day_of_week' => 'miercoles',
    'subject_id' => $subjMap['riesgo_laguaira'],
    'title' => 'Aludes Torrenciales y Flujos de Detritos en el Litoral',
    'summary' => 'Aprende la física de los deslaves, la saturación del suelo y las obras de retención.',
    'reading_content' => "Alexander, en la geografía de La Guaira, las laderas montañosas del Waraira Repano son muy empinadas (pendientes de más de 45 grados).\n\n¿QUÉ ES UN ALUD TORRENCIAL O FLUJO DE DETRITOS?\nCuando llueve de forma continua durante varios días sobre la montaña, la tierra actúa como una esponja. Llega un momento en que el suelo absorbe tanta agua que se SATURA POR COMPLETO.\nAl saturarse, el peso del agua hace que la tierra pierda fricción y colapse ladera abajo. El agua ya no baja limpia: se convierte en un flujo denso y viscoso (como cemento fresco en movimiento) que arrastra rocas inmensas, árboles y sedimentos a gran velocidad por los cañones de los ríos.\n\nOBRAS DE MITIGACIÓN EN LA GUAIRA:\nTras el evento de 1999, ingenieros venezolanos e internacionales construyeron en nuestras cuencas (como en Camurí Grande, Osorio, Curucutí y San Julián) PRENDAS Y DIQUES DE RETENCIÓN DE SEDIMENTOS:\n• Son muros de concreto perforados ubicados en la parte media de la montaña que atrapan las rocas gigantescas y los troncos, permitiendo que el agua fluya con menos fuerza destructiva hacia el mar.\n\nMEDIDAS CLAVE DE SEGURIDAD:\n• Nunca construir viviendas al borde de cauces o conos de deyección.\n• Mantener los diques libres de maleza y escombros.\n• Evacuar hacia cotas altas seguras cuando se emitan alertas meteorológicas de saturación de suelos.",
    'practical_exercise' => 'Investiga qué función cumplen las presas de gaviones y diques abiertos construidos en los ríos de La Guaira.',
    'xp_reward' => 60,
    'order_num' => 2
], [
    [
        'question_text' => '¿Qué ocurre con el suelo de la montaña cuando llueve durante muchos días consecutivos?',
        'question_type' => 'multiple_choice',
        'options' => ['Se satura de agua, pierde fricción y puede generar deslizamientos', 'Se vuelve más duro como una roca', 'Se seca por completo', 'No sufre ningún cambio'],
        'correct_answer' => 'Se satura de agua, pierde fricción y puede generar deslizamientos',
        'explanation' => '¡Exactamente! La saturación hídrica elimina la cohesión del suelo y provoca el flujo de detritos.'
    ],
    [
        'question_text' => '¿Cuál es la función principal de las presas y diques de retención construidos en las cuencas de La Guaira?',
        'question_type' => 'multiple_choice',
        'options' => ['Frenar y retener rocas gigantes y sedimentos pesados en la montaña', 'Generar electricidad para la costa', 'Hacer piscinas turísticas', 'Desviar los ríos hacia Caracas'],
        'correct_answer' => 'Frenar y retener rocas gigantes y sedimentos pesados en la montaña',
        'explanation' => '¡Muy bien! Retienen la carga sólida destructiva para que el caudal fluya con menor impacto.'
    ],
    [
        'question_text' => 'Describe por qué la prevención y el respeto a la geografía de la montaña es la mejor herramienta para proteger a las familias de La Guaira.',
        'question_type' => 'reflexive',
        'correct_answer' => '',
        'explanation' => 'Escribe una reflexión argumentada; tu papá valorará tu pensamiento crítico.'
    ]
]);

// 3. Biología y Ecosistemas
insertActivityWithQuestions($pdo, [
    'week_number' => 1,
    'grade' => 6,
    'day_of_week' => 'miercoles',
    'subject_id' => $subjMap['biologia'],
    'title' => 'Los Cinco Reinos de la Vida y la Megabiodiversidad de Venezuela',
    'summary' => 'Clasificación taxonómica de los seres vivos y por qué nuestro país es un tesoro natural del planeta.',
    'reading_content' => "La Biología organiza la inmensa diversidad de la vida en cinco grandes reinos taxonómicos, propuestos clásicamente por Robert Whittaker:\n\n1. Reino Mónera: Bacterias unicelulares y microscópicas sin núcleo definido (células procariotas).\n2. Reino Protista: Organismos unicelulares más complejos como las amebas, paramecios y protozoarios (eucariotas).\n3. Reino Fungi (Hongos): Organismos heterótrofos descomponedores como las setas, levaduras y mohos. (Nota clave: no hacen fotosíntesis, no son plantas).\n4. Reino Plantae: Organismos pluricelulares autótrofos que realizan la fotosíntesis.\n5. Reino Animalia: Organismos pluricelulares heterótrofos con tejidos especializados y capacidad de desplazamiento.\n\nVENEZUELA: UN PAÍS MEGADIVERSO\nVenezuela ocupa el puesto número 10 entre los países con mayor biodiversidad biológica de todo el planeta Tierra. Posee más de 1.400 especies de aves (casi el 15% de las aves del mundo) y ecosistemas tan variados como arrecifes de coral en el Caribe, bosques nublados en la Cordillera de la Costa y selvas amazónicas milenarias.",
    'practical_exercise' => 'Elabora un cuadro mental comparando el Reino Fungi y el Reino Plantae. ¿Por qué los champiñones no son plantas?',
    'xp_reward' => 50,
    'order_num' => 3
], [
    [
        'question_text' => '¿Cuál es la razón fundamental por la que los hongos (Reino Fungi) no pertenecen al Reino Plantae?',
        'question_type' => 'multiple_choice',
        'options' => ['Porque no realizan fotosíntesis y son heterótrofos', 'Porque no tienen agua', 'Porque se mueven como animales', 'Porque son células procariotas'],
        'correct_answer' => 'Porque no realizan fotosíntesis y son heterótrofos',
        'explanation' => '¡Exacto! Los hongos absorben nutrientes del medio, no fabrican su propio alimento mediante fotosíntesis.'
    ],
    [
        'question_text' => 'Aproximadamente, ¿qué posición ocupa Venezuela a nivel mundial entre los países con mayor biodiversidad?',
        'question_type' => 'multiple_choice',
        'options' => ['Puesto número 10 entre los países megadiversos', 'Puesto número 100', 'Puesto número 50', 'No está en ningún ranking'],
        'correct_answer' => 'Puesto número 10 entre los países megadiversos',
        'explanation' => '¡Excelente! Venezuela es reconocida internacionalmente como una potencia mundial en megabiodiversidad.'
    ],
    [
        'question_text' => 'Menciona un ecosistema de Venezuela que conozcas y describe qué reinos de la naturaleza conviven en él.',
        'question_type' => 'short_answer',
        'correct_answer' => '',
        'explanation' => 'Tu papá evaluará tu análisis biológico y taxonómico.'
    ]
]);

// --- JUEVES 6TO ---
// 1. Álgebra
insertActivityWithQuestions($pdo, [
    'week_number' => 1,
    'grade' => 6,
    'day_of_week' => 'jueves',
    'subject_id' => $subjMap['algebra'],
    'title' => 'Resolución de Ecuaciones Lineales de Primer Grado',
    'summary' => 'Domina el despeje de ecuaciones con coeficientes: 2x + b = c.',
    'reading_content' => "Una ecuación lineal es una balanza perfecta. El objetivo supremo es dejar a la incógnita 'x' completamente aislada en uno de los lados.\n\nPASOS DE ORO PARA RESOLVER: 2x + 6 = 20\n\n1. Primero movemos los números que están sumando o restando:\n   El +6 pasa al otro lado restando:\n   2x = 20 - 6\n   2x = 14\n\n2. Segundo movemos el número que acompaña a la 'x' multiplicando:\n   El 2 que está multiplicando a la x pasa al otro lado a DIVIDIR:\n   \$x = 14 ÷ 2\n   \$x = 7\n\n3. Verificación (¡el toque del matemático experto!):\n   Reemplazamos x por 7 en la ecuación original:\n   2 × (7) + 6 = 14 + 6 = 20.\n   ¡20 es igual a 20! Ecuación resuelta con precisión total.",
    'practical_exercise' => 'Resuelve en tu libreta: 3x + 5 = 26. ¿Cuánto vale x?',
    'xp_reward' => 50,
    'order_num' => 1
], [
    [
        'question_text' => 'En la ecuación: 2x + 4 = 18, ¿cuál es el valor final de x?',
        'question_type' => 'multiple_choice',
        'options' => ['7', '9', '14', '6'],
        'correct_answer' => '7',
        'explanation' => '¡Excelente! 2x = 18 - 4 = 14. Luego x = 14 / 2 = 7.'
    ],
    [
        'question_text' => 'En la ecuación: 5x = 45, ¿cuánto vale x?',
        'question_type' => 'multiple_choice',
        'options' => ['9', '8', '5', '40'],
        'correct_answer' => '9',
        'explanation' => '¡Correcto! x = 45 / 5 = 9.'
    ]
]);

// 2. Historia de Venezuela
insertActivityWithQuestions($pdo, [
    'week_number' => 1,
    'grade' => 6,
    'day_of_week' => 'jueves',
    'subject_id' => $subjMap['historia'],
    'title' => 'La Batalla de Carabobo (1821) y el Genio de Bolívar',
    'summary' => 'Estrategia militar, el papel de la caballería de Páez y el sello definitivo de la soberanía.',
    'reading_content' => "El 24 de junio de 1821, en la sabana de Carabobo, se libró la batalla cumbre que selló la independencia política de Venezuela frente al imperio español.\n\nEl Ejército Patriota, comandado por el Libertador Simón Bolívar, diseñó una maniobra táctica magistral. En lugar de atacar frontalmente las posiciones atrincheradas del general realista Miguel de la Torre, Bolívar ordenó al general José Antonio Páez y a su Primera División (los legendarios 'Bravos de Apure' y la Legión Británica) realizar un movimiento envolvente por el flanco derecho a través de una estrecha pica.\n\nA pesar del fuego concentrado, la Legión Británica resistió rodilla en tierra y la impetuosa caballería llanera de Páez flanqueó a los realistas, rompiendo sus líneas defensivas.\n\nEn menos de dos horas de combate heroico, el ejército libertador obtuvo una victoria contundente. Carabobo no solo liberó a Caracas y al centro de Venezuela, sino que consolidó la base territorial para que Bolívar marchara a liberar el sur (Ecuador, Perú y Bolivia).",
    'practical_exercise' => 'Menciona a tres héroes que combatieron en la Batalla de Carabobo.',
    'xp_reward' => 50,
    'order_num' => 2
], [
    [
        'question_text' => '¿En qué fecha se libró la histórica Batalla de Carabobo?',
        'question_type' => 'multiple_choice',
        'options' => ['24 de junio de 1821', '5 de julio de 1811', '19 de abril de 1810', '12 de octubre de 1492'],
        'correct_answer' => '24 de junio de 1821',
        'explanation' => '¡Exacto! El 24 de junio de 1821, fecha gloriosa de la patria.'
    ],
    [
        'question_text' => '¿Qué división liderada por José Antonio Páez y la Legión Británica realizó el ataque por el flanco?',
        'question_type' => 'multiple_choice',
        'options' => ['La Primera División (Bravos de Apure)', 'La Guardia Real', 'El Batallón Numancia', 'Los Dragones de la Reina'],
        'correct_answer' => 'La Primera División (Bravos de Apure)',
        'explanation' => '¡Brillante! Los Bravos de Apure de Páez y la Legión Británica decidieron el combate.'
    ]
]);

// 3. Formación Ciudadana y Valores
insertActivityWithQuestions($pdo, [
    'week_number' => 1,
    'grade' => 6,
    'day_of_week' => 'jueves',
    'subject_id' => $subjMap['ciudadania'],
    'title' => 'Derechos Humanos, Deberes y Convivencia Democrática',
    'summary' => 'Comprende el equilibrio fundamental entre los derechos fundamentales y nuestras responsabilidades ciudadanas.',
    'reading_content' => "En 6to grado, nos preparamos para comprender cómo funciona la sociedad y el papel que desempeñamos como ciudadanos.\n\n1. EL PRINCIPIO DE LA DIGNIDAD HUMANA\nTodos los seres humanos nacen libres e iguales en dignidad y derechos. La Declaración Universal de los Derechos Humanos (1948) consagra derechos inalienables como la vida, la educación, la libertad de pensamiento y la protección de la infancia (LOPNNA en Venezuela).\n\n2. LA OTRA CARA DE LA MONEDA: LOS DEBERES CIUDADANOS\nUn derecho siempre viene acompañado de un deber correspondiente. No puede existir una sociedad sana donde todos exijan derechos pero nadie cumpla sus obligaciones:\n• Si tengo derecho a estudiar en un colegio limpio ➔ Tengo el deber de cuidar los pupitres y botar la basura en su lugar.\n• Si tengo derecho a expresar mi opinión ➔ Tengo el deber de escuchar y respetar las opiniones de los demás sin ofender.\n• Si tengo derecho a vivir en una comunidad segura ➔ Tengo el deber de respetar las normas cívicas y colaborar con mis vecinos.\n\nLa democracia no es solo votar: es vivir con honestidad, justicia, solidaridad y responsabilidad en nuestro día a día.",
    'practical_exercise' => 'Identifica tres derechos que disfrutes diariamente en tu vida y escribe al lado el deber correspondiente para cada uno.',
    'xp_reward' => 50,
    'order_num' => 3
], [
    [
        'question_text' => '¿Cuál es la relación indispensable entre los derechos y los deberes ciudadanos?',
        'question_type' => 'multiple_choice',
        'options' => ['A cada derecho le corresponde un deber y una responsabilidad ciudadana', 'Los derechos importan, pero los deberes no son necesarios', 'Los deberes son solo para los adultos y los ancianos', 'Solo los gobernantes tienen deberes'],
        'correct_answer' => 'A cada derecho le corresponde un deber y una responsabilidad ciudadana',
        'explanation' => '¡Brillante! El equilibrio entre derechos y deberes es el pilar de la armonía social.'
    ],
    [
        'question_text' => 'Si tienes el derecho a expresar tus ideas libremente, ¿cuál es tu deber correspondiente?',
        'question_type' => 'multiple_choice',
        'options' => ['Escuchar y respetar los puntos de vista de otros con tolerancia', 'Obligar a todos a pensar como tú', 'Burlarte de quienes opinen diferente', 'Gritar para que nadie más hable'],
        'correct_answer' => 'Escuchar y respetar los puntos de vista de otros con tolerancia',
        'explanation' => '¡Exacto! La libertad de expresión exige tolerancia y respeto mutuo.'
    ],
    [
        'question_text' => 'Escribe una propuesta concreta sobre cómo los jóvenes pueden mejorar la convivencia y el cuidado de los espacios públicos en su comunidad.',
        'question_type' => 'reflexive',
        'correct_answer' => '',
        'explanation' => 'Tu papá evaluará tu propuesta ciudadana y tu pensamiento ético.'
    ]
]);

// --- VIERNES 6TO ---
// 1. Gestión de Riesgos La Guaira (Tsunamis)
insertActivityWithQuestions($pdo, [
    'week_number' => 1,
    'grade' => 6,
    'day_of_week' => 'viernes',
    'subject_id' => $subjMap['riesgo_laguaira'],
    'title' => 'Sismos Marinos y Alertas de Tsunami en el Litoral Central',
    'summary' => 'Aprende qué es un tsunami, por qué la retirada del mar es la alarma vital y cómo evacuar.',
    'reading_content' => "Alexander, al vivir en la franja costera de La Guaira frente al Mar Caribe, es fundamental comprender qué es un TSUNAMI (ola de puerto o maremoto).\n\n¿CÓMO SE ORIGINA UN TSUNAMI?\nCuando ocurre un terremoto de gran magnitud (habitualmente superior a 7,0 grados) con epicentro bajo el fondo del mar o cerca de la costa, el lecho marino se deforma o desplaza verticalmente de golpe. Esto empuja toda la columna de agua oceánica hacia arriba, generando una serie de ondas que viajan a cientos de kilómetros por hora.\n\nAl acercarse a aguas poco profundas cerca de la playa, la ola pierde velocidad pero CRECE ENORMEMENTE EN ALTURA y penetra en tierra firme con una fuerza hidráulica colosal.\n\nLA SEÑAL NATURAL MÁS CRÍTICA:\nSi ocurre un sismo muy fuerte en La Guaira (que dificulte mantenerse de pie) y minutos después observas que EL MAR SE RETIRA DE FORMA ANORMAL hacia adentro dejando el fondo marino descubierto con peces saltando:\n¡ESA ES LA ALARMA INMEDIATA DE LA NATURALEZA!\n\nREGLA DE EVACUACIÓN EN LA GUAIRA:\n• NO esperar sirenas, mensajes de texto ni publicaciones en redes.\n• NO acercarse a la orilla a mirar los peces.\n• EVACUAR A PIE DE INMEDIATO hacia COTAS ALTAS (subir a zonas que estén a más de 20 o 30 metros sobre el nivel del mar, hacia las laderas seguras de la montaña).\n• Permanecer en la zona alta hasta que las autoridades de Protección Civil confirmen que el tren de olas ha cesado (un tsunami no es una sola ola, son varias olas separadas por minutos).",
    'practical_exercise' => 'Identifica en tu comunidad cuál es la ruta más rápida para subir a un terreno alto y seguro lejos de la orilla de la playa.',
    'xp_reward' => 70,
    'order_num' => 1
], [
    [
        'question_text' => 'Si tras un terremoto fuerte en la costa el mar se retira repentinamente hacia adentro de forma anormal, ¿qué se debe hacer?',
        'question_type' => 'multiple_choice',
        'options' => ['Evacuar inmediatamente a pie hacia terrenos altos y seguros', 'Ir a la playa a tomar fotos y ver los peces', 'Entrar al agua a nadar', 'Esperar a ver si llega un mensaje por WhatsApp'],
        'correct_answer' => 'Evacuar inmediatamente a pie hacia terrenos altos y seguros',
        'explanation' => '¡Absolutamente correcto! La retirada del agua es el aviso de que viene el impacto de la masa líquida.'
    ],
    [
        'question_text' => 'Un tsunami está compuesto por una única ola solitaria y después el peligro termina.',
        'question_type' => 'true_false',
        'options' => ['Verdadero', 'Falso'],
        'correct_answer' => 'Falso',
        'explanation' => '¡Falso! Un tsunami es un tren de varias ondas, y con frecuencia la segunda o tercera ola puede ser aún más grande que la primera.'
    ],
    [
        'question_text' => 'Explica por qué evacuar a pie hacia zonas altas suele ser mucho más seguro y rápido que intentar escapar en vehículo por avenidas costeras en una emergencia.',
        'question_type' => 'reflexive',
        'correct_answer' => '',
        'explanation' => 'Tu papá evaluará tu razonamiento sobre el colapso vial y la movilidad en emergencias.'
    ]
]);

// 2. Matemáticas
insertActivityWithQuestions($pdo, [
    'week_number' => 1,
    'grade' => 6,
    'day_of_week' => 'viernes',
    'subject_id' => $subjMap['matematicas'],
    'title' => 'Regla de Tres Simple Directa: Proporciones en la Práctica',
    'summary' => 'Aprende a calcular proporciones para recetas, distancias y precios.',
    'reading_content' => "La regla de tres simple es una de las fórmulas más útiles para resolver situaciones de la vida real cuando tres datos son conocidos y queremos averiguar el cuarto.\n\nREGLA DIRECTA:\nSi 4 paquetes de galletas cuestan 6 dólares, ¿cuánto costarán 10 paquetes de las mismas galletas?\n\nPlanteamiento:\n   4 paquetes  ───>  6 dólares\n  10 paquetes  ───>  \$x dólares\n\nMétodo cruzado:\nMultiplicamos en diagonal los dos datos conocidos y dividimos entre el que queda en diagonal con la \$x:\n  \$x = (10 × 6) ÷ 4\n  \$x = 60 ÷ 4 = 15 dólares.\n\n¡Fácil, rápido y exacto!",
    'practical_exercise' => 'Si un automóvil recorre 150 km en 2 horas a velocidad constante, ¿cuántos kilómetros recorrerá en 5 horas?',
    'xp_reward' => 50,
    'order_num' => 2
], [
    [
        'question_text' => 'Si 3 cuadernos cuestan 9 dólares, ¿cuánto costarán 5 cuadernos?',
        'question_type' => 'multiple_choice',
        'options' => ['15 dólares', '12 dólares', '20 dólares', '18 dólares'],
        'correct_answer' => '15 dólares',
        'explanation' => '¡Muy bien! (5 × 9) / 3 = 45 / 3 = 15 dólares.'
    ],
    [
        'question_text' => 'Si un ciclista recorre 20 km en 1 hora, ¿cuántos kilómetros recorrerá en 3 horas al mismo ritmo?',
        'question_type' => 'multiple_choice',
        'options' => ['60 km', '40 km', '50 km', '80 km'],
        'correct_answer' => '60 km',
        'explanation' => '¡Exacto! 20 × 3 = 60 km.'
    ]
]);

// 3. Biología y Ecosistemas
insertActivityWithQuestions($pdo, [
    'week_number' => 1,
    'grade' => 6,
    'day_of_week' => 'viernes',
    'subject_id' => $subjMap['biologia'],
    'title' => 'El Sistema Inmunológico: El Ejército Microscópico del Cuerpo',
    'summary' => 'Explora cómo los glóbulos blancos, anticuerpos y vacunas defienden nuestra salud las 24 horas del día.',
    'reading_content' => "En cada segundo del día, nuestro cuerpo está en contacto con millones de microorganismos (bacterias, virus y hongos). Sin embargo, casi nunca nos enfermamos gracias a uno de los sistemas biológicos más sofisticados: el SISTEMA INMUNOLÓGICO.\n\nLÍNEAS DE DEFENSA DEL ORGANISMO:\n1. Barreras Físicas y Químicas: La piel, el sudor, las lágrimas y las mucosas impiden físicamente la entrada de gérmenes.\n2. La Respuesta Innata (Glóbulos Blancos / Fagocitos): Si un microbio entra por una herida, los macrófagos y neutrófilos viajan por la sangre y 'se comen' literalmente a los invasores.\n3. La Respuesta Adaptativa (Linfocitos B y T): Producen ANTICUERPOS, que son proteínas especializadas en forma de 'Y' diseñadas con precisión milimétrica para neutralizar un patógeno específico.\n\nEL MILAGRO CIENTÍFICO DE LAS VACUNAS:\nLas vacunas le enseñan al sistema inmune la 'foto' de un virus debilitado o inofensivo. Los linfocitos generan células de memoria inmunológica. Así, si el virus real intenta entrar en el futuro, el cuerpo lo elimina inmediatamente antes de que cause daño.",
    'practical_exercise' => 'Explica a un familiar con una analogía creativa (por ejemplo, un castillo medieval o un equipo de fútbol) cómo se defiende el sistema inmunológico.',
    'xp_reward' => 50,
    'order_num' => 3
], [
    [
        'question_text' => '¿Qué son los anticuerpos producidos por los linfocitos en el sistema inmune?',
        'question_type' => 'multiple_choice',
        'options' => ['Proteínas específicas en forma de Y que neutralizan a los agentes invasores', 'Bacterias dañinas que causan infecciones', 'Vitaminas que tomamos en el desayuno', 'Células musculares'],
        'correct_answer' => 'Proteínas específicas en forma de Y que neutralizan a los agentes invasores',
        'explanation' => '¡Perfecto! Los anticuerpos son las armas de alta precisión del sistema inmunitario.'
    ],
    [
        'question_text' => '¿Cuál es la función principal de las vacunas en la protección de la salud humana?',
        'question_type' => 'multiple_choice',
        'options' => ['Entrenar al sistema inmunológico para crear memoria defensiva sin enfermarnos', 'Curar el dolor de cabeza al instante', 'Reemplazar a los glóbulos blancos en la sangre', 'Eliminar la piel para que respire'],
        'correct_answer' => 'Entrenar al sistema inmunológico para crear memoria defensiva sin enfermarnos',
        'explanation' => '¡Extraordinario! Las vacunas generan memoria inmunológica protectora.'
    ],
    [
        'question_text' => '¿Qué hábitos diarios (alimentación, sueño, higiene) fortalecen nuestro sistema inmunológico y nos mantienen sanos?',
        'question_type' => 'reflexive',
        'correct_answer' => '',
        'explanation' => 'Tu papá evaluará tu comprensión sobre los hábitos saludables para el sistema inmune.'
    ]
]);

echo "✅ Batería completa de actividades didácticas y preguntas de Semana 1 cargadas exitosamente.\n";
echo "🎉 ¡Instalación y siembra completadas al 100%!\n";
echo "</pre>";
?>
