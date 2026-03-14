<?php
require_once __DIR__ . '/../Models/Assessment.php';

class DashboardController extends Controller {
  public function index(): void {
    Auth::requireLogin();
    $stats = Assessment::stats();
    $items = Assessment::latest(25);
    $this->view('dashboard', compact('stats', 'items'));
  }
}
