<?php
// scripts/seed_smp_cokro.php
// Seeds example data for SMP Cokroaminoto Banjarnegara and related tables
require __DIR__ . '/../Config.php';
require __DIR__ . '/../app/Database.php';
$config = require __DIR__ . '/../Config.php';
$pdo = Database::getInstance($config);
try {
    $pdo->beginTransaction();

    // Create sekolah if not exists
    $stmt = $pdo->prepare('SELECT id FROM sekolah WHERE nama = :nama LIMIT 1');
    $stmt->execute([':nama' => 'SMP Cokroaminoto Banjarnegara']);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($row) {
        $sekolah_id = $row['id'];
        echo "Sekolah already exists id={$sekolah_id}\n";
    } else {
        $stmt = $pdo->prepare('INSERT INTO sekolah (yayasan_id, nama, jenjang, alamat, kepala_sekolah, status, created_at) VALUES (:yayasan, :nama, :jenjang, :alamat, :kepsek, :status, NOW())');
        $stmt->execute([
            ':yayasan' => 1,
            ':nama' => 'SMP Cokroaminoto Banjarnegara',
            ':jenjang' => 'SMP',
            ':alamat' => 'Jl. Merdeka No. 10, Banjarnegara',
            ':kepsek' => 'Drs. H. Ahmad Sukarno',
            ':status' => 'aktif'
        ]);
        $sekolah_id = $pdo->lastInsertId();
        echo "Created sekolah id={$sekolah_id}\n";
    }

    // Seed sample siswa
    $students = [
        ['nis'=>'SMP2025001','nama'=>'Ali bin Ahmad','kelas'=>'7A'],
        ['nis'=>'SMP2025002','nama'=>'Budi Santoso','kelas'=>'7B'],
        ['nis'=>'SMP2025003','nama'=>'Citra Lestari','kelas'=>'8A'],
        ['nis'=>'SMP2025004','nama'=>'Dewi Kartika','kelas'=>'8B']
    ];
    $stmt = $pdo->prepare('INSERT INTO siswa (sekolah_id, nis, nama, kelas, created_at) VALUES (:sek, :nis, :nama, :kelas, NOW())');
    foreach ($students as $s) {
        // avoid duplicates by nis
        $chk = $pdo->prepare('SELECT id FROM siswa WHERE nis = :nis LIMIT 1');
        $chk->execute([':nis'=>$s['nis']]);
        if ($chk->fetch()) continue;
        $stmt->execute([':sek'=>$sekolah_id, ':nis'=>$s['nis'], ':nama'=>$s['nama'], ':kelas'=>$s['kelas']]);
    }
    echo "Seeded siswa\n";

    // Seed RKJM
    $stmt = $pdo->prepare('INSERT INTO rkjm (sekolah_id, periode_mulai, periode_selesai, visi, misi, anggaran_ringkasan, created_at) VALUES (:sek, :start, :end, :visi, :misi, :anggaran, NOW())');
    $stmt->execute([':sek'=>$sekolah_id, ':start'=>'2025-01-01', ':end'=>'2027-12-31', ':visi'=>'Menjadi sekolah unggul berbasis karakter', ':misi'=>'Meningkatkan kualitas pendidikan', ':anggaran'=>50000000]);
    echo "Seeded rkjm\n";

    // Seed RKT (annual plans)
    $stmt = $pdo->prepare('INSERT INTO rkt (sekolah_id, tahun, semester, created_at) VALUES (:sek, :tahun, :sem, NOW())');
    $stmt->execute([':sek'=>$sekolah_id, ':tahun'=>2025, ':sem'=>1]);
    $stmt->execute([':sek'=>$sekolah_id, ':tahun'=>2025, ':sem'=>2]);
    echo "Seeded rkt\n";

    // Seed RKAS
    $stmt = $pdo->prepare('INSERT INTO rkas (sekolah_id, tahun, anggaran_total, realisasi_total, created_at) VALUES (:sek, :tahun, :ang, :real, NOW())');
    $stmt->execute([':sek'=>$sekolah_id, ':tahun'=>2025, ':ang'=>15000000, ':real'=>4500000]);
    echo "Seeded rkas\n";

    // Seed aset
    $stmt = $pdo->prepare('INSERT INTO aset (sekolah_id, kode_aset, nama, kategori, lokasi, kondisi, nilai_perolehan, tgl_perolehan, created_at) VALUES (:sek, :kode, :nama, :kat, :lok, :kond, :nilai, :tgl, NOW())');
    $stmt->execute([':sek'=>$sekolah_id, ':kode'=>'AST-001', ':nama'=>'Laptop Guru', ':kat'=>'Elektronik', ':lok'=>'Ruang Guru', ':kond'=>'Baik', ':nilai'=>15000000, ':tgl'=>'2024-06-01']);
    echo "Seeded aset\n";

    // Seed tagihan (SPP) for students
    $slist = $pdo->query('SELECT id, nama FROM siswa WHERE sekolah_id = ' . (int)$sekolah_id)->fetchAll(PDO::FETCH_ASSOC);
    $tagStmt = $pdo->prepare('INSERT INTO tagihan (sekolah_id, siswa_id, kode_tagihan, deskripsi, jumlah, status, tgl_dibuat, due_date, created_at) VALUES (:sek, :siswa, :kode, :desc, :jumlah, :status, :tgl, :due, NOW())');
    foreach ($slist as $s) {
        $tagStmt->execute([':sek'=>$sekolah_id, ':siswa'=>$s['id'], ':kode'=>'SPP-2025-' . $s['id'], ':desc'=>'SPP Bulanan Januari 2025', ':jumlah'=>150000, ':status'=>'unpaid', ':tgl'=>'2025-01-01', ':due'=>'2025-01-15']);
    }
    echo "Seeded tagihan\n";

    // Seed one pembayaran as confirmed for demonstration
    $pStmt = $pdo->prepare('INSERT INTO pembayaran (sekolah_id, tagihan_id, jumlah, metode, bukti, status, created_at) VALUES (:sek, :tag, :jumlah, :metode, :bukti, :status, NOW())');
    $firstTag = $pdo->query('SELECT id FROM tagihan WHERE sekolah_id = ' . (int)$sekolah_id . ' LIMIT 1')->fetchColumn();
    if ($firstTag) {
        $pStmt->execute([':sek'=>$sekolah_id, ':tag'=>$firstTag, ':jumlah'=>150000, ':metode'=>'Transfer Bank', ':bukti'=>null, ':status'=>'confirmed']);
    }
    echo "Seeded pembayaran\n";

    $pdo->commit();
    echo "Seeding complete. Sekolah id={$sekolah_id}\n";
} catch (Exception $e) {
    $pdo->rollBack();
    echo "Seeding failed: " . $e->getMessage() . PHP_EOL;
}
