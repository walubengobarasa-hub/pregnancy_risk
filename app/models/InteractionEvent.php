<?php

class InteractionEvent {

  public static function log(?int $userId, ?int $assessmentId, string $eventType, array $meta = []): void {
    $db = Database::connect();

    $stmt = $db->prepare("
      INSERT INTO interaction_events (user_id, assessment_id, event_type, meta_json, ip_hash, ua_hash)
      VALUES (:user_id, :assessment_id, :event_type, :meta_json, :ip_hash, :ua_hash)
    ");

    $stmt->execute([
      ':user_id' => $userId,
      ':assessment_id' => $assessmentId,
      ':event_type' => $eventType,
      ':meta_json' => $meta ? json_encode($meta) : null,
      ':ip_hash' => sha256(client_ip()),
      ':ua_hash' => sha256(user_agent()),
    ]);
  }
}
