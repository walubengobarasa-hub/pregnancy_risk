<?php
require_once __DIR__ . '/../Models/Assessment.php';
require_once __DIR__ . '/../Models/SymptomReport.php';
require_once __DIR__ . '/../Models/Alert.php';
require_once __DIR__ . '/../Models/InteractionEvent.php';
require_once __DIR__ . '/../Core/InterventionEngine.php';

class SymptomsController extends Controller {

  public function index(): void {
    // Can be public; if logged in, we tie to user_id automatically in model
    $this->view('symptoms', []);
  }

  public function submit(): void {
    $payload = json_input();
    if (!$payload) $this->json(['error' => 'Invalid JSON body'], 400);

    $assessmentId = isset($payload['assessment_id']) ? (int)$payload['assessment_id'] : null;
    $latestAssessment = $assessmentId ? Assessment::find($assessmentId) : null;

    $user = Auth::user();
    $userId = $user ? (int)$user['id'] : null;

    $row = [
      'user_id' => $userId,
      'assessment_id' => $assessmentId,
      'headache' => !empty($payload['headache']),
      'vision_changes' => !empty($payload['vision_changes']),
      'swelling_face_hands' => !empty($payload['swelling_face_hands']),
      'upper_abdominal_pain' => !empty($payload['upper_abdominal_pain']),
      'shortness_of_breath' => !empty($payload['shortness_of_breath']),
      'severe_weakness' => !empty($payload['severe_weakness']),
      'bp_systolic' => $payload['bp_systolic'] ?? null,
      'bp_diastolic' => $payload['bp_diastolic'] ?? null,
      'notes' => isset($payload['notes']) ? trim((string)$payload['notes']) : null,
    ];

    $reportId = SymptomReport::create($row);

    $evaluation = InterventionEngine::evaluateSymptoms($row, $latestAssessment);

    // Log interaction
    InteractionEvent::log($userId, $assessmentId, 'symptom_report_submitted', [
      'report_id' => $reportId,
      'score' => $evaluation['score'],
      'severity' => $evaluation['severity'],
    ]);

    // Create alert if warning/urgent
    $alertId = null;
    if (in_array($evaluation['severity'], ['warning','urgent'], true)) {
      $alertId = Alert::create([
        'user_id' => $userId,
        'assessment_id' => $assessmentId,
        'symptom_report_id' => $reportId,
        'severity' => $evaluation['severity'],
        'title' => $evaluation['title'],
        'message' => $evaluation['message'],
      ]);

      InteractionEvent::log($userId, $assessmentId, 'alert_created', [
        'alert_id' => $alertId,
        'severity' => $evaluation['severity'],
        'source' => 'symptoms'
      ]);
    }

    $this->json([
      'ok' => true,
      'report_id' => $reportId,
      'alert_id' => $alertId,
      'evaluation' => $evaluation,
      'disclaimer' => 'Symptom checker is educational support; not a diagnosis. Seek professional care if concerned.'
    ]);
  }

  public function acknowledgeAlert(): void {
    $payload = json_input();
    $id = isset($payload['alert_id']) ? (int)$payload['alert_id'] : 0;
    if ($id <= 0) $this->json(['error' => 'alert_id is required'], 422);

    $user = Auth::user();
    $userId = $user ? (int)$user['id'] : null;

    $ok = Alert::acknowledge($id, $userId);
    if (!$ok) $this->json(['error' => 'Unable to acknowledge alert'], 404);

    InteractionEvent::log($userId, null, 'alert_acknowledged', ['alert_id' => $id]);
    $this->json(['ok' => true]);
  }
}
