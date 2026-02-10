<?php

function config(string $key, $default = null) {
  static $cfg = null;
  if ($cfg === null) $cfg = require __DIR__ . '/../../config/config.php';

  $parts = explode('.', $key);
  $val = $cfg;
  foreach ($parts as $p) {
    if (!is_array($val) || !array_key_exists($p, $val)) return $default;
    $val = $val[$p];
  }
  return $val;
}

function base_url(string $path = ''): string {
  $base = rtrim((string)config('app.base_url', ''), '/');
  $path = ltrim($path, '/');
  return $path ? "{$base}/{$path}" : $base;
}

/**
 * Minimal CSRF protection for form POSTs (optional but recommended).
 * For JSON APIs, you can pass X-CSRF-Token header if you want stricter control.
 */
function csrf_token(): string {
  if (session_status() === PHP_SESSION_NONE) session_start();
  if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
  }
  return (string)$_SESSION['csrf_token'];
}

function csrf_validate(?string $token): bool {
  if (session_status() === PHP_SESSION_NONE) session_start();
  $known = $_SESSION['csrf_token'] ?? null;
  if (!$known || !$token) return false;
  return hash_equals($known, $token);
}

function json_input(): array {
  $raw = file_get_contents('php://input');
  $data = json_decode($raw ?: '', true);
  return is_array($data) ? $data : [];
}

function h(string $s): string {
  return htmlspecialchars($s, ENT_QUOTES, 'UTF-8');
}

function sha256(?string $s): ?string {
  if ($s === null || $s === '') return null;
  return hash('sha256', $s);
}

function client_ip(): string {
  // Basic, safe; do not trust forwarded headers by default
  return $_SERVER['REMOTE_ADDR'] ?? '';
}

function user_agent(): string {
  return $_SERVER['HTTP_USER_AGENT'] ?? '';
}
