<?php

class InterventionContent {

  public static function getBundleByTier(string $tier): array {
    $db = Database::connect();

    // If table not populated yet, fall back to defaults
    try {
      $stmt = $db->prepare("
        SELECT content_type, title, body, lang
        FROM intervention_contents
        WHERE tier = :tier AND is_active = 1
        ORDER BY sort_order ASC, id ASC
      ");
      $stmt->execute([':tier' => $tier]);
      $rows = $stmt->fetchAll();

      if (!$rows) {
        return self::defaultBundle($tier);
      }

      $out = [];
      foreach ($rows as $r) {
        $type = (string)$r['content_type'];
        if (!isset($out[$type])) $out[$type] = [];
        $out[$type][] = [
          'title' => (string)$r['title'],
          'body' => (string)$r['body'],
          'lang' => (string)$r['lang'],
        ];
      }

      return $out;
    } catch (Throwable $e) {
      return self::defaultBundle($tier);
    }
  }

  private static function defaultBundle(string $tier): array {
    if ($tier === 'High') {
      return [
        'education' => [[
          'title' => 'What this high-risk screening means',
          'body'  => 'High-risk screening suggests you may need prompt clinical evaluation. Share results with a qualified clinician.'
        ]],
        'warning_signs' => [[
          'title' => 'Warning signs to act on',
          'body'  => 'Severe headache, vision changes, swelling of face/hands, breathing difficulty, or severe abdominal pain: seek urgent care.'
        ]],
        'prevention' => [[
          'title' => 'Prevention & follow-up',
          'body'  => 'Do not miss ANC visits. Monitor BP if possible. Follow clinician advice and any prescribed treatment plan.'
        ]]
      ];
    }

    if ($tier === 'Moderate') {
      return [
        'education' => [[
          'title' => 'Moderate-risk: focus on prevention',
          'body'  => 'Moderate-risk screening means closer attention can help reduce complications. Keep ANC visits and track BP.'
        ]],
        'prevention' => [[
          'title' => 'Practical prevention strategies',
          'body'  => 'Follow a clinician plan, avoid missing appointments, and use symptom check-ins if you feel unwell.'
        ]],
        'general' => [[
          'title' => 'HDP awareness',
          'body'  => 'HDP is a pregnancy blood pressure condition. Early recognition + care-seeking can improve outcomes.'
        ]]
      ];
    }

    // Low
    return [
      'education' => [[
        'title' => 'Low-risk: stay informed',
        'body'  => 'Low-risk screening is reassuring, but continue ANC visits and watch for warning signs.'
      ]],
      'general' => [[
        'title' => 'General HDP awareness',
        'body'  => 'Learn warning signs and keep healthy habits. Seek care if you notice concerning symptoms.'
      ]]
    ];
  }
}
