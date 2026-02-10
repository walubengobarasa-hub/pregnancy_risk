<?php

class SymptomReport {

  public static function create(array $row): int {
    $db = Database::connect();

    $stmt = $db->prepare("
      INSERT INTO symptom_reports
      (user_id, assessment_id, headache, vision_changes, swelling_face_hands, upper_abdominal_pain,
       shortness_of_breath, severe_weakness, bp_systolic, bp_diastolic, notes)
      VALUES
      (:user_id, :assessment_id, :headache, :vision_changes, :swelling_face_hands, :upper_abdominal_pain,
       :shortness_of_breath, :severe_weakness, :bp_systolic, :bp_diastolic, :notes)
    ");

    $stmt->execute([
      ':user_id' => $row['user_id'] ?? null,
      ':assessment_id' => $row['assessment_id'] ?? null,
      ':headache' => (int)!empty($row['headache']),
      ':vision_changes' => (int)!empty($row['vision_changes']),
      ':swelling_face_hands' => (int)!empty($row['swelling_face_hands']),
      ':upper_abdominal_pain' => (int)!empty($row['upper_abdominal_pain']),
      ':shortness_of_breath' => (int)!empty($row['shortness_of_breath']),
      ':severe_weakness' => (int)!empty($row['severe_weakness']),
      ':bp_systolic' => ($row['bp_systolic'] ?? null) !== '' ? $row['bp_systolic'] : null,
      ':bp_diastolic' => ($row['bp_diastolic'] ?? null) !== '' ? $row['bp_diastolic'] : null,
      ':notes' => $row['notes'] ?? null,
    ]);

    return (int)$db->lastInsertId();
  }
}
