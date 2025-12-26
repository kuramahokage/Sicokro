<?php
/**
 * SICOKRO Configuration Example
 * 
 * Copy this file to config.php and adjust the values
 * DO NOT commit config.php to git (it's in .gitignore)
 */

return [
    // Database Configuration
    'db' => [
        'host' => 'localhost',           // Database host
        'dbname' => 'sicokro',          // Database name
        'user' => 'root',               // Database username
        'pass' => '',                   // Database password
        'charset' => 'utf8mb4',         // Character set
        'port' => 3306,                 // Database port (optional)
    ],
    
    // Upload Configuration
    'uploads_dir' => __DIR__ . '/../public/uploads/bukti_pembayaran',
    'max_upload_size' => 5 * 1024 * 1024, // 5MB in bytes
    'allowed_extensions' => ['jpg', 'jpeg', 'png', 'pdf'],
    
    // Application Configuration
    'base_url' => 'http://localhost/sicokro/public',
    'app_name' => 'SICOKRO',
    'app_version' => '1.0.0',
    
    // Session Configuration
    'session_lifetime' => 7200, // 2 hours in seconds
    'session_name' => 'SICOKRO_SESSION',
    
    // Security
    'bcrypt_cost' => 10, // Password hashing cost (10-12 recommended)
    
    // Timezone
    'timezone' => 'Asia/Jakarta',
    
    // Logging
    'enable_logging' => true,
    'log_path' => __DIR__ . '/../logs/error.log',
    
    // Email Configuration (for future use)
    'email' => [
        'enabled' => false,
        'smtp_host' => 'smtp.gmail.com',
        'smtp_port' => 587,
        'smtp_user' => 'your-email@gmail.com',
        'smtp_pass' => 'your-app-password',
        'from_email' => 'noreply@cokroaminoto.sch.id',
        'from_name' => 'SICOKRO System',
    ],
    
    // Environment
    'environment' => 'development', // development, production, staging
    'debug' => true, // Set to false in production
];