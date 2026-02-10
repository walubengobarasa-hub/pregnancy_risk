<?php
class Assessment {

  public static function create(array $row): int {
    $db = Database::connect();

    // Optional user binding
    $user = class_exists('Auth') ? Auth::user() : null;
    $userId = $user ? (int)$user['id'] : null;
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
    $stmt = $db->prepare("SELECT * FROM assessments WHERE id = :id LIMIT 1");
    $stmt->execute([':id' => $id]);
    $row = $stmt->fetch();
    return $row ?: null;
  }

  public static function latest(int $limit = 20): array {
    $db = Database::connect();
    $stmt = $db->prepare("SELECT * FROM assessments ORDER BY id DESC LIMIT :lim");
    $stmt->bindValue(':lim', $limit, PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetchAll();
  }

  public static function stats(): array {
    $db = Database::connect();

    $total = (int)$db->query("SELECT COUNT(*) AS c FROM assessments")->fetch()['c'];

    $high = (int)$db->query("SELECT COUNT(*) AS c FROM assessments WHERE risk_level='High'")->fetch()['c'];
    $moderate = (int)$db->query("SELECT COUNT(*) AS c FROM assessments WHERE risk_level='Moderate'")->fetch()['c'];
    $low = (int)$db->query("SELECT COUNT(*) AS c FROM assessments WHERE risk_level='Low'")->fetch()['c'];

    $avg = $db->query("SELECT AVG(risk_probability) AS a FROM assessments")->fetch()['a'];
    $avg = $avg === null ? 0.0 : (float)$avg;

    return [
      'total' => $total,
      'high' => $high,
      'moderate' => $moderate,
      'low' => $low,
      'avg_prob' => $avg,
    ];
  }
}
