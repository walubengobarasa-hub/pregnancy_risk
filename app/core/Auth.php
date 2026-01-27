<?php

class Auth {
  public static function start(): void {
    if (session_status() === PHP_SESSION_NONE) {
      session_start();
    }
  }

  public static function user(): ?array {
    self::start();
    return $_SESSION['user'] ?? null;
  }

  public static function check(): bool {
    return self::user() !== null;
  }

  public static function login(array $user): void {
    self::start();
    // minimal session hardening
    session_regenerate_id(true);
    $_SESSION['user'] = [
      'id' => (int)$user['id'],
      'full_name' => (string)$user['full_name'],
      'email' => (string)$user['email'],
      'role' => (string)$user['role'],
    ];
  }

  public static function logout(): void {
    self::start();
    $_SESSION = [];
    if (ini_get("session.use_cookies")) {
      $params = session_get_cookie_params();
      setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"], $params["secure"], $params["httponly"]
      );
    }
    session_destroy();
  }

  public static function requireRole(array $roles): void {
    $u = self::user();
    if (!$u) {
      header("Location: " . base_url('login'));
      exit;
    }
    if (!in_array($u['role'], $roles, true)) {
      http_response_code(403);
      echo "403 Forbidden";
      exit;
    }
  }
}
