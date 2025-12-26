<?php
if ($argc < 2) { echo "Usage: php scripts/desc_table.php <table>\n"; exit(1); }
$table = $argv[1];
$config = require __DIR__ . '/../Config.php';
$dsn = sprintf('mysql:host=%s;dbname=%s;port=%s;charset=%s', $config['db']['host'], $config['db']['dbname'], $config['db']['port'] ?? 3306, $config['db']['charset'] ?? 'utf8mb4');
$pdo = new PDO($dsn, $config['db']['user'], $config['db']['pass']);
$stmt = $pdo->query("SHOW CREATE TABLE `".addslashes($table)."`");
$row = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$row) { echo "Table not found\n"; exit(1); }
print_r($row);
