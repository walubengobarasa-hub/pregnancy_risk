<?php

class InterventionEngine {

  public static function buildBundle(array $assessmentRow, ?array $topContributors = null): array {
    $tier = (string)($assessmentRow['risk_level'] ?? 'Moderate');
    $prob = (float)($assessmentRow['risk_probability'] ?? 0);

    // A clear explanation scaffold (safe + non-clinical)
    $explain = self::explanationForTier($tier, $prob, $topContributors);

    // Recommended health actions (behavioral guidance – not “medical orders”)
    $actions = self::recommendedActions($tier, $assessmentRow);

    // Educational content selection (from DB if available; fallback defaults)
    $education = InterventionContent::getBundleByTier($tier);

    // Create an early warning alert for High tier (and sometimes Moderate + very high probability)
    $alert = null;
    if ($tier === 'High' || ($tier === 'Moderate' && $prob >= 0.70)) {
      $alert = [
        'severity' => 'urgent',
        'title' => 'High-risk screening result',
        'message' =>
          "This screening suggests elevated risk. Please seek prompt evaluation by a qualified clinician or nearest health facility. " .
          "If you have severe symptoms (headache, vision changes, swelling of face/hands, breathing difficulty, or severe abdominal pain), seek urgent care."
      ];
    }

    // Impact outcomes prompt (simple quick check)
    $outcomes_prompt = [
      'knowledge' => 'After reading the guidance, how confident are you that you understand HDP risk and warning signs?',
      'self_efficacy' => 'How confident are you you can follow the recommended actions?',
      'care_seeking' => 'How likely are you to seek care if warning signs appear?'
    ];

    return [
      'tier' => $tier,
      'probability' => $prob,
      'explanation' => $explain,
      'education' => $education,
      'recommended_actions' => $actions,
      'alert' => $alert,
      'outcomes_prompt' => $outcomes_prompt,
      'disclaimer' => 'Educational decision support only; not a diagnosis or a substitute for professional medical care.'
    ];
  }

  public static function evaluateSymptoms(array $symptoms, ?array $latestAssessment = null): array {
    $flags = [
      'headache' => !empty($symptoms['headache']),
      'vision_changes' => !empty($symptoms['vision_changes']),
      'swelling_face_hands' => !empty($symptoms['swelling_face_hands']),
      'upper_abdominal_pain' => !empty($symptoms['upper_abdominal_pain']),
      'shortness_of_breath' => !empty($symptoms['shortness_of_breath']),
      'severe_weakness' => !empty($symptoms['severe_weakness']),
    ];

    $bpSys = isset($symptoms['bp_systolic']) && $symptoms['bp_systolic'] !== '' ? (float)$symptoms['bp_systolic'] : null;
    $bpDia = isset($symptoms['bp_diastolic']) && $symptoms['bp_diastolic'] !== '' ? (float)$symptoms['bp_diastolic'] : null;

    $score = 0;
    foreach ($flags as $v) if ($v) $score += 1;

    // Heuristic: symptom cluster or very high BP readings => urgent
    $urgentBySymptoms = ($score >= 3) || ($flags['vision_changes'] && $flags['headache']) || ($flags['shortness_of_breath']);

    // Heuristic: elevated BP threshold signals risk (still not diagnosis)
    $urgentByBP = ($bpSys !== null && $bpSys >= 160) || ($bpDia !== null && $bpDia >= 110);

    // Consider latest assessment tier
    $tier = $latestAssessment ? (string)$latestAssessment['risk_level'] : null;
    $highTier = ($tier === 'High');

    $severity = 'info';
    $title = 'Symptom check recorded';
    $message = 'Thank you. Continue monitoring and follow your care plan. Seek care if symptoms worsen.';

    if ($urgentBySymptoms || $urgentByBP || $highTier) {
      $severity = 'urgent';
      $title = 'Possible warning signs detected';
      $message =
        "Your symptom report suggests possible warning signs. Please seek prompt evaluation by a qualified clinician or nearest health facility. " .
        "If symptoms are severe or worsening, treat it as urgent.";
    } elseif ($score >= 1) {
      $severity = 'warning';
      $title = 'Symptoms reported';
      $message =
        "You reported one or more symptoms. Consider contacting a qualified clinician for advice, and monitor closely. " .
        "Seek urgent care if you develop severe headache, vision changes, swelling of face/hands, breathing difficulty, or severe abdominal pain.";
    }

    return [
      'score' => $score,
      'severity' => $severity,
      'title' => $title,
      'message' => $message,
    ];
  }

  private static function explanationForTier(string $tier, float $prob, ?array $topContributors): array {
    $probPct = round($prob * 100, 1);

    $summary = "Risk tier: {$tier}. Estimated high-risk probability: {$probPct}%.";

    $howToRead = "This is a screening score based on patterns in the training data. " .
                "It highlights risk level and contributing factors, but it is not a clinical diagnosis.";

    $contributors = [];
    if (is_array($topContributors)) {
      foreach ($topContributors as $c) {
        if (!isset($c['feature'])) continue;
        $contributors[] = [
          'feature' => (string)$c['feature'],
          'weight' => isset($c['shap_value']) ? (float)$c['shap_value'] : null,
        ];
      }
    }

    return [
      'summary' => $summary,
      'how_to_read' => $howToRead,
      'contributors' => $contributors
    ];
  }

  private static function recommendedActions(string $tier, array $a): array {
    // Generic + tier-specific actions (non-prescriptive, safe)
    $common = [
      [
        'title' => 'Attend antenatal care (ANC) visits',
        'detail' => 'Keep scheduled visits and share this screening result with a clinician.'
      ],
      [
        'title' => 'Monitor blood pressure if possible',
        'detail' => 'Track readings (home device or clinic). Record dates/times for your clinician.'
      ],
      [
        'title' => 'Know warning signs',
        'detail' => 'Headache, vision changes, swelling of face/hands, breathing difficulty, severe abdominal pain: seek care promptly.'
      ],
    ];

    if ($tier === 'Low') {
      return array_merge($common, [
        [
          'title' => 'Healthy daily habits',
          'detail' => 'Balanced diet, hydration, rest, and following clinician guidance.'
        ],
        [
          'title' => 'Keep learning',
          'detail' => 'Review general HDP awareness content to stay informed.'
        ],
      ]);
    }

    if ($tier === 'Moderate') {
      return array_merge($common, [
        [
          'title' => 'Focus on prevention strategies',
          'detail' => 'Discuss prevention steps with a clinician; avoid missing appointments.'
        ],
        [
          'title' => 'Symptom check-ins',
          'detail' => 'Use the symptom checker to log any new symptoms early.'
        ],
      ]);
    }

    // High
    return array_merge($common, [
      [
        'title' => 'Seek clinician review soon',
        'detail' => 'Arrange prompt medical evaluation and follow the clinician’s plan.'
      ],
      [
        'title' => 'Medication adherence (if prescribed)',
        'detail' => 'If a clinician has prescribed medication, take it exactly as directed.'
      ],
      [
        'title' => 'More frequent monitoring',
        'detail' => 'Consider more frequent BP checks and faster reporting of symptoms.'
      ],
    ]);
  }
}
