<?php
require_once __DIR__ . '/config/db.php';

// Registrar desconexión y tiempo total en línea
logSessionLogout();

session_unset();
session_destroy();

session_start();
$_SESSION['flash_success'] = 'Has cerrado sesión con éxito. ¡Nos vemos en la próxima lección!';
header('Location: ' . getBaseUrl() . 'login.php');
exit;
