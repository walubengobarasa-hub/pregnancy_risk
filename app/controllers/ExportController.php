<?php
require_once __DIR__ . '/../Models/Assessment.php';
require_once __DIR__ . '/../Core/SimplePdf.php';

class ExportController extends Controller {

  private function authorize(): void {
    Auth::requireLogin();
  }

  public function csv(): void {
    $this->authorize();
    $rows = Assessment::forExport(500);

    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="assessments.csv"');

    $out = fopen('php://output', 'w');
    fputcsv($out, ['id','created_at','patient_name','patient_email','age','systolic_bp','diastolic','bs','body_temp','bmi','prev_comp','preexist_dm','gest_dm','mental_health','heart_rate','risk_probability','risk_level']);

    foreach ($rows as $r) {
      fputcsv($out, [
        $r['id'], $r['created_at'], $r['full_name'] ?? '', $r['email'] ?? '',
        $r['age'], $r['systolic_bp'], $r['diastolic'], $r['bs'],
        $r['body_temp'], $r['bmi'], $r['previous_complications'], $r['preexisting_diabetes'],
        $r['gestational_diabetes'], $r['mental_health'], $r['heart_rate'],
        $r['risk_probability'], $r['risk_level']
      ]);
    }

    fclose($out);
    exit;
  }

  public function pdf(): void {
    $this->authorize();
    $rows = Assessment::forExport(100);

    $lines = [];
    $lines[] = 'MaternalCare AI Assessments Report';
    $lines[] = 'Generated: ' . date('Y-m-d H:i');
    $lines[] = str_repeat('-', 80);

    foreach ($rows as $r) {
      $who = trim(($r['full_name'] ?? '') . ' ' . ($r['email'] ? '<' . $r['email'] . '>' : ''));
      $lines[] = sprintf(
        '#%s | %s | %s | Age %s | BP %s/%s | Risk %s%% %s',
        $r['id'],
        substr((string)$r['created_at'], 0, 16),
        $who ?: 'Anonymous',
        $r['age'],
        $r['systolic_bp'],
        $r['diastolic'],
        number_format(((float)$r['risk_probability']) * 100, 1),
        $r['risk_level']
      );
    }

    if (!$rows) {
      $lines[] = 'No assessment records found.';
    }

    $pdf = SimplePdf::fromLines($lines);
    header('Content-Type: application/pdf');
    header('Content-Disposition: attachment; filename="assessments_report.pdf"');
    echo $pdf;
    exit;
  }
}
