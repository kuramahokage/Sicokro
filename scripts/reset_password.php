<?php
// scripts/reset_password.php
// Usage: php scripts/reset_password.php email new_password
if ($argc < 3) {
    echo "Usage: php scripts/reset_password.php email new_password\n";
    exit(1);
}
$email = $argv[1];
$newPassword = $argv[2];
$config = require __DIR__ . '/../Config.php';
$db = $config['db'];
$dsn = sprintf('mysql:host=%s;dbname=%s;port=%s;charset=%s', $db['host'], $db['dbname'], $db['port'] ?? 3306, $db['charset'] ?? 'utf8mb4');
try {
    $pdo = new PDO($dsn, $db['user'], $db['pass'], [PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC]);
} catch (Exception $e) {
    echo "DB connection failed: " . $e->getMessage() . PHP_EOL;
    exit(1);
}
$cost = $config['bcrypt_cost'] ?? 10;
$hash = password_hash($newPassword, PASSWORD_BCRYPT, ['cost' => $cost]);
try {
    $stmt = $pdo->prepare('UPDATE users SET password_hash = :hash, updated_at = NOW() WHERE email = :email');
    $stmt->execute([':hash' => $hash, ':email' => $email]);
    if ($stmt->rowCount() > 0) {
        echo "Password updated for $email\n";
    } else {
        echo "No rows updated. Check if user exists: $email\n";
    }
} catch (Exception $e) {
    echo "DB error: " . $e->getMessage() . PHP_EOL;
    exit(1);
}
