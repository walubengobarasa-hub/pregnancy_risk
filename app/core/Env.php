<?php

class Env {
  private static array $data = [];
  private static bool $loaded = false;

  public static function load(string $path): void {
    if (self::$loaded) return;
    self::$loaded = true;

    if (!file_exists($path)) return;

    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
      $line = trim($line);
      if ($line === '' || str_starts_with($line, '#')) continue;

      [$k, $v] = array_pad(explode('=', $line, 2), 2, '');
      $k = trim($k);
      $v = trim($v);

      // strip quotes
      $v = trim($v, "\"'");

      self::$data[$k] = $v;
      $_ENV[$k] = $v;
      putenv("{$k}={$v}");
    }
  }

  public static function get(string $key, $default = null) {
    return $_ENV[$key] ?? self::$data[$key] ?? getenv($key) ?: $default;
  }
}
