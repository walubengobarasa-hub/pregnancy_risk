(function () {
  const form = document.getElementById("riskForm");
  if (!form) return;

  const submitBtn = document.getElementById("submitBtn");
  const btnText = document.getElementById("btnText");
  const btnSpinner = document.getElementById("btnSpinner");
  const formError = document.getElementById("formError");
  const formResult = document.getElementById("formResult");
  const heroResult = document.getElementById("heroResult");

  // IMPORTANT:
  // Put this in your layout/home view as a global:
  // <script>window.APP_BASE = "<?= base_url() ?>";</script>
  const BASE = (window.APP_BASE || "").replace(/\/$/, "");
  const PREDICT_URL = `${BASE}/api/predict`;

  const required = [
    "Age","Systolic BP","Diastolic","BS","Body Temp","BMI",
    "Previous Complications","Preexisting Diabetes","Gestational Diabetes","Mental Health","Heart Rate"
  ];

  function setLoading(isLoading) {
    if (!submitBtn) return;
    submitBtn.disabled = isLoading;
    if (btnText) btnText.textContent = isLoading ? "Assessing..." : "Assess Risk";
    if (btnSpinner) btnSpinner.classList.toggle("hidden", !isLoading);
  }

  function showError(msg) {
    if (!formError) return;
    formError.textContent = msg;
    formError.classList.remove("hidden");
  }

  function clearError() {
    if (!formError) return;
    formError.classList.add("hidden");
    formError.textContent = "";
  }

  function renderContributors(list) {
    if (!Array.isArray(list) || list.length === 0) return "";

    return `
      <div class="mt-4 text-sm">
        <div class="font-semibold text-slate-800">Top contributing factors</div>
        <div class="mt-2 rounded-xl border border-slate-200 bg-white p-3">
          <ul class="space-y-2">
            ${list.map(x => `
              <li class="flex items-center justify-between gap-4">
                <span class="text-slate-700">${x.feature}</span>
                <span class="font-mono text-xs text-slate-500">${Number(x.shap_value).toFixed(4)}</span>
              </li>
            `).join("")}
          </ul>
        </div>
      </div>
    `;
  }

  function renderResult(data) {
    const prob = (data.probability_high_risk * 100).toFixed(1) + "%";
    const tier = data.risk_tier;

    let box = "border-slate-200 bg-slate-50 text-slate-900";
    if (tier === "High") box = "border-red-100 bg-red-50 text-red-900";
    if (tier === "Moderate") box = "border-amber-100 bg-amber-50 text-amber-900";
    if (tier === "Low") box = "border-emerald-100 bg-emerald-50 text-emerald-900";

    const extra = renderContributors(data.top_contributors);

    const html = `
      <div class="rounded-2xl border ${box} p-5">
        <div class="text-sm opacity-80">Prediction result</div>
        <div class="mt-2 text-2xl font-semibold">${tier} Risk</div>
        <div class="mt-1 text-sm">Probability: <span class="font-semibold">${prob}</span></div>
        <div class="mt-3 text-xs opacity-70">Saved to dashboard (Assessment ID: ${data.id})</div>
        ${extra}
      </div>
    `;

    if (formResult) {
      formResult.innerHTML = html;
      formResult.classList.remove("hidden");
    }
    if (heroResult) heroResult.innerHTML = html;
  }

  form.addEventListener("submit", async (e) => {
    e.preventDefault();
    clearError();

    const fd = new FormData(form);
    const payload = Object.fromEntries(fd.entries());

    // Validate required
    for (const k of required) {
      if (!(k in payload) || payload[k] === "" || payload[k] == null) {
        showError(`Please fill: ${k}`);
        return;
      }
    }

    // Explain toggle (add checkbox in form: name="explain" value="1")
    const explain = payload.explain === "1" || payload.explain === "on";
    delete payload.explain;

    // Add internal flags for PHP controller
    payload.__explain = explain ? 1 : 0;
    payload.__top_k = 6;

    setLoading(true);

    try {
      const res = await fetch(PREDICT_URL, {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify(payload)
      });

      const data = await res.json();

      if (!res.ok) {
        throw new Error(data.error || data.detail || "Prediction failed");
      }

      renderResult(data);
    } catch (err) {
      showError(err.message || "Something went wrong");
    } finally {
      setLoading(false);
    }
  });
})();
