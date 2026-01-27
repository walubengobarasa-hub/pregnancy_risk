<?php
require_once __DIR__ . '/../app/Core/Env.php';
Env::load(__DIR__ . '/../.env');

return [
  'app' => [
    'base_url' => Env::get('APP_BASE_URL', 'http://localhost/pregnancy_risk'),
    'fastapi_url' => Env::get('FASTAPI_URL', 'http://127.0.0.1:8000/predict'),
    'debug' => (bool)Env::get('APP_DEBUG', 0),
  ],
  'db' => [
    'host' => Env::get('DB_HOST', 'localhost'),
    'name' => Env::get('DB_NAME', 'pregnancy_risk'),
    'user' => Env::get('DB_USER', 'root'),
    'pass' => Env::get('DB_PASS', ''),
    'charset' => Env::get('DB_CHARSET', 'utf8mb4'),
  ]
];
