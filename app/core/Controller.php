<?php
class Controller {
  protected function view(string $view, array $data = []): void {
    extract($data);
    require __DIR__ . "/../Views/{$view}.php";
  }

  protected function json($payload, int $code = 200): void {
    http_response_code($code);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($payload);
    exit;
  }
}
