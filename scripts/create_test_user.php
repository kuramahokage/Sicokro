<?php
// scripts/create_test_user.php
// Usage: php scripts/create_test_user.php

$config = require __DIR__ . '/../Config.php';
$db = $config['db'];
$dsn = sprintf('mysql:host=%s;dbname=%s;port=%s;charset=%s', $db['host'], $db['dbname'], $db['port'] ?? 3306, $db['charset'] ?? 'utf8mb4');

try {
    $pdo = new PDO($dsn, $db['user'], $db['pass'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
} catch (Exception $e) {
    echo "DB connection failed: " . $e->getMessage() . PHP_EOL;
    exit(1);
}

$email = 'apitest+local@local';
$password = 'Test1234!';
$nama = 'API Test User';
$role = 'super_admin';
$sekolah_id = null; // super admin
$cost = $config['bcrypt_cost'] ?? 10;
$hash = password_hash($password, PASSWORD_BCRYPT, ['cost' => $cost]);

try {
    // check if user exists
    $stmt = $pdo->prepare('SELECT id FROM users WHERE email = :email');
    $stmt->execute([':email' => $email]);
    $row = $stmt->fetch();
    if ($row) {
        $id = $row['id'];
        $stmt = $pdo->prepare('UPDATE users SET password_hash = :hash, nama = :nama, role = :role, sekolah_id = :sekolah WHERE id = :id');
        $stmt->execute([':hash'=>$hash, ':nama'=>$nama, ':role'=>$role, ':sekolah'=> $sekolah_id, ':id'=>$id]);
        echo "Updated existing user id={$id}\n";
    } else {
        $stmt = $pdo->prepare('INSERT INTO users (email, password_hash, nama, role, sekolah_id, created_at) VALUES (:email, :hash, :nama, :role, :sekolah, NOW())');
        $stmt->execute([':email'=>$email, ':hash'=>$hash, ':nama'=>$nama, ':role'=>$role, ':sekolah'=>$sekolah_id]);
        $id = $pdo->lastInsertId();
        echo "Created user id={$id}\n";
    }
    echo "Credentials:\n  email: {$email}\n  password: {$password}\n";
    exit(0);
} catch (Exception $e) {
    echo "DB error: " . $e->getMessage() . PHP_EOL;
    exit(1);
}
