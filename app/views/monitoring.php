<?php
$title = "Model Monitoring — MaternalCare AI";
ob_start();
?>

<main class="max-w-7xl mx-auto px-4 py-12">
  <div class="flex items-end justify-between flex-wrap gap-4">
    <div>
      <h1 class="text-3xl font-semibold">Model Monitoring</h1>
      <p class="mt-2 text-sm text-slate-600">
        Simple drift indicators using monthly averages (inputs + predicted probability).
      </p>
    </div>
    <a href="<?= base_url('dashboard') ?>" class="px-4 py-2 rounded-xl border bg-white hover:bg-slate-50 text-sm">Back to Dashboard</a>
  </div>

  <div class="mt-8 rounded-[2rem] bg-white border border-slate-200 shadow-sm overflow-hidden">
    <div class="p-6 border-b border-slate-200">
      <div class="text-lg font-semibold">Monthly Summary</div>
      <div class="text-xs text-slate-500">Use this to detect unusual shifts over time.</div>
    </div>

    <div class="overflow-x-auto">
      <table class="min-w-full text-sm">
        <thead class="bg-slate-50 text-slate-600">
          <tr>
            <th class="text-left p-4">Month</th>
            <th class="text-left p-4">N</th>
            <th class="text-left p-4">Avg Age</th>
            <th class="text-left p-4">Avg BP</th>
            <th class="text-left p-4">Avg BS</th>
            <th class="text-left p-4">Avg BMI</th>
            <th class="text-left p-4">Avg Prob</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($rows as $r): ?>
            <tr class="border-t border-slate-100">
              <td class="p-4 text-slate-600"><?= htmlspecialchars($r['ym']) ?></td>
              <td class="p-4"><?= (int)$r['n'] ?></td>
              <td class="p-4"><?= number_format((float)$r['avg_age'], 1) ?></td>
              <td class="p-4"><?= number_format((float)$r['avg_sys'], 1) ?>/<?= number_format((float)$r['avg_dia'], 1) ?></td>
              <td class="p-4"><?= number_format((float)$r['avg_bs'], 2) ?></td>
              <td class="p-4"><?= number_format((float)$r['avg_bmi'], 2) ?></td>
              <td class="p-4"><?= number_format(((float)$r['avg_prob'])*100, 1) ?>%</td>
            </tr>
          <?php endforeach; ?>

          <?php if (count($rows) === 0): ?>
            <tr><td colspan="7" class="p-8 text-center text-slate-500">No data yet.</td></tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</main>

<?php
$content = ob_get_clean();
require __DIR__ . '/layout.php';
