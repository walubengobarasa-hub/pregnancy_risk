<?php
define('BASE_PATH', dirname(__DIR__));

require_once BASE_PATH . '/app/Core/helpers.php';
require_once BASE_PATH . '/app/Core/Auth.php';
Auth::start();
require_once BASE_PATH . '/app/Core/Database.php';
require_once BASE_PATH . '/app/Core/Controller.php';
require_once BASE_PATH . '/app/Core/Router.php';
require_once BASE_PATH . '/app/Core/SimplePdf.php';

require_once BASE_PATH . '/app/Controllers/HomeController.php';
require_once BASE_PATH . '/app/Controllers/PredictionController.php';
require_once BASE_PATH . '/app/Controllers/DashboardController.php';
require_once BASE_PATH . '/app/Controllers/AuthController.php';
require_once BASE_PATH . '/app/Controllers/ExportController.php';
require_once BASE_PATH . '/app/Controllers/MonitoringController.php';
require_once BASE_PATH . '/app/Controllers/InteractionController.php';
require_once BASE_PATH . '/app/Controllers/OutcomesController.php';
require_once BASE_PATH . '/app/Controllers/SymptomsController.php';
require_once BASE_PATH . '/app/Controllers/AdminController.php';
require_once BASE_PATH . '/app/Controllers/GameController.php';

$router = new Router();
$router->get('/', fn() => (new HomeController())->index());
$router->get('/dashboard', fn() => (new DashboardController())->index());
$router->get('/monitoring', fn() => (new MonitoringController())->index());
$router->get('/game', fn() => (new GameController())->index());

$router->post('/api/predict', fn() => (new PredictionController())->predict());
$router->post('/api/interactions', fn() => (new InteractionController())->log());
$router->post('/api/outcomes', fn() => (new OutcomesController())->submit());
$router->get('/symptoms', fn() => (new SymptomsController())->index());
$router->post('/api/symptoms', fn() => (new SymptomsController())->submit());
$router->post('/api/alerts/ack', fn() => (new SymptomsController())->acknowledgeAlert());

$router->get('/login', fn() => (new AuthController())->loginForm());
$router->post('/api/login', fn() => (new AuthController())->login());
$router->get('/register', fn() => (new AuthController())->registerForm());
$router->post('/api/register', fn() => (new AuthController())->register());
$router->get('/logout', fn() => (new AuthController())->logout());

$router->get('/export/csv', fn() => (new ExportController())->csv());
$router->get('/export/pdf', fn() => (new ExportController())->pdf());

$router->get('/admin/users', fn() => (new AdminController())->index());
$router->post('/admin/users/store', fn() => (new AdminController())->store());
$router->post('/admin/users/update', fn() => (new AdminController())->update());
$router->post('/admin/users/delete', fn() => (new AdminController())->delete());

$router->dispatch();
