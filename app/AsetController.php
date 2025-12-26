<?php
// app/AsetController.php
// Basic controller skeleton for Aset module
require_once __DIR__ . '/Database.php';
require_once __DIR__ . '/Auth.php';

class AsetController {
	private $pdo;
	private $auth;
	private $cfg;

	public function __construct(array $config) {
		$this->cfg = $config;
		$this->pdo = Database::getInstance($config);
		$this->auth = new Auth($this->pdo);
		if (!$this->auth->check()) {
			$this->jsonResponse(['error' => 'Unauthorized'], 401);
		}
	}

	// Example: list assets for school
	public function listAset($sekolah_id = null) {
		$user = $this->auth->user();
		$sekolah_id = $sekolah_id ?? ($user['sekolah_id'] ?? null);
		if (!$sekolah_id) return $this->jsonResponse(['error' => 'sekolah_id wajib diisi'], 400);

		$stmt = $this->pdo->prepare("SELECT * FROM aset WHERE sekolah_id = :sek ORDER BY id DESC");
		$stmt->execute([':sek' => $sekolah_id]);
		$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
		$this->jsonResponse(['data' => $rows]);
	}

	// Create aset (admin_sekolah or super_admin)
	public function createAset(array $data) {
		if (!$this->auth->hasRole('admin_sekolah') && !$this->auth->hasRole('super_admin')) {
			$this->jsonResponse(['error'=>'Forbidden'], 403);
		}
		$sekolah_id = $data['sekolah_id'] ?? $this->auth->user()['sekolah_id'] ?? null;
		if (!$sekolah_id || empty($data['nama'])) {
			$this->jsonResponse(['error'=>'sekolah_id dan nama wajib diisi'], 400);
		}
		try {
			$stmt = $this->pdo->prepare("INSERT INTO aset (sekolah_id, kode_aset, nama, kategori, lokasi, kondisi, nilai_perolehan, tgl_perolehan, created_at) VALUES (:sekolah_id, :kode_aset, :nama, :kategori, :lokasi, :kondisi, :nilai_perolehan, :tgl_perolehan, NOW())");
			$stmt->execute([
				':sekolah_id' => $sekolah_id,
				':kode_aset' => $data['kode_aset'] ?? null,
				':nama' => $data['nama'],
				':kategori' => $data['kategori'] ?? null,
				':lokasi' => $data['lokasi'] ?? null,
				':kondisi' => $data['kondisi'] ?? null,
				':nilai_perolehan' => $data['nilai_perolehan'] ?? 0,
				':tgl_perolehan' => $data['tgl_perolehan'] ?? null
			]);
			$id = $this->pdo->lastInsertId();
			$this->jsonResponse(['id'=>$id], 201);
		} catch (Exception $e) {
			$this->jsonResponse(['error'=>'Gagal menyimpan aset: '.$e->getMessage()], 500);
		}
	}

	// Update aset
	public function updateAset(int $id, array $data) {
		if (!$this->auth->hasRole('admin_sekolah') && !$this->auth->hasRole('super_admin')) {
			$this->jsonResponse(['error'=>'Forbidden'], 403);
		}
		$fields = [];
		$params = [':id'=>$id];
		$allowed = ['kode_aset','nama','kategori','lokasi','kondisi','nilai_perolehan','tgl_perolehan'];
		foreach ($allowed as $col) {
			if (isset($data[$col])) { $fields[] = "{$col} = :{$col}"; $params[":{$col}"] = $data[$col]; }
		}
		if (count($fields) === 0) $this->jsonResponse(['error'=>'Tidak ada field untuk diupdate'], 400);
		try {
			$sql = "UPDATE aset SET " . implode(', ', $fields) . " WHERE id = :id";
			$stmt = $this->pdo->prepare($sql);
			$stmt->execute($params);
			$this->jsonResponse(['success'=>true]);
		} catch (Exception $e) {
			$this->jsonResponse(['error'=>'Gagal update aset: '.$e->getMessage()], 500);
		}
	}

	// Delete aset
	public function deleteAset(int $id) {
		if (!$this->auth->hasRole('admin_sekolah') && !$this->auth->hasRole('super_admin')) {
			$this->jsonResponse(['error'=>'Forbidden'], 403);
		}
		try {
			$stmt = $this->pdo->prepare('DELETE FROM aset WHERE id = :id');
			$stmt->execute([':id'=>$id]);
			$this->jsonResponse(['success'=>true]);
		} catch (Exception $e) {
			$this->jsonResponse(['error'=>'Gagal menghapus aset: '.$e->getMessage()], 500);
		}
	}

	private function jsonResponse($data, $status = 200) {
		http_response_code($status);
		header('Content-Type: application/json; charset=utf-8');
		echo json_encode($data, JSON_UNESCAPED_UNICODE);
		exit;
	}
}

