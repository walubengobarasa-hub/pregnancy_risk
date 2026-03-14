<?php
class Assessment {

  public static function create(array $row): int {
    $db = Database::connect();
    $userId = Auth::id();
    $sessionHash = sha256(session_id());

    $sql = "INSERT INTO assessments
      (user_id, session_hash,
       age, systolic_bp, diastolic, bs, body_temp, bmi,
       previous_complications, preexisting_diabetes, gestational_diabetes,
       mental_health, heart_rate, risk_probability, risk_level)
      VALUES
      (:user_id, :session_hash,
       :age, :systolic_bp, :diastolic, :bs, :body_temp, :bmi,
       :previous_complications, :preexisting_diabetes, :gestational_diabetes,
       :mental_health, :heart_rate, :risk_probability, :risk_level)";

    $stmt = $db->prepare($sql);
    $stmt->execute([
      ':user_id' => $userId,
      ':session_hash' => $sessionHash,
      ':age' => $row['Age'],
      ':systolic_bp' => $row['Systolic BP'],
      ':diastolic' => $row['Diastolic'],
      ':bs' => $row['BS'],
      ':body_temp' => $row['Body Temp'],
      ':bmi' => $row['BMI'],
      ':previous_complications' => $row['Previous Complications'],
      ':preexisting_diabetes' => $row['Preexisting Diabetes'],
      ':gestational_diabetes' => $row['Gestational Diabetes'],
      ':mental_health' => $row['Mental Health'],
      ':heart_rate' => $row['Heart Rate'],
      ':risk_probability' => $row['probability_high_risk'],
      ':risk_level' => $row['risk_tier'],
    ]);

    return (int)$db->lastInsertId();
  }

  public static function find(int $id): ?array {
    $db = Database::connect();
    $stmt = $db->prepare('SELECT * FROM assessments WHERE id = :id LIMIT 1');
    $stmt->execute([':id' => $id]);
    $row = $stmt->fetch();
    return $row ?: null;
  }

  public static function ownedByCurrentUser(int $assessmentId): bool {
    $assessment = self::find($assessmentId);
    if (!$assessment) return false;
    if (Auth::isAdmin() || Auth::isClinician()) return true;
    return Auth::id() !== null && (int)$assessment['user_id'] === Auth::id();
  }

  public static function latest(int $limit = 20): array {
    $db = Database::connect();
    if (Auth::isAdmin() || Auth::isClinician()) {
      $stmt = $db->prepare('SELECT a.*, u.full_name, u.email FROM assessments a LEFT JOIN users u ON u.id = a.user_id ORDER BY a.id DESC LIMIT :lim');
      $stmt->bindValue(':lim', $limit, PDO::PARAM_INT);
      $stmt->execute();
      return $stmt->fetchAll();
    }

    Auth::requireLogin();
    $stmt = $db->prepare('SELECT a.*, u.full_name, u.email FROM assessments a LEFT JOIN users u ON u.id = a.user_id WHERE a.user_id = :uid ORDER BY a.id DESC LIMIT :lim');
    $stmt->bindValue(':uid', Auth::id(), PDO::PARAM_INT);
    $stmt->bindValue(':lim', $limit, PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetchAll();
  }

  public static function forExport(int $limit = 500): array {
    return self::latest($limit);
  }

  public static function stats(): array {
    $db = Database::connect();
    $where = '';
    $params = [];

    if (!(Auth::isAdmin() || Auth::isClinician())) {
      Auth::requireLogin();
      $where = ' WHERE user_id = :uid';
      $params[':uid'] = Auth::id();
    }

    $totalStmt = $db->prepare('SELECT COUNT(*) AS c FROM assessments' . $where);
    $totalStmt->execute($params);
    $total = (int)$totalStmt->fetch()['c'];

    $countByRisk = function (string $tier) use ($db, $where, $params): int {
      $stmt = $db->prepare('SELECT COUNT(*) AS c FROM assessments' . $where . ($where ? ' AND' : ' WHERE') . ' risk_level = :tier');
      $stmt->execute($params + [':tier' => $tier]);
      return (int)$stmt->fetch()['c'];
    };

    $avgStmt = $db->prepare('SELECT AVG(risk_probability) AS a FROM assessments' . $where);
    $avgStmt->execute($params);
    $avg = $avgStmt->fetch()['a'];

    return [
      'total' => $total,
      'high' => $countByRisk('High'),
      'moderate' => $countByRisk('Moderate'),
      'low' => $countByRisk('Low'),
      'avg_prob' => $avg === null ? 0.0 : (float)$avg,
    ];
  }
}
