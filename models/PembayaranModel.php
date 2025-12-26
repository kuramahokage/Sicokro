<?php
// models/PembayaranModel.php
class PembayaranModel {
    private $pdo;

    public function __construct(PDO $pdo) {
        $this->pdo = $pdo;
    }

    // Create pembayaran record
    public function create(array $data) {
        $stmt = $this->pdo->prepare(
            "INSERT INTO pembayaran (tagihan_id, sekolah_id, jumlah, metode, bukti, status, created_at)
            VALUES (:tagihan_id, :sekolah_id, :jumlah, :metode, :bukti, :status, NOW())"
        );
        $stmt->execute([
            ':tagihan_id' => $data['tagihan_id'] ?? null,
            ':sekolah_id' => $data['sekolah_id'] ?? null,
            ':jumlah'     => $data['jumlah'] ?? 0,
            ':metode'     => $data['metode'] ?? 'Transfer Bank',
            ':bukti'      => $data['bukti'] ?? null,
            ':status'     => $data['status'] ?? 'pending'
        ]);
        return $this->pdo->lastInsertId();
    }

    // List payments by sekolah
    public function listBySekolah(int $sekolah_id, int $limit = 100) {
        $stmt = $this->pdo->prepare(
            "SELECT p.*, t.kode_tagihan, t.deskripsi as tagihan_deskripsi
             FROM pembayaran p
             LEFT JOIN tagihan t ON p.tagihan_id = t.id
             WHERE p.sekolah_id = :sek
             LIMIT :limit"
        );
        $stmt->bindValue(':sek', $sekolah_id, PDO::PARAM_INT);
        $stmt->bindValue(':limit', (int)$limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Confirm payment (used by bendahara) - change status to 'confirmed'
    public function confirm(int $pembayaran_id) {
        $stmt = $this->pdo->prepare("UPDATE pembayaran SET status = 'confirmed' WHERE id = :id");
        return $stmt->execute([':id' => $pembayaran_id]);
    }
}