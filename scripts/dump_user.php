<?php
$config = require __DIR__ . '/../Config.php';
$db = $config['db'];
$dsn = sprintf('mysql:host=%s;dbname=%s;port=%s;charset=%s', $db['host'], $db['dbname'], $db['port'] ?? 3306, $db['charset'] ?? 'utf8mb4');
try {
    $pdo = new PDO($dsn, $db['user'], $db['pass']);
} catch (Exception $e) {
    echo "DB connect error: " . $e->getMessage() . PHP_EOL;
    exit(1);
}
$email = 'apitest+local@local';
$stmt = $pdo->prepare('SELECT * FROM users WHERE email = :email');
$stmt->execute([':email'=>$email]);
$row = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$row) { echo "No user found\n"; exit(1); }
print_r($row);
