<?php
$title = "MaternalCare AI — Dashboard";
$user = Auth::user();
$isStaff = in_array($user['role'] ?? '', ['admin','clinician'], true);
ob_start();
?>

<main class="max-w-7xl mx-auto px-4 py-12">
  <div class="flex items-end justify-between flex-wrap gap-4">
    <div>
      <h1 class="text-3xl font-semibold"><?= $isStaff ? 'Clinical Dashboard' : 'My Assessments' ?></h1>
      <p class="mt-2 text-slate-600 text-sm">
        <?= $isStaff ? 'Latest assessments and summary statistics.' : 'Only your own assessments are visible here.' ?>
      </p>
    </div>

    <div class="flex items-center gap-2 flex-wrap">
      <a href="/" class="px-4 py-2 rounded-xl bg-white border border-slate-200 hover:bg-slate-50 text-sm">New assessment →</a>
      <a href="<?= base_url('export/csv') ?>" class="px-4 py-2 rounded-xl border bg-white hover:bg-slate-50 text-sm">Export CSV</a>
      <a href="<?= base_url('export/pdf') ?>" class="px-4 py-2 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white text-sm">Export PDF</a>
      <?php if (($user['role'] ?? '') === 'admin'): ?>
        <a href="<?= base_url('admin/users') ?>" class="px-4 py-2 rounded-xl bg-slate-900 hover:bg-slate-800 text-white text-sm">Manage Users</a>
      <?php endif; ?>
    </div>
  </div>

  <div class="mt-8 grid grid-cols-2 md:grid-cols-4 gap-4">
    <?php foreach ([['Total',$stats['total']],['High',$stats['high']],['Moderate',$stats['moderate']],['Low',$stats['low']]] as $t): ?>
      <div class="rounded-2xl bg-white border border-slate-200 p-5 shadow-sm">
        <div class="text-xs text-slate-500"><?= htmlspecialchars($t[0]) ?></div>
        <div class="mt-2 text-2xl font-semibold"><?= htmlspecialchars((string)$t[1]) ?></div>
      </div>
    <?php endforeach; ?>
  </div>

  <div class="mt-4 rounded-2xl bg-white border border-slate-200 p-5 shadow-sm">
    <div class="text-xs text-slate-500">Average risk probability</div>
    <div class="mt-2 text-xl font-semibold"><?= number_format($stats['avg_prob'] * 100, 1) ?>%</div>
  </div>

  <div class="mt-8 rounded-[2rem] bg-white border border-slate-200 shadow-sm overflow-hidden">
    <div class="p-6 border-b border-slate-200 flex items-center justify-between">
      <div>
        <div class="text-lg font-semibold"><?= $isStaff ? 'Recent Assessments' : 'Assessment History' ?></div>
        <div class="text-xs text-slate-500"><?= $isStaff ? 'Stored predictions for reporting and evaluation.' : 'Your saved predictions and game access.' ?></div>
      </div>
    </div>

    <div class="overflow-x-auto">
      <table class="min-w-full text-sm">
        <thead class="bg-slate-50 text-slate-600">
          <tr>
            <th class="text-left p-4">Date</th>
            <?php if ($isStaff): ?>
              <th class="text-left p-4">Patient</th>
            <?php endif; ?>
            <th class="text-left p-4">Age</th>
            <th class="text-left p-4">BP</th>
            <th class="text-left p-4">BS</th>
            <th class="text-left p-4">BMI</th>
            <th class="text-left p-4">Prob</th>
            <th class="text-left p-4">Tier</th>
            <th class="text-left p-4">Game</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach($items as $row): ?>
            <tr class="border-t border-slate-100">
              <td class="p-4 text-slate-500"><?= htmlspecialchars($row['created_at']) ?></td>
              <?php if ($isStaff): ?>
                <td class="p-4">
                  <div class="font-medium text-slate-800"><?= htmlspecialchars($row['full_name'] ?? 'Anonymous') ?></div>
                  <div class="text-xs text-slate-500"><?= htmlspecialchars($row['email'] ?? '') ?></div>
                </td>
              <?php endif; ?>
              <td class="p-4"><?= htmlspecialchars((string)$row['age']) ?></td>
              <td class="p-4"><?= htmlspecialchars($row['systolic_bp'] . '/' . $row['diastolic']) ?></td>
              <td class="p-4"><?= htmlspecialchars((string)$row['bs']) ?></td>
              <td class="p-4"><?= htmlspecialchars((string)$row['bmi']) ?></td>
              <td class="p-4"><?= number_format(((float)$row['risk_probability']) * 100, 1) ?>%</td>
              <td class="p-4">
                <?php $tier = $row['risk_level']; $pill='bg-slate-100 text-slate-700 border-slate-200'; if($tier==='High') $pill='bg-red-50 text-red-700 border-red-100'; if($tier==='Moderate') $pill='bg-amber-50 text-amber-800 border-amber-100'; if($tier==='Low') $pill='bg-emerald-50 text-emerald-700 border-emerald-100'; ?>
                <span class="inline-flex px-3 py-1 rounded-full border <?= $pill ?> text-xs font-medium"><?= htmlspecialchars($tier) ?></span>
              </td>
              <td class="p-4">
                <a href="<?= base_url('game?assessment_id=' . (int)$row['id']) ?>" class="inline-flex px-3 py-2 rounded-xl border border-slate-200 bg-white hover:bg-slate-50 text-xs">Play game</a>
              </td>
            </tr>
          <?php endforeach; ?>
          <?php if (count($items) === 0): ?>
            <tr><td colspan="<?= $isStaff ? '9' : '8' ?>" class="p-8 text-center text-slate-500">No assessments yet. Run your first prediction.</td></tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</main>

<?php $content = ob_get_clean(); require __DIR__ . '/layout.php'; ?>
