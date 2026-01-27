<?php

define('BASE_PATH', dirname(__DIR__));
// echo BASE_PATH;
// Core classes
require_once BASE_PATH . '/app/Core/helpers.php';
require_once BASE_PATH . '/app/Core/Auth.php';
Auth::start();
require_once BASE_PATH . '/app/Core/Database.php';
require_once BASE_PATH . '/app/Core/Controller.php';
require_once BASE_PATH . '/app/Core/Router.php';

// Controllers
require_once BASE_PATH . '/app/Controllers/HomeController.php';
require_once BASE_PATH . '/app/Controllers/PredictionController.php';
require_once BASE_PATH . '/app/Controllers/DashboardController.php';
require_once BASE_PATH . '/app/Controllers/AuthController.php';
require_once BASE_PATH . '/app/Controllers/ExportController.php';
require_once BASE_PATH . '/app/Controllers/MonitoringController.php';

$router = new Router();

$router->get('/', function () {
  (new HomeController())->index();
});

$router->get('/dashboard', function () {
  (new DashboardController())->index();
});

$router->post('/api/predict', function () {
  (new PredictionController())->predict();
});
$router->get('/login', fn() => (new AuthController())->loginForm());
$router->post('/api/login', fn() => (new AuthController())->login());
$router->get('/logout', fn() => (new AuthController())->logout());

$router->get('/export/csv', fn() => (new ExportController())->csv());
$router->get('/export/pdf', fn() => (new ExportController())->pdf());

$router->get('/monitoring', fn() => (new MonitoringController())->index());

$router->dispatch();
