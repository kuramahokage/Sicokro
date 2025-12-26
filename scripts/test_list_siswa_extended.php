<?php
$config = require __DIR__ . '/../Config.php';
require __DIR__ . '/../app/Database.php';
$pdo = Database::getInstance($config);
$sekolahId = 1;
$sql = "
    SELECT s.id, s.nama, s.kelas, s.tahun_masuk,
           COALESCE(t.total_tagihan, 0) AS total_tagihan,
           COALESCE(p.total_paid, 0) AS total_paid,
           (COALESCE(t.total_tagihan,0) - COALESCE(p.total_paid,0)) AS tunggakan
    FROM siswa s
    LEFT JOIN (
        SELECT siswa_id, SUM(jumlah) AS total_tagihan
        FROM tagihan
        WHERE sekolah_id = :sek
        GROUP BY siswa_id
    ) t ON t.siswa_id = s.id
    LEFT JOIN (
        SELECT t.siswa_id AS siswa_id, SUM(p.jumlah) AS total_paid
        FROM pembayaran p
        JOIN tagihan t ON p.tagihan_id = t.id
        WHERE p.status = 'confirmed' AND t.sekolah_id = :sek
        GROUP BY t.siswa_id
    ) p ON p.siswa_id = s.id
    WHERE s.sekolah_id = :sek
    ORDER BY s.nama
";
try {
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':sek' => $sekolahId]);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode(['count'=>count($rows), 'rows'=>$rows], JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE);
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
