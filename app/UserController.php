<?php
// app/UserController.php
// Basic user controller skeleton

require_once __DIR__ . '/Database.php';
require_once __DIR__ . '/Auth.php';

class UserController {
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

	// Example: get current user
	public function me() {
		$user = $this->auth->user();
		if (!$user) return $this->jsonResponse(['error' => 'Unauthorized'], 401);
		unset($user['password_hash']);
		$this->jsonResponse(['user' => $user]);
	}

	private function jsonResponse($data, $status = 200) {
		http_response_code($status);
		header('Content-Type: application/json; charset=utf-8');
		echo json_encode($data, JSON_UNESCAPED_UNICODE);
		exit;
	}
}

