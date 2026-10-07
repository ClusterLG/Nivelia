<?php
require_once __DIR__ . '/../config/db.php';

$testCases = [
    ['dimas', 'adrian3108'],
    ['moralesponcedimasadrian@gmail.com', 'adrian3108'],
    ['alexander', 'alex123'],
    ['alex', 'alex123'],
    ['alexander@estudio.local', 'alex123'],
    ['papa', 'papa123'],
    ['admin', 'papa123'],
    ['papa@estudio.local', 'papa123']
];

echo "=== PROBANDO AUTENTICACIÓN FLEXIBLE ===\n";
foreach ($testCases as [$loginInput, $password]) {
    $user = findUserForLogin($loginInput);
    if (!$user) {
        echo "❌ Fallo al buscar usuario: '$loginInput'\n";
        continue;
    }
    $pwOk = password_verify($password, $user['password']);
    if ($pwOk) {
        echo "✅ Éxito para '$loginInput' -> Usuario: {$user['name']} (Rol: {$user['role']}, Grado: " . ($user['grade'] ?? 'N/A') . ")\n";
    } else {
        echo "❌ Contraseña inválida para '$loginInput'\n";
    }
}
