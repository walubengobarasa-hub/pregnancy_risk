<?php
$title = "Symptom Checker — MaternalCare AI";
ob_start();
?>

<main class="max-w-3xl mx-auto px-4 py-12">
  <div class="flex items-end justify-between gap-3 flex-wrap">
    <div>
      <h1 class="text-3xl font-semibold">Symptom checker</h1>
      <p class="mt-2 text-sm text-slate-600">
        Log symptoms to trigger early warning alerts. This is educational support, not a diagnosis.
      </p>
    </div>
    <a href="<?= base_url() ?>" class="px-4 py-2 rounded-xl border bg-white hover:bg-slate-50 text-sm">Back</a>
  </div>

  <div class="mt-8 rounded-[2rem] bg-white border border-slate-200 shadow-sm p-7">
    <div class="text-sm text-slate-500">Optional</div>
    <div class="text-lg font-semibold">Link to an assessment ID</div>
    <input id="assessment_id" type="number" class="mt-2 w-full rounded-xl border border-slate-200 px-3 py-2 text-sm" placeholder="e.g. 12">

    <div class="mt-6 grid sm:grid-cols-2 gap-3">
      <?php
        $symptoms = [
          ['headache','Headache'],
          ['vision_changes','Vision changes'],
          ['swelling_face_hands','Swelling (face/hands)'],
          ['upper_abdominal_pain','Upper abdominal pain'],
          ['shortness_of_breath','Shortness of breath'],
          ['severe_weakness','Severe weakness'],
        ];
        foreach ($symptoms as $s):
      ?>
        <label class="flex items-center gap-2 rounded-xl border border-slate-200 bg-slate-50 p-3 text-sm">
          <input id="<?= h($s[0]) ?>" type="checkbox" class="rounded border-slate-300">
          <span><?= h($s[1]) ?></span>
        </label>
      <?php endforeach; ?>
    </div>

    <div class="mt-6 grid sm:grid-cols-2 gap-4">
      <label class="block">
        <div class="text-xs text-slate-600">BP systolic (optional)</div>
        <input id="bp_systolic" type="number" step="any" class="mt-1 w-full rounded-xl border border-slate-200 px-3 py-2 text-sm">
      </label>
      <label class="block">
        <div class="text-xs text-slate-600">BP diastolic (optional)</div>
        <input id="bp_diastolic" type="number" step="any" class="mt-1 w-full rounded-xl border border-slate-200 px-3 py-2 text-sm">
      </label>
      <label class="block sm:col-span-2">
        <div class="text-xs text-slate-600">Notes (optional)</div>
        <input id="notes" type="text" maxlength="500" class="mt-1 w-full rounded-xl border border-slate-200 px-3 py-2 text-sm">
      </label>
    </div>

    <div class="mt-6 flex items-center gap-3">
      <button id="submit_symptoms" class="px-5 py-3 rounded-2xl bg-indigo-600 hover:bg-indigo-700 text-white text-sm">
        Submit
      </button>
      <span id="status" class="text-sm text-slate-600"></span>
    </div>

    <div id="alertBox" class="mt-5"></div>

    <div class="mt-6 text-xs text-slate-500">
      If you feel very unwell or symptoms worsen, seek professional care urgently.
    </div>
  </div>
</main>

<script>
(function(){
  const BASE = (window.APP_BASE || "").replace(/\/$/, "");
  const SYMPTOMS_URL = `${BASE}/api/symptoms`;

  const byId = (id) => document.getElementById(id);
  const submit = byId("submit_symptoms");
  const status = byId("status");
  const alertBox = byId("alertBox");

  function pill(severity) {
    if (severity === "urgent") return "bg-red-50 text-red-700 border-red-100";
    if (severity === "warning") return "bg-amber-50 text-amber-800 border-amber-100";
    return "bg-slate-50 text-slate-700 border-slate-200";
  }

  submit.addEventListener("click", async () => {
    const body = {
      assessment_id: byId("assessment_id").value ? Number(byId("assessment_id").value) : null,
      headache: byId("headache").checked,
      vision_changes: byId("vision_changes").checked,
      swelling_face_hands: byId("swelling_face_hands").checked,
      upper_abdominal_pain: byId("upper_abdominal_pain").checked,
      shortness_of_breath: byId("shortness_of_breath").checked,
      severe_weakness: byId("severe_weakness").checked,
      bp_systolic: byId("bp_systolic").value ? Number(byId("bp_systolic").value) : null,
      bp_diastolic: byId("bp_diastolic").value ? Number(byId("bp_diastolic").value) : null,
      notes: byId("notes").value ? byId("notes").value.trim() : null
    };

    try {
      submit.disabled = true;
      status.textContent = "Submitting...";
      const res = await fetch(SYMPTOMS_URL, {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify(body)
      });
      const data = await res.json();
      if (!res.ok) throw new Error(data.error || "Failed");

      const sev = data.evaluation?.severity || "info";
      alertBox.innerHTML = `
        <div class="rounded-2xl border ${pill(sev)} p-4">
          <div class="font-semibold">${data.evaluation?.title || "Update"}</div>
          <div class="mt-2 text-sm">${data.evaluation?.message || ""}</div>
        </div>
      `;
      status.textContent = "Saved.";
    } catch (e) {
      status.textContent = e.message || "Error";
    } finally {
      submit.disabled = false;
    }
  });
})();
</script>

<?php
$content = ob_get_clean();
require __DIR__ . '/layout.php';
