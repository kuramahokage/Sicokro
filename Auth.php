<?php
// app/Auth.php
class Auth {
    private $pdo;
    private $user = null;

    public function __construct(PDO $pdo) {
        $this->pdo = $pdo;
        // start session safe
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        $this->checkFromSessionOrBearer();
    }

    private function checkFromSessionOrBearer() {
        // 1) Session-based
        if (!empty($_SESSION['user_id'])) {
            $this->user = $this->getUserById((int)$_SESSION['user_id']);
            return;
        }

        // 2) Bearer token (Authorization header)
        $headers = $this->getAuthorizationHeader();
        if ($headers && preg_match('/Bearer\s(\S+)/', $headers, $matches)) {
            $token = $matches[1];
            $this->user = $this->getUserByToken($token);
        }
    }

    private function getAuthorizationHeader() {
        if (!empty($_SERVER['HTTP_AUTHORIZATION'])) {
            return trim($_SERVER['HTTP_AUTHORIZATION']);
        }
        if (!empty($_SERVER['Authorization'])) { // some servers
            return trim($_SERVER['Authorization']);
        }
        // apache_request_headers fallback
        if (function_exists('apache_request_headers')) {
            $req = apache_request_headers();
            if (!empty($req['Authorization'])) return trim($req['Authorization']);
        }
        return null;
    }

    private function getUserById(int $id) {
        $stmt = $this->pdo->prepare("SELECT * FROM users WHERE id = :id LIMIT 1");
        $stmt->execute([':id' => $id]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    private function getUserByToken(string $token) {
        $stmt = $this->pdo->prepare("SELECT * FROM users WHERE api_token = :token LIMIT 1");
        $stmt->execute([':token' => $token]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    public function check() : bool {
        return $this->user !== null;
    }

    public function user() {
        return $this->user;
    }

    // role check helper
    public function hasRole(string $role) : bool {
        if (!$this->user) return false;
        return (isset($this->user['role']) && $this->user['role'] === $role);
    }
}