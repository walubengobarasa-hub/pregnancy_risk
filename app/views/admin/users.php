<?php $title = 'Admin — User Management'; ob_start(); ?>
<main class="max-w-7xl mx-auto px-4 py-12 space-y-8">
  <div class="flex items-end justify-between gap-4 flex-wrap">
    <div>
      <h1 class="text-3xl font-semibold">User & Role Management</h1>
      <p class="mt-2 text-sm text-slate-600">Create admins, clinicians and users, then change roles whenever needed.</p>
    </div>
    <a href="<?= base_url('dashboard') ?>" class="px-4 py-2 rounded-xl border bg-white hover:bg-slate-50 text-sm">Back to dashboard</a>
  </div>

  <section class="rounded-[2rem] bg-white border border-slate-200 shadow-sm p-6">
    <h2 class="text-xl font-semibold">Add new user</h2>
    <form method="post" action="<?= base_url('admin/users/store') ?>" class="mt-5 grid md:grid-cols-2 gap-4">
      <input name="full_name" required placeholder="Full name" class="rounded-xl border border-slate-200 px-4 py-3 text-sm">
      <input name="email" type="email" required placeholder="Email address" class="rounded-xl border border-slate-200 px-4 py-3 text-sm">
      <input name="password" type="password" required placeholder="Password (min 8 chars)" class="rounded-xl border border-slate-200 px-4 py-3 text-sm">
      <select name="role" class="rounded-xl border border-slate-200 px-4 py-3 text-sm">
        <option value="user">User</option>
        <option value="clinician">Clinician</option>
        <option value="admin">Admin</option>
      </select>
      <div class="md:col-span-2 flex items-center justify-between gap-4">
        <label class="text-sm text-slate-600 flex items-center gap-2"><input type="checkbox" checked name="is_active"> Active account</label>
        <button class="px-5 py-3 rounded-2xl bg-indigo-600 hover:bg-indigo-700 text-white text-sm">Create user</button>
      </div>
    </form>
  </section>

  <section class="rounded-[2rem] bg-white border border-slate-200 shadow-sm overflow-hidden">
    <div class="p-6 border-b border-slate-200">
      <h2 class="text-xl font-semibold">Existing users</h2>
    </div>
    <div class="divide-y divide-slate-100">
      <?php foreach ($users as $row): ?>
        <?php $isSelf = (int)$row['id'] === (int)Auth::id(); ?>
        <div class="p-6 space-y-4">
          <form method="post" action="<?= base_url('admin/users/update') ?>" class="grid lg:grid-cols-6 gap-4 items-end">
            <input type="hidden" name="id" value="<?= (int)$row['id'] ?>">
            <label class="block lg:col-span-2"><div class="text-xs text-slate-500 mb-1">Full name</div><input name="full_name" value="<?= htmlspecialchars($row['full_name']) ?>" class="w-full rounded-xl border border-slate-200 px-4 py-3 text-sm"></label>
            <label class="block lg:col-span-2"><div class="text-xs text-slate-500 mb-1">Email</div><input type="email" name="email" value="<?= htmlspecialchars($row['email']) ?>" class="w-full rounded-xl border border-slate-200 px-4 py-3 text-sm"></label>
            <label class="block"><div class="text-xs text-slate-500 mb-1">Role</div><select name="role" class="w-full rounded-xl border border-slate-200 px-4 py-3 text-sm"><?php foreach (['user','clinician','admin'] as $role): ?><option value="<?= $role ?>" <?= $row['role'] === $role ? 'selected' : '' ?>><?= ucfirst($role) ?></option><?php endforeach; ?></select></label>
            <label class="block"><div class="text-xs text-slate-500 mb-1">Reset password</div><input type="password" name="password" placeholder="Leave blank" class="w-full rounded-xl border border-slate-200 px-4 py-3 text-sm"></label>
            <div class="lg:col-span-6 flex items-center justify-between gap-4 flex-wrap">
              <label class="text-sm text-slate-600 flex items-center gap-2"><input type="checkbox" name="is_active" <?= (int)$row['is_active'] === 1 ? 'checked' : '' ?>> Active</label>
              <button class="px-4 py-2 rounded-xl bg-slate-900 hover:bg-slate-800 text-white text-sm">Save changes</button>
            </div>
          </form>

          <form method="post" action="<?= base_url('admin/users/delete') ?>" onsubmit="return confirm('Delete this user?');" class="flex justify-end">
            <input type="hidden" name="id" value="<?= (int)$row['id'] ?>">
            <button class="px-4 py-2 rounded-xl border text-sm <?= $isSelf ? 'border-slate-200 text-slate-400 cursor-not-allowed' : 'border-red-200 text-red-700 hover:bg-red-50' ?>" <?= $isSelf ? 'disabled title="You cannot delete your own account"' : '' ?>>Delete</button>
          </form>
        </div>
      <?php endforeach; ?>
    </div>
  </section>
</main>
<?php $content = ob_get_clean(); require __DIR__ . '/../layout.php'; ?>
