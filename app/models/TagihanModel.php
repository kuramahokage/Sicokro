<?php
// app/models/TagihanModel.php (copy)
class TagihanModel {
    private $pdo;

    public function __construct(PDO $pdo) {
        $this->pdo = $pdo;
    }

    public function getById(int $id) {
        $stmt = $this->pdo->prepare("SELECT t.*, s.nama AS nama_siswa FROM tagihan t LEFT JOIN siswa s ON t.siswa_id = s.id WHERE t.id = :id");
        $stmt->execute([':id'=>$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function listBySekolahAndPeriode(int $sekolah_id, string $periode = null) {
        $sql = "SELECT t.*, s.nama AS siswa_nama FROM tagihan t LEFT JOIN siswa s ON t.siswa_id = s.id WHERE t.sekolah_id = :sekolah_id";
        $params = [':sekolah_id' => $sekolah_id];
        if ($periode) {
            $start = date('Y-m-01', strtotime($periode . '-01'));
            $end = date('Y-m-t', strtotime($periode . '-01'));
            $sql .= " AND t.tgl_dibuat BETWEEN :start AND :end";
            $params[':start'] = $start;
            $params[':end'] = $end;
        }
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function create(array $data) {
        $stmt = $this->pdo->prepare(
            "INSERT INTO tagihan (sekolah_id, siswa_id, kode_tagihan, deskripsi, jumlah, tgl_dibuat, due_date, status, created_at)
             VALUES (:sekolah_id, :siswa_id, :kode_tagihan, :deskripsi, :jumlah, :tgl_dibuat, :due_date, :status, NOW())"
        );
        $stmt->execute([
            ':sekolah_id'=>$data['sekolah_id'],
            ':siswa_id'=>$data['siswa_id'] ?? null,
            ':kode_tagihan'=>$data['kode_tagihan'] ?? null,
            ':deskripsi'=>$data['deskripsi'] ?? null,
            ':jumlah'=>$data['jumlah'] ?? 0,
            ':tgl_dibuat'=>$data['tgl_dibuat'] ?? date('Y-m-d'),
            ':due_date'=>$data['due_date'] ?? null,
            ':status'=>$data['status'] ?? 'unpaid'
        ]);
        return $this->pdo->lastInsertId();
    }

    public function updateStatus(int $id, string $status) {
        $stmt = $this->pdo->prepare("UPDATE tagihan SET status = :status WHERE id = :id");
        return $stmt->execute([':status'=>$status, ':id'=>$id]);
    }

    public function getTotalPaid(int $tagihan_id) {
        $stmt = $this->pdo->prepare("SELECT COALESCE(SUM(jumlah),0) AS total_paid FROM pembayaran WHERE tagihan_id = :tid AND status = 'confirmed'");
        $stmt->execute([':tid'=>$tagihan_id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return (float)($row['total_paid'] ?? 0);
    }
}
