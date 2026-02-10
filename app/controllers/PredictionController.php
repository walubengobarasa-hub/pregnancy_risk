<?php
require_once __DIR__ . '/../Models/Assessment.php';
require_once __DIR__ . '/../Models/InterventionContent.php';
require_once __DIR__ . '/../Models/InteractionEvent.php';
require_once __DIR__ . '/../Models/Alert.php';
require_once __DIR__ . '/../Core/InterventionEngine.php';

class PredictionController extends Controller {

  public function predict(): void {
    $config = require __DIR__ . '/../../config/config.php';
    $fastapi = $config['app']['fastapi_url'];

    $payload = json_decode(file_get_contents('php://input'), true);
    if (!is_array($payload)) $this->json(['error' => 'Invalid JSON body'], 400);

    $explain = !empty($payload['__explain']);
    $topK    = isset($payload['__top_k']) ? (int)$payload['__top_k'] : 6;

    unset($payload['__explain'], $payload['__top_k']);

    $required = [
      "Age","Systolic BP","Diastolic","BS","Body Temp","BMI",
      "Previous Complications","Preexisting Diabetes","Gestational Diabetes",
      "Mental Health","Heart Rate"
    ];

    foreach ($required as $k) {
      if (!array_key_exists($k, $payload)) {
        $this->json(['error' => "Missing field: {$k}"], 422);
      }
      if ($payload[$k] === '' || $payload[$k] === null) {
        $this->json(['error' => "Empty field: {$k}"], 422);
      }
    }

    $numeric = ["Age","Systolic BP","Diastolic","BS","Body Temp","BMI","Heart Rate"];
    foreach ($numeric as $k) $payload[$k] = (float)$payload[$k];

    $binary = ["Previous Complications","Preexisting Diabetes","Gestational Diabetes","Mental Health"];
    foreach ($binary as $k) $payload[$k] = (int)$payload[$k];

    // Call FastAPI
    $ch = curl_init($fastapi);
    curl_setopt_array($ch, [
      CURLOPT_RETURNTRANSFER => true,
      CURLOPT_POST => true,
      CURLOPT_CONNECTTIMEOUT => 5,
      CURLOPT_TIMEOUT => 25,
      CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
      CURLOPT_POSTFIELDS => json_encode([
        'features' => $payload,
        'explain'  => $explain,
        'top_k'    => $topK
      ])
    ]);

    $raw = curl_exec($ch);
    $err = curl_error($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($raw === false) $this->json(['error' => "ML service error: {$err}"], 502);

    $resp = json_decode($raw, true);

    if ($httpCode >= 400) {
      $this->json([
        'error' => 'ML service returned an error',
        'status' => $httpCode,
        'detail' => $resp ?: $raw
      ], 502);
    }

    if (!is_array($resp) || !isset($resp['probability_high_risk'])) {
      $this->json(['error' => 'Invalid response from ML service', 'raw' => $raw], 502);
    }

    // Save assessment
    $payload['probability_high_risk'] = (float)$resp['probability_high_risk'];
    $payload['risk_tier'] = (string)$resp['risk_tier'];

    $assessmentId = Assessment::create($payload);
    $assessmentRow = Assessment::find($assessmentId);

    // Build personalized bundle (education + actions + impact prompts)
    $bundle = InterventionEngine::buildBundle(
      $assessmentRow ?: ['risk_level'=>$payload['risk_tier'], 'risk_probability'=>$payload['probability_high_risk']],
      $resp['top_contributors'] ?? null
    );

    // Log interaction event (feedback loop)
    $user = Auth::user();
    $userId = $user ? (int)$user['id'] : null;
    InteractionEvent::log($userId, $assessmentId, 'assessment_completed', [
      'risk_tier' => $payload['risk_tier'],
      'probability_high_risk' => $payload['probability_high_risk'],
      'explain' => $explain ? 1 : 0
    ]);

    // Create alert if bundle suggests it
    $alertId = null;
    if (!empty($bundle['alert'])) {
      $alertId = Alert::create([
        'user_id' => $userId,
        'assessment_id' => $assessmentId,
        'severity' => $bundle['alert']['severity'] ?? 'warning',
        'title' => $bundle['alert']['title'] ?? 'Alert',
        'message' => $bundle['alert']['message'] ?? '',
      ]);
      InteractionEvent::log($userId, $assessmentId, 'alert_created', [
        'alert_id' => $alertId,
        'severity' => $bundle['alert']['severity'] ?? 'warning'
      ]);
    }

    $this->json([
      'id' => $assessmentId,
      'probability_high_risk' => $payload['probability_high_risk'],
      'risk_tier' => $payload['risk_tier'],
      'binary_label' => (int)($resp['binary_label'] ?? 0),
      'threshold_used' => (float)($resp['threshold_used'] ?? 0.5),
      'top_contributors' => $resp['top_contributors'] ?? null,

      // OUTPUT STAGE
      'bundle' => $bundle,
      'alert_id' => $alertId,
    ]);
  }
}
