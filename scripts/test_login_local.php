<?php
$config = require __DIR__ . '/../Config.php';
$db = $config['db'];
$dsn = sprintf('mysql:host=%s;dbname=%s;port=%s;charset=%s', $db['host'], $db['dbname'], $db['port'] ?? 3306, $db['charset'] ?? 'utf8mb4');
$pdo = new PDO($dsn, $db['user'], $db['pass']);
$email = 'apitest+local@local';
$password = 'Test1234!';
$stmt = $pdo->prepare('SELECT * FROM users WHERE email = :email LIMIT 1');
$stmt->execute([':email'=>$email]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$user) { echo "user not found\n"; exit(1); }
var_dump(isset($user['password_hash']), $user['password_hash']);
var_dump(password_verify($password, $user['password_hash']));
