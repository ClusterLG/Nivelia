<?php
require_once __DIR__ . '/config/db.php';
header('Content-Type: application/json');

if (isLoggedIn()) {
    updateSessionHeartbeat();
    echo json_encode(['status' => 'ok', 'logged_in' => true]);
} else {
    echo json_encode(['status' => 'guest', 'logged_in' => false]);
}
