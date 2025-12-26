<?php
// app/RKASController.php
// Basic RKAS controller skeleton

require_once __DIR__ . '/Database.php';
require_once __DIR__ . '/Auth.php';

class RKASController {
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

	// Example: list RKAS entries
	public function listRKAS($sekolah_id = null) {
		$user = $this->auth->user();
		$sekolah_id = $sekolah_id ?? ($user['sekolah_id'] ?? null);
		if (!$sekolah_id) return $this->jsonResponse(['error' => 'sekolah_id wajib diisi'], 400);

		$stmt = $this->pdo->prepare("SELECT * FROM rkas WHERE sekolah_id = :sek ORDER BY id DESC");
		$stmt->execute([':sek' => $sekolah_id]);
		$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
		$this->jsonResponse(['data' => $rows]);
	}

	// Create rkas (admin_sekolah or super_admin)
	public function createRKAS(array $data) {
		if (!$this->auth->hasRole('admin_sekolah') && !$this->auth->hasRole('super_admin')) {
			$this->jsonResponse(['error'=>'Forbidden'], 403);
		}
		$sekolah_id = $data['sekolah_id'] ?? $this->auth->user()['sekolah_id'] ?? null;
		if (!$sekolah_id || empty($data['tahun'])) {
			$this->jsonResponse(['error'=>'sekolah_id dan tahun wajib diisi'], 400);
		}
		try {
			$stmt = $this->pdo->prepare("INSERT INTO rkas (sekolah_id, tahun, anggaran_total, realisasi_total, created_at) VALUES (:sekolah_id, :tahun, :anggaran_total, :realisasi_total, NOW())");
			$stmt->execute([
				':sekolah_id' => $sekolah_id,
				':tahun' => $data['tahun'],
				':anggaran_total' => $data['anggaran_total'] ?? 0,
				':realisasi_total' => $data['realisasi_total'] ?? 0
			]);
			$this->jsonResponse(['id'=>$this->pdo->lastInsertId()], 201);
		} catch (Exception $e) {
			$this->jsonResponse(['error'=>'Gagal menyimpan rkas: '.$e->getMessage()], 500);
		}
	}

	public function updateRKAS(int $id, array $data) {
		if (!$this->auth->hasRole('admin_sekolah') && !$this->auth->hasRole('super_admin')) {
			$this->jsonResponse(['error'=>'Forbidden'], 403);
		}
		$fields = [];
		$params = [':id'=>$id];
		$allowed = ['tahun','anggaran_total','realisasi_total'];
		foreach ($allowed as $col) {
			if (isset($data[$col])) { $fields[] = "{$col} = :{$col}"; $params[":{$col}"] = $data[$col]; }
		}
		if (count($fields) === 0) $this->jsonResponse(['error'=>'Tidak ada field untuk diupdate'], 400);
		try {
			$sql = "UPDATE rkas SET " . implode(', ', $fields) . " WHERE id = :id";
			$stmt = $this->pdo->prepare($sql);
			$stmt->execute($params);
			$this->jsonResponse(['success'=>true]);
		} catch (Exception $e) {
			$this->jsonResponse(['error'=>'Gagal update rkas: '.$e->getMessage()], 500);
		}
	}

	public function deleteRKAS(int $id) {
		if (!$this->auth->hasRole('admin_sekolah') && !$this->auth->hasRole('super_admin')) {
			$this->jsonResponse(['error'=>'Forbidden'], 403);
		}
		try {
			$stmt = $this->pdo->prepare('DELETE FROM rkas WHERE id = :id');
			$stmt->execute([':id'=>$id]);
			$this->jsonResponse(['success'=>true]);
		} catch (Exception $e) {
			$this->jsonResponse(['error'=>'Gagal menghapus rkas: '.$e->getMessage()], 500);
		}
	}

	private function jsonResponse($data, $status = 200) {
		http_response_code($status);
		header('Content-Type: application/json; charset=utf-8');
		echo json_encode($data, JSON_UNESCAPED_UNICODE);
		exit;
	}
}

