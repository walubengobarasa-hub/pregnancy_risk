<?php
$title = "MaternalCare AI — Dashboard";
ob_start();
?>

<main class="max-w-7xl mx-auto px-4 py-12">

  <div class="flex items-end justify-between flex-wrap">
  <div>
    <h1 class="text-3xl font-semibold">Dashboard</h1>
    <p class="mt-2 text-slate-600 text-sm">
      Latest assessments and summary statistics.
    </p>
  </div>

  <!-- ACTION LINKS -->
  <div class="flex items-center gap-2">
    <a href="/" class="px-4 py-2 rounded-xl bg-white border border-slate-200 hover:bg-slate-50 text-sm">
      New assessment →
    </a>

    <a href="<?= base_url('export/csv') ?>" class="px-4 py-2 rounded-xl border bg-white hover:bg-slate-50 text-sm">
      Export CSV
    </a>

    <a href="<?= base_url('export/pdf') ?>" class="px-4 py-2 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white text-sm">
      Export PDF
    </a>
  </div>
</div>


  <!-- Stats row -->
  <div class="mt-8 grid grid-cols-2 md:grid-cols-4 gap-4">
    <?php
      $tiles = [
        ['label'=>'Total', 'value'=>$stats['total']],
        ['label'=>'High', 'value'=>$stats['high']],
        ['label'=>'Moderate', 'value'=>$stats['moderate']],
        ['label'=>'Low', 'value'=>$stats['low']],
      ];
      foreach($tiles as $t):
    ?>
      <div class="rounded-2xl bg-white border border-slate-200 p-5 shadow-sm">
        <div class="text-xs text-slate-500"><?= htmlspecialchars($t['label']) ?></div>
        <div class="mt-2 text-2xl font-semibold"><?= htmlspecialchars((string)$t['value']) ?></div>
      </div>
    <?php endforeach; ?>
  </div>

  <div class="mt-4 rounded-2xl bg-white border border-slate-200 p-5 shadow-sm">
    <div class="text-xs text-slate-500">Average risk probability</div>
    <div class="mt-2 text-xl font-semibold"><?= number_format($stats['avg_prob'] * 100, 1) ?>%</div>
  </div>

  <!-- Table -->
  <div class="mt-8 rounded-[2rem] bg-white border border-slate-200 shadow-sm overflow-hidden">
    <div class="p-6 border-b border-slate-200 flex items-center justify-between">
      <div>
        <div class="text-lg font-semibold">Recent Assessments</div>
        <div class="text-xs text-slate-500">Stored predictions for reporting and evaluation.</div>
      </div>
    </div>

    <div class="overflow-x-auto">
      <table class="min-w-full text-sm">
        <thead class="bg-slate-50 text-slate-600">
          <tr>
            <th class="text-left p-4">Date</th>
            <th class="text-left p-4">Age</th>
            <th class="text-left p-4">BP</th>
            <th class="text-left p-4">BS</th>
            <th class="text-left p-4">BMI</th>
            <th class="text-left p-4">Prob</th>
            <th class="text-left p-4">Tier</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach($items as $row): ?>
            <tr class="border-t border-slate-100">
              <td class="p-4 text-slate-500"><?= htmlspecialchars($row['created_at']) ?></td>
              <td class="p-4"><?= htmlspecialchars((string)$row['age']) ?></td>
              <td class="p-4"><?= htmlspecialchars($row['systolic_bp'] . "/" . $row['diastolic']) ?></td>
              <td class="p-4"><?= htmlspecialchars((string)$row['bs']) ?></td>
              <td class="p-4"><?= htmlspecialchars((string)$row['bmi']) ?></td>
              <td class="p-4"><?= number_format(((float)$row['risk_probability']) * 100, 1) ?>%</td>
              <td class="p-4">
                <?php
                  $tier = $row['risk_level'];
                  $pill = 'bg-slate-100 text-slate-700 border-slate-200';
                  if ($tier === 'High') $pill = 'bg-red-50 text-red-700 border-red-100';
                  if ($tier === 'Moderate') $pill = 'bg-amber-50 text-amber-800 border-amber-100';
                  if ($tier === 'Low') $pill = 'bg-emerald-50 text-emerald-700 border-emerald-100';
                ?>
                <span class="inline-flex px-3 py-1 rounded-full border <?= $pill ?> text-xs font-medium">
                  <?= htmlspecialchars($tier) ?>
                </span>
              </td>
            </tr>
          <?php endforeach; ?>

          <?php if (count($items) === 0): ?>
            <tr>
              <td colspan="7" class="p-8 text-center text-slate-500">No assessments yet. Run your first prediction.</td>
            </tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>

</main>

<?php
$content = ob_get_clean();
require __DIR__ . '/layout.php';
