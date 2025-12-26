<?php
// app/controllers/TagihanController.php
// Controller untuk modul Tagihan & Pembayaran
// Pastikan file ini di-require dari public/api.php atau bootstrap yang sesuai.

require_once __DIR__ . '/Database.php';
require_once __DIR__ . '/Auth.php';
require_once __DIR__ . '/models/TagihanModel.php';
require_once __DIR__ . '/models/PembayaranModel.php';

class TagihanController {
    private $pdo;
    private $cfg;
    private $tagihanModel;
    private $pembModel;
    private $auth;
    private $currentUser;

    public function __construct(array $config) {
        $this->cfg = $config;
        $this->pdo = Database::getInstance($config);
        $this->tagihanModel = new TagihanModel($this->pdo);
        $this->pembModel = new PembayaranModel($this->pdo);

        // init auth (session or bearer token)
        $this->auth = new Auth($this->pdo);
        if (!$this->auth->check()) {
            $this->jsonResponse(['error' => 'Unauthorized'], 401);
        }
        $this->currentUser = $this->auth->user();
    }

    /**
     * GET: ambil detail tagihan
     */
    public function getTagihan($id) {
        $tag = $this->tagihanModel->getById((int)$id);
        if (!$tag) return $this->jsonResponse(['error'=>'Tagihan tidak ditemukan'], 404);
        $paid = $this->tagihanModel->getTotalPaid((int)$id);
        $tag['total_paid'] = $paid;
        $this->jsonResponse($tag);
    }

    /**
     * Create single tagihan (role: admin_sekolah / super_admin)
     */
    public function createTagihan($payload) {
        if (!$this->auth->hasRole('admin_sekolah') && !$this->auth->hasRole('super_admin')) {
            return $this->jsonResponse(['error' => 'Forbidden: role tidak diizinkan'], 403);
        }
        if (empty($payload['sekolah_id']) && !empty($this->currentUser['sekolah_id'])) {
            $payload['sekolah_id'] = $this->currentUser['sekolah_id'];
        }
        if (empty($payload['sekolah_id']) || empty($payload['jumlah']) || empty($payload['kode_tagihan'])) {
            return $this->jsonResponse(['error'=>'sekolah_id, kode_tagihan, jumlah wajib diisi'], 400);
        }
        $data = [
            'sekolah_id' => (int)$payload['sekolah_id'],
            'siswa_id' => isset($payload['siswa_id']) ? (int)$payload['siswa_id'] : null,
            'kode_tagihan' => $payload['kode_tagihan'],
            'deskripsi' => $payload['deskripsi'] ?? 'Tagihan bulanan',
            'jumlah' => (float)$payload['jumlah'],
            'tgl_dibuat' => $payload['tgl_dibuat'] ?? date('Y-m-d'),
            'due_date' => $payload['due_date'] ?? date('Y-m-d', strtotime('+14 days')),
            'status' => 'unpaid'
        ];
        $id = $this->tagihanModel->create($data);
        return $this->jsonResponse(['id'=>$id], 201);
    }

    /**
     * Generate massal tagihan (role: admin_sekolah / super_admin)
     */
    public function generateMass(array $payload) {
        if (!$this->auth->hasRole('admin_sekolah') && !$this->auth->hasRole('super_admin')) {
            return $this->jsonResponse(['error' => 'Forbidden: role tidak diizinkan'], 403);
        }
        $sekolah_id = (int)($payload['sekolah_id'] ?? 0);
        // jika user punya sekolah, override
        if (empty($sekolah_id) && !empty($this->currentUser['sekolah_id'])) {
            $sekolah_id = (int)$this->currentUser['sekolah_id'];
        }
        $siswa_ids = $payload['siswa_ids'] ?? [];
        $jumlah = (float)($payload['jumlah'] ?? 0);
        $kode_prefix = $payload['kode_prefix'] ?? 'TAG';
        $periode = $payload['periode'] ?? date('Y-m');
        if (empty($sekolah_id) || empty($siswa_ids) || $jumlah <= 0) {
            return $this->jsonResponse(['error'=>'sekolah_id, siswa_ids, jumlah wajib diisi dengan benar'], 400);
        }
        $created = [];
        foreach ($siswa_ids as $siswa_id) {
            $kode = sprintf("%s-%s-%s", $kode_prefix, $periode, $siswa_id);
                $data = [
                'sekolah_id' => $sekolah_id,
                'siswa_id' => (int)$siswa_id,
                'kode_tagihan' => $kode,
                'deskripsi' => "SPP $periode",
                'jumlah' => $jumlah,
                'tgl_dibuat' => date('Y-m-01', strtotime($periode.'-01')),
                'due_date' => date('Y-m-d', strtotime($periode.'-01 +14 days'))
            ];
            $id = $this->tagihanModel->create($data);
            $created[] = $id;
        }
        return $this->jsonResponse(['created'=>$created], 201);
    }

