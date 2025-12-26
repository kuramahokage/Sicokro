<?php
$config = require __DIR__ . '/../Config.php';
require __DIR__ . '/../app/Database.php';
$pdo = Database::getInstance($config);
$stmt = $pdo->query('SELECT * FROM yayasan');
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
print_r($rows);
