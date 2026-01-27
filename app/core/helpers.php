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
