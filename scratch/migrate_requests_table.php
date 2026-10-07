<?php
require_once __DIR__ . '/../config/db.php';
$pdo = getDB();

$isPg = ($pdo->getAttribute(PDO::ATTR_DRIVER_NAME) === 'pgsql');

if ($isPg) {
    $sql = "CREATE TABLE IF NOT EXISTS user_access_requests (
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
    );";
} else {
    $sql = "CREATE TABLE IF NOT EXISTS user_access_requests (
        id INT AUTO_INCREMENT PRIMARY KEY,
        student_name VARCHAR(120) NOT NULL,
        guardian_name VARCHAR(120) NULL,
        email VARCHAR(120) NOT NULL,
        whatsapp VARCHAR(50) NOT NULL,
        grade INT NOT NULL,
        message TEXT NULL,
        status VARCHAR(20) NOT NULL DEFAULT 'pending',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        reviewed_at TIMESTAMP NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";
}

$pdo->exec($sql);
echo "Tabla user_access_requests creada/verificada correctamente.\n";
