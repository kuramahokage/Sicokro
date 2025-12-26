<?php
/**
 * SICOKRO API Router
 * Main API endpoint for all requests
 * Handles routing to appropriate controllers
 */

// Error reporting (disable in production)
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../logs/error.log');

// CORS headers
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Load configuration - support both app/config.php and root Config.php
$config = null;
if (file_exists(__DIR__ . '/../app/config.php')) {
    $config = require __DIR__ . '/../app/config.php';
} elseif (file_exists(__DIR__ . '/../Config.php')) {
    $config = require __DIR__ . '/../Config.php';
} else {
    // Minimal fallback: return error (jsonResponse isn't defined yet), so send basic response
    header('Content-Type: application/json; charset=utf-8');
    http_response_code(500);
    echo json_encode(['error' => 'Missing configuration file. Please copy Config.php to the project root or app/config.php']);
    exit;
}

// Start session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Get action from request
$action = $_GET['action'] ?? $_POST['action'] ?? null;

// JSON response helper
function jsonResponse($data, $status = 200) {
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    exit;
}

// No action specified
if (!$action) {
    jsonResponse([
        'error' => 'No action specified',
        'usage' => 'Add ?action=ACTION_NAME to the URL'
    ], 400);
}

// ============================================
// AUTHENTICATION ROUTES
// ============================================
if ($action === 'login') {
    require_once __DIR__ . '/../Database.php';
    
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';
    
    if (empty($email) || empty($password)) {
        jsonResponse(['error' => 'Email dan password wajib diisi'], 400);
    }
    
    try {
        $pdo = Database::getInstance($config);
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = :email LIMIT 1");
        $stmt->execute([':email' => $email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        // DEBUG: log whether user found and a short part of hash (development only)
        if ($user) {
            error_log('DEBUG login: user found id=' . ($user['id'] ?? 'n') . ' email=' . ($user['email'] ?? '')); 
            error_log('DEBUG login: hash_prefix=' . substr($user['password_hash'] ?? '', 0, 12));
        } else {
            error_log('DEBUG login: user not found for email=' . $email);
        }

        $pwOk = false;
        if ($user && isset($user['password_hash'])) {
            $pwOk = password_verify($password, $user['password_hash']);
            error_log('DEBUG login: password_verify => ' . ($pwOk ? 'true' : 'false'));
        }

        if (!$user || !$pwOk) {
            jsonResponse(['error' => 'Email atau password salah'], 401);
        }
        
        // Set session
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_email'] = $user['email'];
        $_SESSION['user_role'] = $user['role'];
        
        // Get sekolah name if exists
        $sekolahNama = null;
        if ($user['sekolah_id']) {
            $stmt = $pdo->prepare("SELECT nama FROM sekolah WHERE id = :id");
            $stmt->execute([':id' => $user['sekolah_id']]);
            $sekolah = $stmt->fetch(PDO::FETCH_ASSOC);
            $sekolahNama = $sekolah['nama'] ?? null;
        }
        
        // Remove sensitive data
        unset($user['password_hash']);
        $user['sekolah_nama'] = $sekolahNama;
        
        jsonResponse([
            'success' => true,
            'user' => $user,
            'message' => 'Login berhasil'
        ]);
        
    } catch (Exception $e) {
        error_log("Login error: " . $e->getMessage());
        jsonResponse(['error' => 'Terjadi kesalahan server'], 500);
    }
}

if ($action === 'logout') {
    session_destroy();
    jsonResponse(['success' => true, 'message' => 'Logout berhasil']);
}

if ($action === 'check_auth') {
    require_once __DIR__ . '/../app/Auth.php';
    require_once __DIR__ . '/../app/Database.php';
    
    $pdo = Database::getInstance($config);
    $auth = new Auth($pdo);
    
    if ($auth->check()) {
        $user = $auth->user();
        unset($user['password_hash']);
        jsonResponse(['authenticated' => true, 'user' => $user]);
    } else {
        jsonResponse(['authenticated' => false], 401);
    }
}

// ============================================
// TAGIHAN ROUTES
// ============================================
require_once __DIR__ . '/../app/TagihanController.php';

if ($action === 'get_tagihan') {
    $id = $_GET['id'] ?? null;
    if (!$id) {
        jsonResponse(['error' => 'ID tagihan wajib diisi'], 400);
    }
    $controller = new TagihanController($config);
    $controller->getTagihan($id);
}

if ($action === 'list_tagihan') {
    require_once __DIR__ . '/../app/Database.php';
    require_once __DIR__ . '/../app/Auth.php';
    require_once __DIR__ . '/../app/models/TagihanModel.php';
    
    $pdo = Database::getInstance($config);
    $auth = new Auth($pdo);
    
    if (!$auth->check()) {
        jsonResponse(['error' => 'Unauthorized'], 401);
    }
    
    $user = $auth->user();
    $sekolahId = $_GET['sekolah_id'] ?? $user['sekolah_id'] ?? null;
    $periode = $_GET['periode'] ?? null;
    
    if (!$sekolahId) {
        jsonResponse(['error' => 'sekolah_id wajib diisi'], 400);
    }
    
    // Check permission
    if (!$auth->hasRole('super_admin') && (int)$sekolahId !== (int)$user['sekolah_id']) {
        jsonResponse(['error' => 'Forbidden: tidak dapat akses data sekolah lain'], 403);
    }
    
    $model = new TagihanModel($pdo);
    $tagihan = $model->listBySekolahAndPeriode((int)$sekolahId, $periode);
    
    jsonResponse(['data' => $tagihan]);
}

if ($action === 'create_tagihan') {
    $controller = new TagihanController($config);
    $controller->createTagihan($_POST);
}

if ($action === 'generate_mass') {
    $controller = new TagihanController($config);
    
    // Parse JSON if content-type is application/json
    $payload = $_POST;
    if (empty($payload) && isset($_SERVER['CONTENT_TYPE']) && 
        strpos($_SERVER['CONTENT_TYPE'], 'application/json') !== false) {
        $payload = json_decode(file_get_contents('php://input'), true);
    }
    
    $controller->generateMass($payload);
}

if ($action === 'update_tagihan_status') {
    require_once __DIR__ . '/../app/Database.php';
    require_once __DIR__ . '/../app/Auth.php';
    require_once __DIR__ . '/../app/models/TagihanModel.php';
    
    $pdo = Database::getInstance($config);
    $auth = new Auth($pdo);
    
    if (!$auth->check()) {
        jsonResponse(['error' => 'Unauthorized'], 401);
    }
    
    if (!$auth->hasRole('admin_sekolah') && !$auth->hasRole('super_admin')) {
        jsonResponse(['error' => 'Forbidden'], 403);
    }
    
    $id = $_POST['id'] ?? null;
    $status = $_POST['status'] ?? null;
    
    if (!$id || !$status) {
        jsonResponse(['error' => 'ID dan status wajib diisi'], 400);
    }
    
    if (!in_array($status, ['unpaid', 'partial', 'paid'])) {
        jsonResponse(['error' => 'Status tidak valid'], 400);
    }
    
    $model = new TagihanModel($pdo);
    $result = $model->updateStatus((int)$id, $status);
    
    jsonResponse(['success' => $result, 'message' => 'Status tagihan berhasil diupdate']);
}

// ============================================
// PEMBAYARAN ROUTES
// ============================================
if ($action === 'create_pembayaran') {
    $controller = new TagihanController($config);
    $controller->createPembayaran($_POST, $_FILES);
}

if ($action === 'list_pembayaran') {
    require_once __DIR__ . '/../app/Database.php';
    require_once __DIR__ . '/../app/Auth.php';
    require_once __DIR__ . '/../app/models/PembayaranModel.php';
    
    $pdo = Database::getInstance($config);
    $auth = new Auth($pdo);
    
    if (!$auth->check()) {
        jsonResponse(['error' => 'Unauthorized'], 401);
    }
    
    $user = $auth->user();
    $sekolahId = $_GET['sekolah_id'] ?? $user['sekolah_id'] ?? null;
    $limit = $_GET['limit'] ?? 100;
    
    if (!$sekolahId) {
        jsonResponse(['error' => 'sekolah_id wajib diisi'], 400);
    }
    
    // Check permission
    if (!$auth->hasRole('super_admin') && (int)$sekolahId !== (int)$user['sekolah_id']) {
        jsonResponse(['error' => 'Forbidden'], 403);
    }
    
    $model = new PembayaranModel($pdo);
    $pembayaran = $model->listBySekolah((int)$sekolahId, (int)$limit);
    
    jsonResponse(['data' => $pembayaran]);
}

if ($action === 'confirm_pembayaran') {
    require_once __DIR__ . '/../app/Database.php';
    require_once __DIR__ . '/../app/Auth.php';
    require_once __DIR__ . '/../app/models/PembayaranModel.php';
    require_once __DIR__ . '/../app/models/TagihanModel.php';
    
    $pdo = Database::getInstance($config);
    $auth = new Auth($pdo);
    
    if (!$auth->check()) {
        jsonResponse(['error' => 'Unauthorized'], 401);
    }
    
    // Only bendahara and super_admin can confirm
    if (!$auth->hasRole('bendahara') && !$auth->hasRole('super_admin')) {
        jsonResponse(['error' => 'Forbidden: hanya bendahara yang dapat konfirmasi'], 403);
    }
    
    $id = $_POST['id'] ?? null;
    if (!$id) {
        jsonResponse(['error' => 'ID pembayaran wajib diisi'], 400);
    }
    
    try {
        $pdo->beginTransaction();
        
        $pembModel = new PembayaranModel($pdo);
        $pembModel->confirm((int)$id);
        
        // Get pembayaran details
        $stmt = $pdo->prepare("SELECT tagihan_id, jumlah FROM pembayaran WHERE id = :id");
        $stmt->execute([':id' => $id]);
        $pembayaran = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Update tagihan status if linked
        if ($pembayaran && $pembayaran['tagihan_id']) {
            $tagihanModel = new TagihanModel($pdo);
            $totalPaid = $tagihanModel->getTotalPaid((int)$pembayaran['tagihan_id']);
            
            $tagihan = $tagihanModel->getById((int)$pembayaran['tagihan_id']);
            $jumlahTagihan = (float)$tagihan['jumlah'];
            
            if ($totalPaid >= $jumlahTagihan) {
                $tagihanModel->updateStatus((int)$pembayaran['tagihan_id'], 'paid');
            } elseif ($totalPaid > 0) {
                $tagihanModel->updateStatus((int)$pembayaran['tagihan_id'], 'partial');
            }
        }
        
        $pdo->commit();
        jsonResponse(['success' => true, 'message' => 'Pembayaran berhasil dikonfirmasi']);
        
    } catch (Exception $e) {
        $pdo->rollBack();
        error_log("Confirm payment error: " . $e->getMessage());
        jsonResponse(['error' => 'Gagal konfirmasi pembayaran'], 500);
    }
}

// ============================================
// DASHBOARD & STATISTICS ROUTES
// ============================================
if ($action === 'dashboard_stats') {
    require_once __DIR__ . '/../app/Database.php';
    require_once __DIR__ . '/../app/Auth.php';
    
    $pdo = Database::getInstance($config);
    $auth = new Auth($pdo);
    
    if (!$auth->check()) {
        jsonResponse(['error' => 'Unauthorized'], 401);
    }
    
    $user = $auth->user();
    $sekolahId = $_GET['sekolah_id'] ?? $user['sekolah_id'] ?? null;
    
    if (!$sekolahId) {
        jsonResponse(['error' => 'sekolah_id wajib diisi'], 400);
    }
    
    // Check permission
    if (!$auth->hasRole('super_admin') && (int)$sekolahId !== (int)$user['sekolah_id']) {
        jsonResponse(['error' => 'Forbidden'], 403);
    }
    
    try {
        $currentMonth = date('Y-m');
        
        // Total tagihan bulan ini
        $stmt = $pdo->prepare("
            SELECT COALESCE(SUM(jumlah), 0) as total 
            FROM tagihan 
            WHERE sekolah_id = :sek 
            AND DATE_FORMAT(tgl_dibuat, '%Y-%m') = :month
        ");
        $stmt->execute([':sek' => $sekolahId, ':month' => $currentMonth]);
        $totalTagihan = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
        
        // Total terbayar bulan ini
        $stmt = $pdo->prepare("
            SELECT COALESCE(SUM(p.jumlah), 0) as total 
            FROM pembayaran p
            JOIN tagihan t ON p.tagihan_id = t.id
            WHERE t.sekolah_id = :sek 
            AND p.status = 'confirmed'
            AND DATE_FORMAT(t.tgl_dibuat, '%Y-%m') = :month
        ");
        $stmt->execute([':sek' => $sekolahId, ':month' => $currentMonth]);
        $totalPaid = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
        
        // Belum lunas
        $unpaid = $totalTagihan - $totalPaid;
        
        // Jatuh tempo
        $stmt = $pdo->prepare("
            SELECT COUNT(*) as count 
            FROM tagihan 
            WHERE sekolah_id = :sek 
            AND status != 'paid'
            AND due_date < CURDATE()
        ");
        $stmt->execute([':sek' => $sekolahId]);
        $overdue = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
        
        // Pending payments
        $stmt = $pdo->prepare("
            SELECT COUNT(*) as count 
            FROM pembayaran 
            WHERE sekolah_id = :sek 
            AND status = 'pending'
        ");
        $stmt->execute([':sek' => $sekolahId]);
        $pendingPayments = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
        
        jsonResponse([
            'totalTagihan' => (float)$totalTagihan,
            'totalPaid' => (float)$totalPaid,
            'unpaid' => (float)$unpaid,
            'overdue' => (int)$overdue,
            'pendingPayments' => (int)$pendingPayments
        ]);
        
    } catch (Exception $e) {
        error_log("Dashboard stats error: " . $e->getMessage());
        jsonResponse(['error' => 'Gagal mengambil statistik'], 500);
    }
}

// ============================================
// SISWA ROUTES (Basic)
// ============================================
if ($action === 'list_siswa') {
    require_once __DIR__ . '/../app/Database.php';
    require_once __DIR__ . '/../app/Auth.php';
    
    $pdo = Database::getInstance($config);
    $auth = new Auth($pdo);
    
    if (!$auth->check()) {
        jsonResponse(['error' => 'Unauthorized'], 401);
    }
    
    $user = $auth->user();
    $sekolahId = $_GET['sekolah_id'] ?? $user['sekolah_id'] ?? null;
    
    if (!$sekolahId) {
        jsonResponse(['error' => 'sekolah_id wajib diisi'], 400);
    }
    
    // Check permission
    if (!$auth->hasRole('super_admin') && (int)$sekolahId !== (int)$user['sekolah_id']) {
        jsonResponse(['error' => 'Forbidden'], 403);
    }
    
    $stmt = $pdo->prepare("SELECT * FROM siswa WHERE sekolah_id = :sek ORDER BY nama");
    $stmt->execute([':sek' => $sekolahId]);
    $siswa = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    jsonResponse(['data' => $siswa]);
}

// Extended siswa with financial aggregates per student
if ($action === 'list_siswa_extended') {
    require_once __DIR__ . '/../app/Database.php';
    require_once __DIR__ . '/../app/Auth.php';

    $pdo = Database::getInstance($config);
    $auth = new Auth($pdo);

    if (!$auth->check()) {
        jsonResponse(['error' => 'Unauthorized'], 401);
    }

    $user = $auth->user();
    $sekolahId = $_GET['sekolah_id'] ?? $user['sekolah_id'] ?? null;

    if (!$sekolahId) {
        jsonResponse(['error' => 'sekolah_id wajib diisi'], 400);
    }

    // permission
    if (!$auth->hasRole('super_admin') && (int)$sekolahId !== (int)$user['sekolah_id']) {
        jsonResponse(['error' => 'Forbidden'], 403);
    }

    try {
         $sql = "
             SELECT s.id, s.nama, s.kelas,
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

        $stmt = $pdo->prepare($sql);
        $stmt->execute([':sek' => $sekolahId]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        jsonResponse(['data' => $rows]);
    } catch (Exception $e) {
        error_log('list_siswa_extended error: ' . $e->getMessage());
        jsonResponse(['error' => 'Gagal mengambil data siswa extended'], 500);
    }
}

    // ============================================
    // SEKOLAH ROUTES
    // ============================================
    if ($action === 'list_sekolah') {
        require_once __DIR__ . '/../app/Database.php';
        $pdo = Database::getInstance($config);
        try {
            // adapt to current schema: sekolah table has alamat, jenjang, kepala_sekolah, status
            $stmt = $pdo->query("SELECT id, nama, alamat, jenjang, kepala_sekolah, status FROM sekolah ORDER BY nama");
            $sekolah = $stmt->fetchAll(PDO::FETCH_ASSOC);
            jsonResponse(['data' => $sekolah]);
        } catch (Exception $e) {
            error_log('List sekolah error: '.$e->getMessage());
            jsonResponse(['error' => 'Gagal mengambil daftar sekolah'], 500);
        }
    }

    // ============================================
    // ADDITIONAL ROUTES: ASET, RKAS, USER
    // ============================================
    if ($action === 'list_aset') {
        require_once __DIR__ . '/../app/AsetController.php';
        $sekolah_id = $_GET['sekolah_id'] ?? null;
        $controller = new AsetController($config);
        $controller->listAset($sekolah_id);
    }

    if ($action === 'create_aset') {
        require_once __DIR__ . '/../app/AsetController.php';
        $controller = new AsetController($config);
        $controller->createAset($_POST);
    }

    if ($action === 'update_aset') {
        require_once __DIR__ . '/../app/AsetController.php';
        $controller = new AsetController($config);
        $id = $_POST['id'] ?? null;
        if (!$id) jsonResponse(['error'=>'id wajib diisi'], 400);
        $controller->updateAset((int)$id, $_POST);
    }

    if ($action === 'delete_aset') {
        require_once __DIR__ . '/../app/AsetController.php';
        $controller = new AsetController($config);
        $id = $_POST['id'] ?? null;
        if (!$id) jsonResponse(['error'=>'id wajib diisi'], 400);
        $controller->deleteAset((int)$id);
    }

    if ($action === 'list_rkas') {
        require_once __DIR__ . '/../app/RKASController.php';
        $sekolah_id = $_GET['sekolah_id'] ?? null;
        $controller = new RKASController($config);
        $controller->listRKAS($sekolah_id);
    }

    if ($action === 'create_rkas') {
        require_once __DIR__ . '/../app/RKASController.php';
        $controller = new RKASController($config);
        $payload = $_POST;
        $controller->createRKAS($payload);
    }

    if ($action === 'update_rkas') {
        require_once __DIR__ . '/../app/RKASController.php';
        $controller = new RKASController($config);
        $id = $_POST['id'] ?? null;
        if (!$id) jsonResponse(['error'=>'id wajib diisi'], 400);
        $controller->updateRKAS((int)$id, $_POST);
    }

    if ($action === 'delete_rkas') {
        require_once __DIR__ . '/../app/RKASController.php';
        $controller = new RKASController($config);
        $id = $_POST['id'] ?? null;
        if (!$id) jsonResponse(['error'=>'id wajib diisi'], 400);
        $controller->deleteRKAS((int)$id);
    }

    if ($action === 'list_rkt') {
        require_once __DIR__ . '/../app/Database.php';
        require_once __DIR__ . '/../app/Auth.php';

        $pdo = Database::getInstance($config);
        $auth = new Auth($pdo);
        if (!$auth->check()) jsonResponse(['error'=>'Unauthorized'], 401);

        $sekolahId = $_GET['sekolah_id'] ?? $auth->user()['sekolah_id'] ?? null;
        if (!$sekolahId) jsonResponse(['error'=>'sekolah_id wajib diisi'], 400);

        try {
            $stmt = $pdo->prepare("SELECT * FROM rkt WHERE sekolah_id = :sek ORDER BY tahun DESC, id DESC");
            $stmt->execute([':sek' => $sekolahId]);
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
            jsonResponse(['data' => $rows]);
        } catch (Exception $e) {
            error_log('list_rkt error: '.$e->getMessage());
            jsonResponse(['data' => []]);
        }
    }

    if ($action === 'list_rkjm') {
        require_once __DIR__ . '/../app/Database.php';
        require_once __DIR__ . '/../app/Auth.php';

        $pdo = Database::getInstance($config);
        $auth = new Auth($pdo);
        if (!$auth->check()) jsonResponse(['error'=>'Unauthorized'], 401);

        $sekolahId = $_GET['sekolah_id'] ?? $auth->user()['sekolah_id'] ?? null;
        if (!$sekolahId) jsonResponse(['error'=>'sekolah_id wajib diisi'], 400);

        try {
            $stmt = $pdo->prepare("SELECT * FROM rkjm WHERE sekolah_id = :sek ORDER BY tahun DESC, id DESC");
            $stmt->execute([':sek' => $sekolahId]);
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
            jsonResponse(['data' => $rows]);
        } catch (Exception $e) {
            error_log('list_rkjm error: '.$e->getMessage());
            jsonResponse(['data' => []]);
        }
    }

    if ($action === 'me') {
        require_once __DIR__ . '/../app/UserController.php';
        $controller = new UserController($config);
        $controller->me();
    }

    // ============================================
    // PLANS API: get_plan, save_plan
    // supports type=rkjm|rkt|rkas
    // ============================================
    if ($action === 'get_plan') {
        require_once __DIR__ . '/../app/Database.php';
        require_once __DIR__ . '/../app/Auth.php';

        $pdo = Database::getInstance($config);
        $auth = new Auth($pdo);
        if (!$auth->check()) jsonResponse(['error' => 'Unauthorized'], 401);

        $type = $_GET['type'] ?? null;
        $id = $_GET['id'] ?? null;
        if (!$type || !$id) jsonResponse(['error' => 'type dan id wajib diisi'], 400);

        $allowed = ['rkjm','rkt','rkas'];
        if (!in_array($type, $allowed)) jsonResponse(['error' => 'type tidak didukung'], 400);

        try {
            $stmt = $pdo->prepare("SELECT * FROM {$type} WHERE id = :id LIMIT 1");
            $stmt->execute([':id' => $id]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$row) jsonResponse(['error' => 'Item tidak ditemukan'], 404);

            // permission: allow super_admin and admin_yayasan; admin_sekolah only if sekolah match
            $user = $auth->user();
            if (!$auth->hasRole('super_admin') && !$auth->hasRole('admin_yayasan')) {
                if ($auth->hasRole('admin_sekolah')) {
                    if (isset($row['sekolah_id']) && $row['sekolah_id'] != $user['sekolah_id']) {
                        jsonResponse(['error' => 'Forbidden'], 403);
                    }
                } else {
                    jsonResponse(['error' => 'Forbidden'], 403);
                }
            }

            jsonResponse(['data' => $row]);
        } catch (Exception $e) {
            error_log('get_plan error: ' . $e->getMessage());
            jsonResponse(['error' => 'Gagal mengambil data plan'], 500);
        }
    }

    if ($action === 'save_plan') {
        require_once __DIR__ . '/../app/Database.php';
        require_once __DIR__ . '/../app/Auth.php';

        $pdo = Database::getInstance($config);
        $auth = new Auth($pdo);
        if (!$auth->check()) jsonResponse(['error' => 'Unauthorized'], 401);

        // parse JSON body
        $payload = $_POST;
        if (empty($payload) && isset($_SERVER['CONTENT_TYPE']) && strpos($_SERVER['CONTENT_TYPE'], 'application/json') !== false) {
            $payload = json_decode(file_get_contents('php://input'), true);
        }

        $type = $payload['type'] ?? null;
        $id = $payload['id'] ?? null;
        $data = $payload['data'] ?? [];

        if (!$type) jsonResponse(['error' => 'type wajib diisi'], 400);
        $allowed = ['rkjm','rkt','rkas'];
        if (!in_array($type, $allowed)) jsonResponse(['error' => 'type tidak didukung'], 400);

        // permission: only super_admin or admin_yayasan or admin_sekolah (with sekolah match)
        $user = $auth->user();
        if (!$auth->hasRole('super_admin') && !$auth->hasRole('admin_yayasan') && !$auth->hasRole('admin_sekolah')) {
            jsonResponse(['error' => 'Forbidden'], 403);
        }

        try {
            if ($id) {
                // check existing
                $stmt = $pdo->prepare("SELECT * FROM {$type} WHERE id = :id LIMIT 1");
                $stmt->execute([':id' => $id]);
                $row = $stmt->fetch(PDO::FETCH_ASSOC);
                if (!$row) {
                    jsonResponse(['error' => 'Item tidak ditemukan'], 404);
                }

                // sekolah match for admin_sekolah
                if ($auth->hasRole('admin_sekolah') && isset($row['sekolah_id']) && $row['sekolah_id'] != $user['sekolah_id']) {
                    jsonResponse(['error' => 'Forbidden'], 403);
                }

                // build update fields (only allow these columns)
                $fields = [];
                $params = [':id' => $id];
                if (isset($data['tahun'])) { $fields[] = 'tahun = :tahun'; $params[':tahun'] = $data['tahun']; }
                if (isset($data['nama'])) { $fields[] = 'nama = :nama'; $params[':nama'] = $data['nama']; }
                if (isset($data['deskripsi'])) { $fields[] = 'deskripsi = :deskripsi'; $params[':deskripsi'] = $data['deskripsi']; }
                if (isset($data['anggaran_total'])) { $fields[] = 'anggaran_total = :anggaran_total'; $params[':anggaran_total'] = $data['anggaran_total']; }

                if (count($fields) === 0) {
                    jsonResponse(['error' => 'Tidak ada field untuk diupdate'], 400);
                }

                $sql = "UPDATE {$type} SET " . implode(', ', $fields) . " WHERE id = :id";
                $stmt = $pdo->prepare($sql);
                $stmt->execute($params);

                // return updated row
                $stmt = $pdo->prepare("SELECT * FROM {$type} WHERE id = :id LIMIT 1");
                $stmt->execute([':id' => $id]);
                $updated = $stmt->fetch(PDO::FETCH_ASSOC);
                jsonResponse(['success' => true, 'data' => $updated]);
            } else {
                // create new
                // require sekolah_id for non-super_admin
                $sekolah_id = $data['sekolah_id'] ?? ($user['sekolah_id'] ?? null);
                if (!$sekolah_id) jsonResponse(['error' => 'sekolah_id wajib diisi untuk membuat plan'], 400);

                // admin_sekolah can only create for their sekolah
                if ($auth->hasRole('admin_sekolah') && $sekolah_id != $user['sekolah_id']) jsonResponse(['error' => 'Forbidden'], 403);

                $cols = ['sekolah_id']; $place = [':sekolah_id' => $sekolah_id];
                if (isset($data['tahun'])) { $cols[] = 'tahun'; $place[':tahun'] = $data['tahun']; }
                if (isset($data['nama'])) { $cols[] = 'nama'; $place[':nama'] = $data['nama']; }
                if (isset($data['deskripsi'])) { $cols[] = 'deskripsi'; $place[':deskripsi'] = $data['deskripsi']; }
                if (isset($data['anggaran_total'])) { $cols[] = 'anggaran_total'; $place[':anggaran_total'] = $data['anggaran_total']; }

                $colStr = implode(', ', $cols);
                $valStr = implode(', ', array_keys($place));
                $sql = "INSERT INTO {$type} ({$colStr}) VALUES ({$valStr})";
                $stmt = $pdo->prepare($sql);
                $stmt->execute($place);
                $newId = $pdo->lastInsertId();
                $stmt = $pdo->prepare("SELECT * FROM {$type} WHERE id = :id LIMIT 1");
                $stmt->execute([':id' => $newId]);
                $created = $stmt->fetch(PDO::FETCH_ASSOC);
                jsonResponse(['success' => true, 'data' => $created]);
            }
        } catch (Exception $e) {
            error_log('save_plan error: ' . $e->getMessage());
            jsonResponse(['error' => 'Gagal menyimpan plan'], 500);
        }
    }

// ============================================
// ERROR HANDLER - Unknown action
// ============================================
jsonResponse([
    'error' => 'Unknown action',
    'action' => $action,
    'available_actions' => [
        'login', 'logout', 'check_auth',
        'get_tagihan', 'list_tagihan', 'create_tagihan', 'generate_mass', 'update_tagihan_status',
        'create_pembayaran', 'list_pembayaran', 'confirm_pembayaran',
        'dashboard_stats', 'list_siswa'
    ]
], 400);