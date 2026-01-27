<?php
require_once __DIR__ . '/../Models/Assessment.php';

class PredictionController extends Controller {

  public function predict(): void {
    $config = require __DIR__ . '/../../config/config.php';
    $fastapi = $config['app']['fastapi_url'];

    $payload = json_decode(file_get_contents('php://input'), true);
    if (!is_array($payload)) $this->json(['error' => 'Invalid JSON body'], 400);

    // Read explain toggle coming from JS
    $explain = !empty($payload['__explain']);
    $topK    = isset($payload['__top_k']) ? (int)$payload['__top_k'] : 6;

    unset($payload['__explain'], $payload['__top_k']);

    // Required fields (exact names)
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

    // Normalize numeric types
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

    $id = Assessment::create($payload);

    $this->json([
      'id' => $id,
      'probability_high_risk' => $payload['probability_high_risk'],
      'risk_tier' => $payload['risk_tier'],
      'binary_label' => (int)($resp['binary_label'] ?? 0),
      'threshold_used' => (float)($resp['threshold_used'] ?? 0.5),
      'top_contributors' => $resp['top_contributors'] ?? null,
    ]);
  }
}
