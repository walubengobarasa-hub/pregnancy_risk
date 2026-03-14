<?php
require_once __DIR__ . '/../Models/Assessment.php';

class GameController extends Controller {
  public function index(): void {
    Auth::requireLogin();
    $assessmentId = isset($_GET['assessment_id']) ? (int)$_GET['assessment_id'] : 0;
    if ($assessmentId <= 0 || !Assessment::ownedByCurrentUser($assessmentId)) {
      header('Location: ' . base_url('dashboard'));
      exit;
    }

    $assessment = Assessment::find($assessmentId);
    $this->view('game', ['assessment' => $assessment]);
  }
}
