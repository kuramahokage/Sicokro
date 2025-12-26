<?php
// app/config.php - tiny shim to root Config.php
if (file_exists(__DIR__ . '/../Config.php')) {
    return require __DIR__ . '/../Config.php';
}

// fallback minimal config
return [
    'db' => [
        'host' => 'localhost',
        'dbname' => 'sicokro',
        'user' => 'root',
        'pass' => '',
        'charset' => 'utf8mb4'
    ],
    'uploads_dir' => __DIR__ . '/../public/uploads/bukti_pembayaran',
    'base_url' => 'http://localhost/sicokro/public',
    'environment' => 'development',
    'debug' => true
];
