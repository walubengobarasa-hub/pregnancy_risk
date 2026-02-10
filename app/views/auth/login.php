<?php
$title = "Login — MaternalCare AI";
ob_start();
?>

<main class="max-w-6xl mx-auto px-4 py-14">
  <div class="max-w-md mx-auto bg-white border border-slate-200 rounded-[2rem] shadow-sm p-7">
    <h1 class="text-2xl font-semibold">Sign in</h1>
    <p class="mt-2 text-sm text-slate-600">Clinicians and admins can access dashboards and exports.</p>

    <form id="loginForm" class="mt-6 space-y-4">
      <label class="block">
        <span class="text-xs text-slate-600">Email</span>
        <input name="email" type="email" required
          class="mt-1 w-full rounded-xl border border-slate-200 px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-200 outline-none" />
      </label>

      <label class="block">
        <span class="text-xs text-slate-600">Password</span>
        <input name="password" type="password" required
          class="mt-1 w-full rounded-xl border border-slate-200 px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-200 outline-none" />
      </label>

      <button class="w-full px-5 py-3 rounded-2xl bg-indigo-600 hover:bg-indigo-700 text-white shadow">
        Sign in
      </button>

      <div id="loginError" class="hidden text-sm text-red-700 bg-red-50 border border-red-100 rounded-xl p-3"></div>

      <div class="text-sm text-slate-600">
        Don’t have an account?
        <a class="text-indigo-700 hover:text-indigo-900 font-medium" href="<?= base_url('register') ?>">Create one</a>
      </div>
    </form>
  </div>
</main>

<script>
document.getElementById('loginForm').addEventListener('submit', async (e) => {
  e.preventDefault();
  const fd = new FormData(e.target);

  const res = await fetch("<?= base_url('api/login') ?>", {
    method: "POST",
    body: fd
  });
  const data = await res.json();
  if (!res.ok) {
    const box = document.getElementById('loginError');
    box.textContent = data.error || "Login failed";
    box.classList.remove('hidden');
    return;
  }
  window.location.href = data.redirect || "<?= base_url('dashboard') ?>";
});
</script>

<?php
$content = ob_get_clean();
require __DIR__ . '/../layout.php';
