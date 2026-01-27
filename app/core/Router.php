<?php

class Router {
  private array $routes = [];

  // Base folder name (your XAMPP subfolder)
  private string $basePath = '/pregnancy_risk';

  public function get(string $path, callable $handler): void {
    $this->routes['GET'][$this->norm($path)] = $handler;
  }

  public function post(string $path, callable $handler): void {
    $this->routes['POST'][$this->norm($path)] = $handler;
  }

  public function dispatch(): void {
    $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

    // Only path part, ignore querystring
    $uri = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';

    // Normalize double slashes: //dashboard -> /dashboard
    $uri = preg_replace('#/+#', '/', $uri);

    // If someone accesses /pregnancy_risk/public/... strip /public as well
    if (strpos($uri, $this->basePath . '/public') === 0) {
      $uri = substr($uri, strlen($this->basePath . '/public'));
      if ($uri === '') $uri = '/';
    }

    // Strip /pregnancy_risk prefix so routes match: "/", "/dashboard", "/api/predict"
    if (strpos($uri, $this->basePath) === 0) {
      $uri = substr($uri, strlen($this->basePath));
      if ($uri === '') $uri = '/';
    }

    $uri = $this->norm($uri);

    $handler = $this->routes[$method][$uri] ?? null;

    if (!$handler) {
      http_response_code(404);
      echo "404 Not Found — route: " . htmlspecialchars($uri);
      return;
    }

    $handler();
  }

  private function norm(string $p): string {
    $p = '/' . trim($p, '/');
    return $p === '/' ? '/' : rtrim($p, '/');
  }
}
