<?php
require_once __DIR__ . '/../Models/Assessment.php';

class ExportController extends Controller {

  public function csv(): void {
    Auth::requireRole(['clinician','admin']);

    $rows = Assessment::latest(500);

    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="assessments.csv"');

    $out = fopen('php://output', 'w');
    fputcsv($out, ['id','created_at','age','systolic_bp','diastolic','bs','body_temp','bmi','prev_comp','preexist_dm','gest_dm','mental_health','heart_rate','risk_probability','risk_level']);

    foreach ($rows as $r) {
      fputcsv($out, [
        $r['id'], $r['created_at'], $r['age'], $r['systolic_bp'], $r['diastolic'], $r['bs'],
        $r['body_temp'], $r['bmi'], $r['previous_complications'], $r['preexisting_diabetes'],
        $r['gestational_diabetes'], $r['mental_health'], $r['heart_rate'],
        $r['risk_probability'], $r['risk_level']
      ]);
    }

    fclose($out);
    exit;
  }

  public function pdf(): void {
    Auth::requireRole(['clinician','admin']);

    // Install dompdf once:
    // composer require dompdf/dompdf
    require_once __DIR__ . '/../../vendor/autoload.php';
    $rows = Assessment::latest(200);

    $html = "<h2>MaternalCare AI — Assessments Report</h2><p>Generated: " . date('Y-m-d H:i') . "</p>";
    $html .= "<table width='100%' border='1' cellspacing='0' cellpadding='6'>
      <thead><tr>
        <th>ID</th><th>Date</th><th>Age</th><th>BP</th><th>BS</th><th>BMI</th><th>Prob</th><th>Tier</th>
      </tr></thead><tbody>";

    foreach ($rows as $r) {
      $html .= "<tr>
        <td>{$r['id']}</td>
        <td>{$r['created_at']}</td>
        <td>{$r['age']}</td>
        <td>{$r['systolic_bp']}/{$r['diastolic']}</td>
        <td>{$r['bs']}</td>
        <td>{$r['bmi']}</td>
        <td>" . round($r['risk_probability'] * 100, 1) . "%</td>
        <td>{$r['risk_level']}</td>
      </tr>";
    }
    $html .= "</tbody></table>";

    $dompdf = new \Dompdf\Dompdf();
    $dompdf->loadHtml($html);
    $dompdf->setPaper('A4', 'portrait');
    $dompdf->render();

    header("Content-Type: application/pdf");
    header("Content-Disposition: attachment; filename=assessments_report.pdf");
    echo $dompdf->output();
    exit;
  }
}
