<?php

class Alert {

  public static function create(array $data): int {
    $db = Database::connect();

    $stmt = $db->prepare("
      INSERT INTO alerts (user_id, assessment_id, symptom_report_id, severity, title, message, status)
      VALUES (:user_id, :assessment_id, :symptom_report_id, :severity, :title, :message, 'active')
    ");

    $stmt->execute([
      ':user_id' => $data['user_id'] ?? null,
      ':assessment_id' => $data['assessment_id'] ?? null,
      ':symptom_report_id' => $data['symptom_report_id'] ?? null,
      ':severity' => $data['severity'] ?? 'warning',
      ':title' => $data['title'] ?? 'Alert',
      ':message' => $data['message'] ?? '',
    ]);

    return (int)$db->lastInsertId();
  }

  public static function activeForUser(?int $userId, int $limit = 10): array {
    $db = Database::connect();

    if ($userId) {
      $stmt = $db->prepare("
        SELECT * FROM alerts
        WHERE user_id = :uid AND status='active'
        ORDER BY id DESC
        LIMIT :lim
      ");
      $stmt->bindValue(':uid', $userId, PDO::PARAM_INT);
      $stmt->bindValue(':lim', $limit, PDO::PARAM_INT);
      $stmt->execute();
      return $stmt->fetchAll();
    }

    // Anonymous: no user alerts
    return [];
  }

  public static function acknowledge(int $id, ?int $userId): bool {
    $db = Database::connect();

    // If userId is null, allow acknowledging only if alert user_id is null (rare)
    $sql = "UPDATE alerts SET status='acknowledged', acknowledged_at=NOW() WHERE id=:id";
    if ($userId !== null) $sql .= " AND (user_id=:uid OR user_id IS NULL)";

    $stmt = $db->prepare($sql);
    $stmt->bindValue(':id', $id, PDO::PARAM_INT);
    if ($userId !== null) $stmt->bindValue(':uid', $userId, PDO::PARAM_INT);

    $stmt->execute();
    return $stmt->rowCount() > 0;
  }
}
