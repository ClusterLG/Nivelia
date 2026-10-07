<?php
/**
 * Migración para Notificaciones, Logs de Sesión, Ajustes y Alertas Pedagógicas
 */

require_once __DIR__ . '/../config/db.php';
$pdo = getDB();

echo "========================================================\n";
echo "INICIANDO MIGRACIÓN DE TABLAS DE SESIONES Y NOTIFICACIONES\n";
echo "========================================================\n";

// 1. Tabla de Notificaciones del Administrador
$pdo->exec("
CREATE TABLE IF NOT EXISTS admin_notifications (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  type ENUM('submission', 'alert_reinforcement', 'session', 'system') NOT NULL DEFAULT 'submission',
  title VARCHAR(255) NOT NULL,
  message TEXT NOT NULL,
  link VARCHAR(255) NULL,
  is_read TINYINT(1) DEFAULT 0,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  KEY (user_id),
  KEY (is_read),
  KEY (created_at),
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
");
echo "1. Tabla admin_notifications verificada/creada.\n";

// 2. Tabla de Logs de Sesión
$pdo->exec("
CREATE TABLE IF NOT EXISTS user_session_logs (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  session_token VARCHAR(64) NOT NULL,
  ip_address VARCHAR(45) NULL,
  user_agent VARCHAR(255) NULL,
  login_at DATETIME NOT NULL,
  last_activity_at DATETIME NOT NULL,
  logout_at DATETIME NULL,
  duration_seconds INT DEFAULT 0,
  status ENUM('active', 'closed', 'expired') DEFAULT 'active',
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  KEY (user_id),
  KEY (session_token),
  KEY (status),
  KEY (login_at),
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
");
echo "2. Tabla user_session_logs verificada/creada.\n";

// 3. Tabla de Configuraciones del Sistema (Semana Activa, etc.)
$pdo->exec("
CREATE TABLE IF NOT EXISTS system_settings (
  setting_key VARCHAR(50) PRIMARY KEY,
  setting_value TEXT NOT NULL,
  updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
");

$pdo->exec("
INSERT IGNORE INTO system_settings (setting_key, setting_value) VALUES
('active_week_5to', '1'),
('active_week_6to', '1'),
('current_lapso', '1')
");
echo "3. Tabla system_settings verificada/creada con valores iniciales.\n";

echo "========================================================\n";
echo "MIGRACIÓN COMPLETADA EXITOSAMENTE.\n";
echo "========================================================\n";
