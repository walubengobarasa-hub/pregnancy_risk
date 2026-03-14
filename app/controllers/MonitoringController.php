<?php
class MonitoringController extends Controller {
  public function index(): void {
    Auth::requireRole(['clinician','admin']);

    $db = Database::connect();
    $sql = "
      SELECT
        DATE_FORMAT(created_at, '%Y-%m') AS ym,
        COUNT(*) AS n,
        AVG(age) AS avg_age,
        AVG(systolic_bp) AS avg_sys,
        AVG(diastolic) AS avg_dia,
        AVG(bs) AS avg_bs,
        AVG(bmi) AS avg_bmi,
        AVG(risk_probability) AS avg_prob
      FROM assessments
      GROUP BY ym
      ORDER BY ym ASC
    ";
    $rows = $db->query($sql)->fetchAll();

    $this->view('monitoring', ['rows' => $rows]);
  }
}
