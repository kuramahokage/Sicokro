<?php
/**
 * Password Utility
 * Script untuk generate password hash dan verifikasi
 * Jalankan di command line: php password_utility.php
 */

echo "========================================\n";
echo "SICOKRO - Password Utility\n";
echo "========================================\n\n";

// Fungsi untuk generate password hash
function generateHash($password) {
    return password_hash($password, PASSWORD_BCRYPT, ['cost' => 10]);
}

// Fungsi untuk verifikasi password
function verifyPassword($password, $hash) {
    return password_verify($password, $hash);
}

// Menu
echo "Pilih operasi:\n";
echo "1. Generate password hash\n";
echo "2. Verifikasi password dengan hash\n";
echo "3. Generate password untuk semua user default\n";
echo "\nPilihan (1-3): ";

$choice = trim(fgets(STDIN));

switch ($choice) {
    case '1':
        echo "\nMasukkan password yang ingin di-hash: ";
        $password = trim(fgets(STDIN));
        
        if (empty($password)) {
            echo "Error: Password tidak boleh kosong!\n";
            exit(1);
        }
        
        $hash = generateHash($password);
        echo "\nPassword: $password\n";
        echo "Hash: $hash\n\n";
        echo "Copy hash ini ke database kolom password_hash\n";
        break;
        
    case '2':
        echo "\nMasukkan password: ";
        $password = trim(fgets(STDIN));
        
        echo "Masukkan hash: ";
        $hash = trim(fgets(STDIN));
        
        if (verifyPassword($password, $hash)) {
            echo "\n✓ Password COCOK dengan hash!\n";
        } else {
            echo "\n✗ Password TIDAK COCOK dengan hash!\n";
        }
        break;
        
    case '3':
        echo "\nGenerate password hash untuk user default...\n\n";
        
        $defaultPassword = 'admin123';
        $hash = generateHash($defaultPassword);
        
        echo "Default Password: $defaultPassword\n";
        echo "Hash yang dihasilkan:\n";
        echo "$hash\n\n";
        
        echo "SQL untuk update password semua user:\n";
        echo "----------------------------------------\n";
        echo "UPDATE users SET password_hash = '$hash' WHERE email IN (\n";
        echo "  'admin@cokroaminoto.sch.id',\n";
        echo "  'admin_smp@cokroaminoto.sch.id',\n";
        echo "  'bendahara_smp@cokroaminoto.sch.id',\n";
        echo "  'admin_sma@cokroaminoto.sch.id'\n";
        echo ");\n";
        echo "----------------------------------------\n\n";
        
        echo "Copy SQL di atas dan jalankan di database Anda.\n";
        echo "Semua user akan memiliki password: $defaultPassword\n";
        break;
        
    default:
        echo "Pilihan tidak valid!\n";
        exit(1);
}

echo "\n========================================\n";
echo "Selesai!\n";
echo "========================================\n";