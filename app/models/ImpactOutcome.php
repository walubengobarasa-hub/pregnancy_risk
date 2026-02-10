<?php

class ImpactOutcome {

  public static function create(array $row): int {
    $db = Database::connect();

    $stmt = $db->prepare("
      INSERT INTO impact_outcomes
      (user_id, assessment_id, knowledge_score, warning_sign_recognition, self_efficacy, care_seeking_intention, notes)
      VALUES
      (:user_id, :assessment_id, :knowledge_score, :warning_sign_recognition, :self_efficacy, :care_seeking_intention, :notes)
    ");

    $stmt->execute([
      ':user_id' => $row['user_id'] ?? null,
      ':assessment_id' => $row['assessment_id'] ?? null,
      ':knowledge_score' => isset($row['knowledge_score']) ? (int)$row['knowledge_score'] : null,
      ':warning_sign_recognition' => isset($row['warning_sign_recognition']) ? (int)$row['warning_sign_recognition'] : null,
      ':self_efficacy' => isset($row['self_efficacy']) ? (int)$row['self_efficacy'] : null,
      ':care_seeking_intention' => isset($row['care_seeking_intention']) ? (int)$row['care_seeking_intention'] : null,
      ':notes' => $row['notes'] ?? null,
    ]);

    return (int)$db->lastInsertId();
  }
}
