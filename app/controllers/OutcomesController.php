<?php
require_once __DIR__ . '/../Models/ImpactOutcome.php';
require_once __DIR__ . '/../Models/InteractionEvent.php';

class OutcomesController extends Controller {

  public function submit(): void {
    $payload = json_input();
    if (!$payload) $this->json(['error' => 'Invalid JSON body'], 400);

    $assessmentId = isset($payload['assessment_id']) ? (int)$payload['assessment_id'] : null;

    // Clamp helpers
    $clamp = function($v, $min, $max) {
      if ($v === null || $v === '') return null;
      $n = (int)$v;
      if ($n < $min) $n = $min;
      if ($n > $max) $n = $max;
      return $n;
    };

    $row = [
      'assessment_id' => $assessmentId,
      'knowledge_score' => $clamp($payload['knowledge_score'] ?? null, 0, 5),
      'warning_sign_recognition' => $clamp($payload['warning_sign_recognition'] ?? null, 1, 5),
      'self_efficacy' => $clamp($payload['self_efficacy'] ?? null, 1, 5),
      'care_seeking_intention' => $clamp($payload['care_seeking_intention'] ?? null, 1, 5),
      'notes' => isset($payload['notes']) ? trim((string)$payload['notes']) : null,
    ];

    $user = Auth::user();
    $userId = $user ? (int)$user['id'] : null;
    $row['user_id'] = $userId;

    $id = ImpactOutcome::create($row);

    InteractionEvent::log($userId, $assessmentId, 'impact_outcomes_submitted', [
      'impact_outcome_id' => $id
    ]);

    $this->json(['ok' => true, 'id' => $id]);
  }
}
