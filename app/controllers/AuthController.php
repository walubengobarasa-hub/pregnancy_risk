<?php
require_once __DIR__ . '/../Models/User.php';

class AuthController extends Controller {

  public function loginForm(): void {
    $this->view('auth/login', []);
  }

  public function registerForm(): void {
    $this->view('auth/register', []);
  }

  public function login(): void {
    $payload = $_POST ?: json_decode(file_get_contents('php://input'), true);

    $email = strtolower(trim((string)($payload['email'] ?? '')));
    $pass  = (string)($payload['password'] ?? '');

    if ($email === '' || $pass === '') {
      $this->json(['error' => 'Email and password are required'], 422);
    }

    $user = User::findByEmail($email);

    if (!$user || (int)($user['is_active'] ?? 1) !== 1) {
      $this->json(['error' => 'Invalid credentials'], 401);
    }

    if (!password_verify($pass, $user['password_hash'])) {
      $this->json(['error' => 'Invalid credentials'], 401);
    }

    Auth::login($user);
    $this->json(['ok' => true, 'redirect' => base_url('dashboard')]);
  }

  public function register(): void {
    $payload = $_POST ?: json_decode(file_get_contents('php://input'), true);

    $fullName = trim((string)($payload['full_name'] ?? ''));
    $email = strtolower(trim((string)($payload['email'] ?? '')));
    $pass = (string)($payload['password'] ?? '');
    $confirm = (string)($payload['confirm_password'] ?? '');

    // Basic validation
    if ($fullName === '' || mb_strlen($fullName) < 2 || mb_strlen($fullName) > 120) {
      $this->json(['error' => 'Full name must be 2–120 characters'], 422);
    }

    if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL) || mb_strlen($email) > 190) {
      $this->json(['error' => 'Please enter a valid email'], 422);
    }

    if (mb_strlen($pass) < 8) {
      $this->json(['error' => 'Password must be at least 8 characters'], 422);
    }

    if ($pass !== $confirm) {
      $this->json(['error' => 'Passwords do not match'], 422);
    }

    if (User::emailExists($email)) {
      $this->json(['error' => 'That email is already registered'], 409);
    }

    $hash = password_hash($pass, PASSWORD_DEFAULT);

    try {
      $userId = User::create([
        'full_name' => $fullName,
        'email' => $email,
        'password_hash' => $hash,
        'role' => 'user', // default role for self-registration
      ]);
    } catch (Throwable $e) {
      // Handle unique constraint / DB issues safely
      $this->json(['error' => 'Unable to create account. Please try again.'], 500);
    }

    $user = User::findById($userId);
    if ($user) {
      Auth::login($user); // auto-login after registration
    }

    $this->json([
      'ok' => true,
      'redirect' => base_url('dashboard'),
      'message' => 'Account created successfully'
    ]);
  }

  public function logout(): void {
    Auth::logout();
    header("Location: " . base_url());
    exit;
  }
}
