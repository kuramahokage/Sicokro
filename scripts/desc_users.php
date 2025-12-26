<?php
$config = require __DIR__ . '/../Config.php';
$db = $config['db'];
$dsn = sprintf('mysql:host=%s;dbname=%s;port=%s;charset=%s', $db['host'], $db['dbname'], $db['port'] ?? 3306, $db['charset'] ?? 'utf8mb4');
try {
    $pdo = new PDO($dsn, $db['user'], $db['pass']);
} catch (Exception $e) {
    echo "DB connect error: " . $e->getMessage() . PHP_EOL; exit(1);
}
$stmt = $pdo->query("SHOW CREATE TABLE users");
$row = $stmt->fetch(PDO::FETCH_ASSOC);
print_r($row);
