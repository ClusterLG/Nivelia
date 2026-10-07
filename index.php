<?php
require_once __DIR__ . '/config/db.php';

if (isLoggedIn()) {
    $user = currentUser();
    if ($user['role'] === 'admin') {
        header('Location: admin/index.php');
    } else {
        header('Location: student/dashboard.php');
    }
    exit;
}

// Redirigir a la pantalla de comenzar sesión
header('Location: login.php');
exit;
