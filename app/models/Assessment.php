<?php
class Assessment {
  public static function create(array $row): int {
    $db = Database::connect();

    $sql = "INSERT INTO assessments
      (age, systolic_bp, diastolic, bs, body_temp, bmi,
       previous_complications, preexisting_diabetes, gestational_diabetes,
       mental_health, heart_rate, risk_probability, risk_level)
      VALUES
      (:age, :systolic_bp, :diastolic, :bs, :body_temp, :bmi,
       :previous_complications, :preexisting_diabetes, :gestational_diabetes,
       :mental_health, :heart_rate, :risk_probability, :risk_level)";

    $stmt = $db->prepare($sql);

    $stmt->execute([
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
