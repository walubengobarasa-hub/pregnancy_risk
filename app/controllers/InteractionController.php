<?php
require_once __DIR__ . '/../Models/InteractionEvent.php';

class InteractionController extends Controller {

  public function log(): void {
    $payload = json_input();
    if (!$payload) $this->json(['error' => 'Invalid JSON body'], 400);

    $eventType = trim((string)($payload['event_type'] ?? ''));
    if ($eventType === '' || strlen($eventType) > 80) {
      $this->json(['error' => 'event_type is required (max 80 chars)'], 422);
    }

    $assessmentId = isset($payload['assessment_id']) ? (int)$payload['assessment_id'] : null;
    $meta = isset($payload['meta']) && is_array($payload['meta']) ? $payload['meta'] : [];

    $user = Auth::user();
    $userId = $user ? (int)$user['id'] : null;

    InteractionEvent::log($userId, $assessmentId, $eventType, $meta);
    $this->json(['ok' => true]);
  }
}
