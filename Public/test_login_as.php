<?php
// test_login_as.php - development helper to create a session for a given email
// Usage (only for local/dev): GET /Public/test_login_as.php?email=... will set session for that user

// Load config
if (file_exists(__DIR__ . '/../app/config.php')) {
    $config = require __DIR__ . '/../app/config.php';
} elseif (file_exists(__DIR__ . '/../Config.php')) {
    $config = require __DIR__ . '/../Config.php';
} else {
    http_response_code(500);
    echo "Missing config";
    exit;
}

if (($config['environment'] ?? 'production') !== 'development') {
    http_response_code(403);
    echo "Forbidden";
    exit;
}

session_start();
require_once __DIR__ . '/../app/Database.php';
$pdo = Database::getInstance($config);

$email = $_GET['email'] ?? null;
if (!$email) {
    echo "Provide ?email=...";
    exit;
}

$stmt = $pdo->prepare('SELECT * FROM users WHERE email = :email LIMIT 1');
$stmt->execute([':email' => $email]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$user) {
    http_response_code(404);
    echo "User not found";
    exit;
}

// set session values similar to login
$_SESSION['user_id'] = $user['id'];
$_SESSION['user_email'] = $user['email'];
$_SESSION['user_role'] = $user['role'];

header('Content-Type: application/json; charset=utf-8');
echo json_encode(['success' => true, 'user' => $user]);