    /**
     * CREATE PEMBAYARAN
     * - boleh dibuat oleh: admin_sekolah, bendahara, super_admin, atau role lain yang Anda tentukan.
     * - jika user memiliki sekolah_id, harus sesuai sekolah payload (kecuali super_admin)
     * - mendukung upload file bukti (multipart)
     * - mendukung auto_confirm (auto konfirmasi pembayaran)
     */
    public function createPembayaran($payload, $files) {
        // Allowed roles to create payment (you can adjust)
        $allowedRoles = ['admin_sekolah', 'bendahara', 'super_admin', 'tata_usaha'];
        $userRole = $this->currentUser['role'] ?? null;

        // Anyone authenticated may submit a payment in this implementation if they belong to the school.
        // But we limit some actions (like auto_confirm) to allowed roles.
        // Ensure sekolah_id present (use user's sekolah if not provided)
        if (empty($payload['sekolah_id']) && !empty($this->currentUser['sekolah_id'])) {
            $payload['sekolah_id'] = $this->currentUser['sekolah_id'];
        }
        if (empty($payload['sekolah_id']) || empty($payload['jumlah'])) {
            return $this->jsonResponse(['error'=>'sekolah_id dan jumlah wajib diisi'], 400);
        }

        // School ownership check: if user tied to a school, they cannot create for other school (unless super_admin)
        $sekolahIdFromUser = $this->currentUser['sekolah_id'] ?? null;
        if ($sekolahIdFromUser && ((int)$payload['sekolah_id'] !== (int)$sekolahIdFromUser) && !$this->auth->hasRole('super_admin')) {
            return $this->jsonResponse(['error'=>'Forbidden: cannot create payment for other school'], 403);
        }

        // Handle bukti file if present
        $buktiFilename = null;
        if (!empty($files['bukti']) && is_array($files['bukti'])) {
            $buktiFilename = $this->handleUpload($files['bukti']);
            if ($buktiFilename === false) {
                return $this->jsonResponse(['error'=>'Upload bukti gagal atau tidak memenuhi syarat (tipe/size)'], 400);
            }
        }

        // Prepare payment payload
        $paymentData = [
            'tagihan_id' => !empty($payload['tagihan_id']) ? (int)$payload['tagihan_id'] : null,
            'sekolah_id' => (int)$payload['sekolah_id'],
            'jumlah'     => (float)$payload['jumlah'],
            'metode'     => $payload['metode'] ?? 'Transfer Bank',
            'bukti'      => $buktiFilename,
            'status'     => 'pending'
        ];

        # auto_confirm flag: only allowed for certain roles (e.g., bendahara, super_admin)
        $autoConfirmRequested = !empty($payload['auto_confirm']) && in_array($payload['auto_confirm'], ['1','true',1,true], true);
        $autoConfirmAllowedRoles = ['bendahara', 'super_admin'];
        $autoConfirm = $autoConfirmRequested && in_array($userRole, $autoConfirmAllowedRoles, true);

        if ($autoConfirm) {
            $paymentData['status'] = 'confirmed';
        }

        # Transaction: insert pembayaran, if confirmed then update tagihan status
        try {
            $this->pdo->beginTransaction();

            $pembId = $this->pembModel->create($paymentData);

            # If payment was stored as pending but someone with permission wants to confirm now
            if ($autoConfirm) {
                $this->pembModel->confirm((int)$pembId);
            }

            # If linked to tagihan and payment is confirmed, recompute tagihan status
            if (!empty($paymentData['tagihan_id']) && $autoConfirm) {
                $tagId = (int)$paymentData['tagihan_id'];
                $tag = $this->tagihanModel->getById($tagId);
                if ($tag) {
                    $totalPaid = $this->tagihanModel->getTotalPaid($tagId);
                    $jumlahTagihan = (float)$tag['jumlah'];
                    if ($totalPaid >= $jumlahTagihan) {
                        $this->tagihanModel->updateStatus($tagId, 'paid');
                    } elseif ($totalPaid > 0) {
                        $this->tagihanModel->updateStatus($tagId, 'partial');
                    } else {
                        $this->tagihanModel->updateStatus($tagId, 'unpaid');
                    }
                }
            }

            $this->pdo->commit();
            return $this->jsonResponse(['id'=>$pembId, 'status'=>$paymentData['status']], 201);

        } catch (Exception $e) {
            $this->pdo->rollBack();
            return $this->jsonResponse(['error'=>'Gagal menyimpan pembayaran: '.$e->getMessage()], 500);
        }
    }

    /**
     * handleUpload
     * - menerima array $_FILES['bukti']
     * - validasi: ukuran <=5MB; ekstensi jpg,jpeg,png,pdf
     * - mengembalikan nama file yang disimpan atau false jika gagal
     */
    private function handleUpload(array $file) {
        $uploadsDir = $this->cfg['uploads_dir'] ?? (__DIR__ . '/../../public/uploads/bukti_pembayaran');
        if (!is_dir($uploadsDir)) {
            if (!@mkdir($uploadsDir, 0755, true)) {
                return false;
            }
        }

        if (!isset($file['error']) || is_array($file['error'])) {
            return false;
        }
        if ($file['error'] !== UPLOAD_ERR_OK) {
            return false;
        }
        # size limit 5MB
        if ($file['size'] > 5 * 1024 * 1024) {
            return false;
        }
        $finfo = pathinfo($file['name']);
        $ext = strtolower($finfo['extension'] ?? '');
        $allowed = ['jpg','jpeg','png','pdf'];
        if (!in_array($ext, $allowed, true)) {
            return false;
        }

        $safeName = bin2hex(random_bytes(10)) . '_' . preg_replace('/[^a-zA-Z0-9_\-\.]/', '_', $finfo['filename']) . '.' . $ext;
        $dest = rtrim($uploadsDir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $safeName;

        if (!move_uploaded_file($file['tmp_name'], $dest)) {
            return false;
        }

        return $safeName;
    }

    /**
     * Generic JSON response helper
     */
    private function jsonResponse($data, $status = 200) {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
        exit;
    }
}
