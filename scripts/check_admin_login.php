<?php
// scripts/check_admin_login.php
// Usage: php scripts/check_admin_login.php email password
if ($argc < 3) {
    echo "Usage: php scripts/check_admin_login.php email password\n";
    exit(1);
}
$email = $argv[1];
$password = $argv[2];
$config = require __DIR__ . '/../Config.php';
$db = $config['db'];
$dsn = sprintf('mysql:host=%s;dbname=%s;port=%s;charset=%s', $db['host'], $db['dbname'], $db['port'] ?? 3306, $db['charset'] ?? 'utf8mb4');
try {
    $pdo = new PDO($dsn, $db['user'], $db['pass'], [PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC]);
} catch (Exception $e) {
    echo "DB connection failed: " . $e->getMessage() . PHP_EOL;
    exit(1);
}
$stmt = $pdo->prepare('SELECT * FROM users WHERE email = :email LIMIT 1');
$stmt->execute([':email'=>$email]);
$user = $stmt->fetch();
if (!$user) {
    echo "User not found for email: $email\n";
    exit(0);
}
echo "Found user id={$user['id']} nama={$user['nama']} role={$user['role']}\n";
$hash = $user['password_hash'] ?? null;
if (!$hash) {
    echo "No password hash stored for this user.\n";
    exit(0);
}
$ok = password_verify($password, $hash);
echo "password_verify for supplied password: " . ($ok ? 'MATCH' : 'NO MATCH') . "\n";
// advise
if (!$ok) {
    echo "To reset the password to 'superadmin123' run:\n";
    echo "php -r \"require 'scripts/create_test_user.php';\"\n";
    echo "Or run this SQL (recommended to use password_hash output):\n";
    $newhash = password_hash('superadmin123', PASSWORD_BCRYPT, ['cost'=>$config['bcrypt_cost'] ?? 10]);
    echo "UPDATE users SET password_hash = '" . $newhash . "' WHERE email = '" . addslashes($email) . "';\n";
}
