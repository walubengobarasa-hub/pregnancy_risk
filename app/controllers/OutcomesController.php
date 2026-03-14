<?php
require_once __DIR__ . '/../Models/ImpactOutcome.php';
require_once __DIR__ . '/../Models/InteractionEvent.php';
require_once __DIR__ . '/../Models/Assessment.php';

class OutcomesController extends Controller {

  public function submit(): void {
    Auth::requireLogin();
    $payload = json_input();
    if (!$payload) $this->json(['error' => 'Invalid JSON body'], 400);

    $assessmentId = isset($payload['assessment_id']) ? (int)$payload['assessment_id'] : null;
    if (!$assessmentId || !Assessment::ownedByCurrentUser($assessmentId)) {
      $this->json(['error' => 'Assessment not found or access denied'], 403);
    }

    $clamp = function($v, $min, $max) {
      if ($v === null || $v === '') return null;
      $n = (int)$v;
      if ($n < $min) $n = $min;
      if ($n > $max) $n = $max;
      return $n;
    };

    $userId = Auth::id();
    $row = [
      'user_id' => $userId,
      'assessment_id' => $assessmentId,
      'knowledge_score' => $clamp($payload['knowledge_score'] ?? null, 0, 5),
      'warning_sign_recognition' => $clamp($payload['warning_sign_recognition'] ?? null, 1, 5),
      'self_efficacy' => $clamp($payload['self_efficacy'] ?? null, 1, 5),
      'care_seeking_intention' => $clamp($payload['care_seeking_intention'] ?? null, 1, 5),
      'notes' => isset($payload['notes']) ? trim((string)$payload['notes']) : null,
      'game_score' => isset($payload['game_score']) ? (int)$payload['game_score'] : null,
      'game_level' => isset($payload['game_level']) ? trim((string)$payload['game_level']) : null,
    ];

    $id = ImpactOutcome::create($row);
    InteractionEvent::log($userId, $assessmentId, 'impact_outcomes_submitted', ['impact_outcome_id' => $id]);
    $this->json(['ok' => true, 'id' => $id]);
  }
}
