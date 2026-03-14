<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= htmlspecialchars($title ?? 'Maternal Risk Predictor') ?></title>
  <script src="https://cdn.tailwindcss.com"></script>
</head>
<?php
  $user = class_exists('Auth') ? Auth::user() : null;
  $fullName = $user['full_name'] ?? null;
  $role = $user['role'] ?? null;
  $canSeeClinicianNav = $user && in_array($role, ['clinician','admin'], true);
  $isAdmin = $role === 'admin';
?>
<body class="min-h-screen bg-gradient-to-br from-indigo-50 via-white to-purple-100 text-slate-900">
  <header class="sticky top-0 z-50 backdrop-blur bg-white/70 border-b border-slate-200">
    <div class="max-w-7xl mx-auto px-4 py-3 flex items-center justify-between gap-4">
      <a href="<?= base_url() ?>" class="flex items-center gap-2">
        <div class="w-9 h-9 rounded-xl bg-indigo-600 text-white grid place-items-center font-bold">MH</div>
        <div class="leading-tight">
          <div class="font-semibold">MaternalCare AI</div>
          <div class="text-xs text-slate-500">Risk Assessment</div>
        </div>
      </a>

      <nav class="hidden md:flex items-center gap-5 text-sm text-slate-600">
        <a class="hover:text-slate-900" href="<?= base_url() ?>">Home</a>
        <?php if ($user): ?>
          <a class="hover:text-slate-900" href="<?= base_url('dashboard') ?>">My Assessments</a>
        <?php endif; ?>
        <?php if ($canSeeClinicianNav): ?>
          <a class="hover:text-slate-900" href="<?= base_url('monitoring') ?>">Monitoring</a>
        <?php endif; ?>
        <?php if ($isAdmin): ?>
          <a class="hover:text-slate-900" href="<?= base_url('admin/users') ?>">User Management</a>
        <?php endif; ?>
      </nav>

      <div class="flex items-center gap-2">
        <?php if ($user): ?>
          <div class="hidden sm:block px-3 py-2 rounded-xl bg-white border border-slate-200">
            <div class="text-sm font-semibold text-slate-900">Welcome, <?= htmlspecialchars($fullName) ?></div>
            <div class="text-[11px] text-slate-500 uppercase tracking-wide"><?= htmlspecialchars($role) ?></div>
          </div>
          <a href="<?= base_url('logout') ?>" class="inline-flex px-4 py-2 rounded-xl bg-slate-900 hover:bg-slate-800 text-white text-sm shadow">Logout</a>
        <?php else: ?>
          <a href="<?= base_url('login') ?>" class="hidden sm:inline-flex px-4 py-2 rounded-xl border border-slate-200 bg-white hover:bg-slate-50 text-sm">Sign in</a>
          <a href="<?= base_url('register') ?>" class="hidden sm:inline-flex px-4 py-2 rounded-xl border border-slate-200 bg-white hover:bg-slate-50 text-sm">Sign up</a>
          <a href="<?= base_url('#assess') ?>" class="px-4 py-2 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white text-sm shadow">Assess risk</a>
        <?php endif; ?>
      </div>
    </div>
  </header>

  <?= $content ?>

  <footer class="border-t border-slate-200 mt-16">
    <div class="max-w-7xl mx-auto px-4 py-10 text-sm text-slate-500 flex flex-col md:flex-row items-start md:items-center justify-between gap-4">
      <div>© <?= date('Y') ?> MaternalCare AI. All rights reserved.</div>
      <div class="text-xs">For academic decision support; not a substitute for clinical judgment.</div>
    </div>
  </footer>

  <script>window.APP_BASE = "<?= htmlspecialchars(base_url()) ?>";</script>
  <script src="<?= htmlspecialchars(base_url('assets/js/app.js')) ?>"></script>
</body>
</html>
