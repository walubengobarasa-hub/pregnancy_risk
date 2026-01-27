<?php
require_once __DIR__ . '/../Models/User.php';

class AuthController extends Controller {
  public function loginForm(): void {
    $this->view('auth/login', []);
  }

  public function login(): void {
    $payload = $_POST ?: json_decode(file_get_contents('php://input'), true);
    $email = trim((string)($payload['email'] ?? ''));
    $pass  = (string)($payload['password'] ?? '');

    if ($email === '' || $pass === '') {
      $this->json(['error' => 'Email and password are required'], 422);
    }

    $user = User::findByEmail($email);
    if (!$user || !password_verify($pass, $user['password_hash'])) {
      $this->json(['error' => 'Invalid credentials'], 401);
    }

    Auth::login($user);
    $this->json(['ok' => true, 'redirect' => base_url('dashboard')]);
  }

  public function logout(): void {
    Auth::logout();
    header("Location: " . base_url());
    exit;
  }
}
