<?php
require_once __DIR__ . '/../config/db.php';
$pdo = getDB();

echo "=== PROBANDO FLUJO DE SOLICITUD DE ACCESO ===\n";

// 1. Simular creación de solicitud
$ok = createUserAccessRequest(
    'Valentina González',
    'Mamá Carmen',
    'valentina.gonzalez@ejemplo.com',
    '04121234567',
    4,
    'Hola profesor, deseo ingresar a 4to grado para nivelación en lectura.'
);

echo "1. Creación de solicitud: " . ($ok ? "✅ ÉXITO" : "❌ ERROR") . "\n";

// 2. Consultar solicitudes pendientes
$reqs = getAllUserAccessRequests('pending');
echo "2. Solicitudes pendientes encontradas: " . count($reqs) . "\n";
if (!empty($reqs)) {
    $last = $reqs[0];
    echo "   - Solicitante: {$last['student_name']} ({$last['grade']}to Grado)\n";
    echo "   - Email: {$last['email']}\n";
    echo "   - WhatsApp: {$last['whatsapp']}\n";
    echo "   - Estado: {$last['status']}\n";

    // 3. Simular aprobación
    $assignedPass = 'nivelia2026';
    $hashed = password_hash($assignedPass, PASSWORD_BCRYPT);
    $pdo->prepare("INSERT INTO users (name, email, password, role, grade, avatar, total_xp, streak_days, created_at) VALUES (:n, :e, :p, 'student', :g, '🌟', 0, 1, NOW())")
        ->execute(['n' => $last['student_name'], 'e' => $last['email'], 'p' => $hashed, 'g' => $last['grade']]);
    $newUserId = $pdo->lastInsertId();
    updateUserAccessRequestStatus($last['id'], 'approved');

    echo "3. Aprobación y creación de usuario ID #$newUserId: ✅ ÉXITO\n";

    // 4. Probar login del nuevo usuario
    $testUser = findUserForLogin('valentina.gonzalez@ejemplo.com');
    if ($testUser && password_verify('nivelia2026', $testUser['password'])) {
        echo "4. Login del nuevo usuario con email y clave: ✅ ÉXITO\n";
    } else {
        echo "4. Login del nuevo usuario: ❌ ERROR\n";
    }

    // 5. Probar login por nombre
    $testUser2 = findUserForLogin('Valentina González');
    if ($testUser2 && password_verify('nivelia2026', $testUser2['password'])) {
        echo "5. Login del nuevo usuario por Nombre completo: ✅ ÉXITO\n";
    } else {
        echo "5. Login del nuevo usuario por Nombre: ❌ ERROR\n";
    }

    // Limpieza de datos de prueba
    $pdo->prepare("DELETE FROM users WHERE id = :id")->execute(['id' => $newUserId]);
    $pdo->prepare("DELETE FROM user_access_requests WHERE id = :id")->execute(['id' => $last['id']]);
    echo "6. Limpieza de datos de prueba: ✅ OK\n";
}
