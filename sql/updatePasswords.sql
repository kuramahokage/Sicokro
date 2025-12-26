-- SICOKRO - Update Default Passwords
-- File ini berisi SQL untuk update password default user
-- Password: admin123

USE sicokro;

-- Update semua user dengan password hash untuk 'admin123'
-- Hash ini dihasilkan dengan bcrypt cost 10
-- Jalankan script ini jika password di database belum di-set dengan benar

UPDATE users 
SET password_hash = '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi'
WHERE email IN (
    'admin@cokroaminoto.sch.id',
    'admin_smp@cokroaminoto.sch.id',
    'bendahara_smp@cokroaminoto.sch.id',
    'admin_sma@cokroaminoto.sch.id'
);

-- Verify update
SELECT 
    id,
    nama,
    email,
    role,
    CASE 
        WHEN password_hash = '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi' 
        THEN 'PASSWORD UPDATED' 
        ELSE 'OLD PASSWORD' 
    END as status
FROM users
WHERE email IN (
    'admin@cokroaminoto.sch.id',
    'admin_smp@cokroaminoto.sch.id',
    'bendahara_smp@cokroaminoto.sch.id',
    'admin_sma@cokroaminoto.sch.id'
);

-- Tambah API token untuk testing (optional)
-- Token ini bisa digunakan untuk testing API dengan Bearer authentication
UPDATE users 
SET api_token = CONCAT('token_', MD5(CONCAT(email, NOW())))
WHERE api_token IS NULL;

-- Verify API tokens
SELECT 
    id,
    nama,
    email,
    role,
    api_token
FROM users
LIMIT 10;

-- ===== INFORMASI PENTING =====
-- Default Password: admin123
-- Hash Algorithm: bcrypt (cost 10)
-- 
-- Untuk generate password hash baru, jalankan:
-- php password_utility.php
-- 
-- Atau gunakan PHP:
-- php -r "echo password_hash('your_password', PASSWORD_BCRYPT, ['cost' => 10]);"
-- ==============================