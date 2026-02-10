(function () {
  const form = document.getElementById("riskForm");
  if (!form) return;

  const submitBtn = document.getElementById("submitBtn");
  const btnText = document.getElementById("btnText");
  const btnSpinner = document.getElementById("btnSpinner");
  const formError = document.getElementById("formError");
  const formResult = document.getElementById("formResult");
  const heroResult = document.getElementById("heroResult");

  const personalizedPanel = document.getElementById("personalizedPanel");

  const BASE = (window.APP_BASE || "").replace(/\/$/, "");
  const PREDICT_URL = `${BASE}/api/predict`;
  const INTERACT_URL = `${BASE}/api/interactions`;
  const OUTCOMES_URL = `${BASE}/api/outcomes`;
  const SYMPTOMS_URL = `${BASE}/api/symptoms`;
  const ACK_ALERT_URL = `${BASE}/api/alerts/ack`;

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

  async function logEvent(event_type, assessment_id, meta) {
    try {
      await fetch(INTERACT_URL, {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({ event_type, assessment_id, meta: meta || {} })
      });
    } catch (_) {}
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
        <div class="mt-3 text-xs opacity-70">Saved (Assessment ID: ${data.id})</div>
        ${extra}
      </div>
    `;

    if (formResult) {
      formResult.innerHTML = html;
      formResult.classList.remove("hidden");
    }
    if (heroResult) heroResult.innerHTML = html;
  }

  function pill(severity) {
    if (severity === "urgent") return "bg-red-50 text-red-700 border-red-100";
    if (severity === "warning") return "bg-amber-50 text-amber-800 border-amber-100";
    return "bg-slate-50 text-slate-700 border-slate-200";
  }

  function renderBundle(assessmentId, bundle) {
    if (!personalizedPanel) return;
    if (!bundle) {
      personalizedPanel.innerHTML = "";
      return;
    }

    const alert = bundle.alert;
    const educationGroups = bundle.education || {};
    const actions = bundle.recommended_actions || [];
    const disclaimer = bundle.disclaimer || "";

    const educationHtml = Object.keys(educationGroups).map((type) => {
      const items = educationGroups[type] || [];
      return `
        <div class="rounded-2xl border border-slate-200 bg-white p-5">
          <div class="text-sm font-semibold text-slate-900">${type.replace(/_/g, " ").toUpperCase()}</div>
          <div class="mt-3 space-y-3">
            ${items.map(it => `
              <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
                <div class="font-semibold text-slate-900">${it.title}</div>
                <div class="mt-2 text-sm text-slate-600 leading-relaxed">${it.body}</div>
              </div>
            `).join("")}
          </div>
        </div>
      `;
    }).join("");

    const actionsHtml = actions.map((a) => `
      <li class="rounded-xl border border-slate-200 bg-white p-4">
        <div class="font-semibold text-slate-900">${a.title}</div>
        <div class="mt-1 text-sm text-slate-600">${a.detail}</div>
        <button data-action="${a.title}" class="mt-3 text-xs px-3 py-2 rounded-xl border border-slate-200 hover:bg-slate-50">
          Mark as helpful
        </button>
      </li>
    `).join("");

    const alertHtml = alert ? `
      <div class="rounded-2xl border ${pill(alert.severity)} p-5">
        <div class="flex items-center justify-between gap-3">
          <div>
            <div class="text-xs opacity-80">${alert.severity.toUpperCase()} ALERT</div>
            <div class="text-lg font-semibold mt-1">${alert.title}</div>
          </div>
          ${bundle.alert_id ? `<button data-ack="${bundle.alert_id}" class="text-xs px-3 py-2 rounded-xl border border-slate-200 bg-white hover:bg-slate-50">
            Acknowledge
          </button>` : ""}
        </div>
        <div class="mt-3 text-sm leading-relaxed">${alert.message}</div>
      </div>
    ` : "";

    // Outcomes quick form (impact outcomes)
    const outcomesHtml = `
      <div class="rounded-2xl border border-slate-200 bg-white p-5">
        <div class="text-lg font-semibold">Quick impact check (1 minute)</div>
        <div class="text-sm text-slate-600 mt-1">This helps improve content effectiveness and model feedback.</div>

        <div class="mt-4 grid sm:grid-cols-2 gap-4">
          <label class="block">
            <div class="text-xs text-slate-600">Knowledge score (0–5)</div>
            <input id="knowledge_score" type="number" min="0" max="5"
              class="mt-1 w-full rounded-xl border border-slate-200 px-3 py-2 text-sm">
          </label>

          <label class="block">
            <div class="text-xs text-slate-600">Warning sign recognition (1–5)</div>
            <input id="warning_sign_recognition" type="number" min="1" max="5"
              class="mt-1 w-full rounded-xl border border-slate-200 px-3 py-2 text-sm">
          </label>

          <label class="block">
            <div class="text-xs text-slate-600">Self-efficacy (1–5)</div>
            <input id="self_efficacy" type="number" min="1" max="5"
              class="mt-1 w-full rounded-xl border border-slate-200 px-3 py-2 text-sm">
          </label>

          <label class="block">
            <div class="text-xs text-slate-600">Care-seeking intention (1–5)</div>
            <input id="care_seeking_intention" type="number" min="1" max="5"
              class="mt-1 w-full rounded-xl border border-slate-200 px-3 py-2 text-sm">
          </label>

          <label class="block sm:col-span-2">
            <div class="text-xs text-slate-600">Notes (optional)</div>
            <input id="outcome_notes" type="text" maxlength="500"
              class="mt-1 w-full rounded-xl border border-slate-200 px-3 py-2 text-sm"
              placeholder="Anything you found confusing or helpful?">
          </label>
        </div>

        <div class="mt-4 flex items-center gap-3">
          <button id="submit_outcomes" class="px-5 py-3 rounded-2xl bg-slate-900 hover:bg-slate-800 text-white text-sm">
            Submit outcomes
          </button>
          <a href="${BASE}/symptoms" class="text-sm text-indigo-700 hover:text-indigo-900 font-medium">
            Symptom checker →
          </a>
          <span id="outcomes_status" class="text-sm text-slate-600"></span>
        </div>

        <div class="mt-4 text-xs text-slate-500">${disclaimer}</div>
      </div>
    `;

    const html = `
      <section class="mt-10 space-y-6">
        <div class="flex items-end justify-between flex-wrap gap-3">
          <div>
            <div class="text-sm text-slate-500">Output stage</div>
            <div class="text-2xl font-semibold">Personalized interventions</div>
          </div>
          <button id="open_symptoms_inline" class="text-xs px-3 py-2 rounded-xl border border-slate-200 bg-white hover:bg-slate-50">
            Log symptoms
          </button>
        </div>

        ${alertHtml}

        <div class="grid lg:grid-cols-2 gap-6">
          ${educationHtml || `<div class="rounded-2xl border border-slate-200 bg-white p-5">No education content found.</div>`}
          <div class="rounded-2xl border border-slate-200 bg-white p-5">
            <div class="text-lg font-semibold">Recommended actions</div>
            <ul class="mt-4 space-y-3">${actionsHtml}</ul>
          </div>
        </div>

        ${outcomesHtml}

        <div id="inlineSymptoms" class="hidden rounded-2xl border border-slate-200 bg-white p-5">
          <div class="text-lg font-semibold">Symptom check-in</div>
          <div class="text-sm text-slate-600 mt-1">Log symptoms to trigger early warning alerts.</div>

          <div class="mt-4 grid sm:grid-cols-2 gap-3">
            ${checkbox("headache","Headache")}
            ${checkbox("vision_changes","Vision changes")}
            ${checkbox("swelling_face_hands","Swelling (face/hands)")}
            ${checkbox("upper_abdominal_pain","Upper abdominal pain")}
            ${checkbox("shortness_of_breath","Shortness of breath")}
            ${checkbox("severe_weakness","Severe weakness")}
          </div>

          <div class="mt-4 grid sm:grid-cols-2 gap-4">
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
              <input id="sym_notes" type="text" maxlength="500" class="mt-1 w-full rounded-xl border border-slate-200 px-3 py-2 text-sm">
            </label>
          </div>

          <div class="mt-4 flex items-center gap-3">
            <button id="submit_symptoms" class="px-5 py-3 rounded-2xl bg-indigo-600 hover:bg-indigo-700 text-white text-sm">
              Submit symptom report
            </button>
            <span id="symptoms_status" class="text-sm text-slate-600"></span>
          </div>

          <div id="symptoms_alert" class="mt-4"></div>
        </div>
      </section>
    `;

    personalizedPanel.innerHTML = html;

    // Action click logging
    personalizedPanel.querySelectorAll("button[data-action]").forEach(btn => {
      btn.addEventListener("click", async () => {
        const name = btn.getAttribute("data-action");
        btn.textContent = "Saved ✓";
        btn.disabled = true;
        await logEvent("action_marked_helpful", assessmentId, { action: name });
      });
    });

    // Inline symptom box toggles
    const openBtn = personalizedPanel.querySelector("#open_symptoms_inline");
    const box = personalizedPanel.querySelector("#inlineSymptoms");
    if (openBtn && box) {
      openBtn.addEventListener("click", () => box.classList.toggle("hidden"));
    }

    // Outcomes submit
    const submitOut = personalizedPanel.querySelector("#submit_outcomes");
    const statusOut = personalizedPanel.querySelector("#outcomes_status");
    if (submitOut) {
      submitOut.addEventListener("click", async () => {
        const body = {
          assessment_id: assessmentId,
          knowledge_score: val("#knowledge_score"),
          warning_sign_recognition: val("#warning_sign_recognition"),
          self_efficacy: val("#self_efficacy"),
          care_seeking_intention: val("#care_seeking_intention"),
          notes: str("#outcome_notes"),
        };

        try {
          submitOut.disabled = true;
          if (statusOut) statusOut.textContent = "Submitting...";
          const res = await fetch(OUTCOMES_URL, {
            method: "POST",
            headers: { "Content-Type": "application/json" },
            body: JSON.stringify(body)
          });
          const data = await res.json();
          if (!res.ok) throw new Error(data.error || "Failed to submit outcomes");
          if (statusOut) statusOut.textContent = "Thanks — saved.";
          await logEvent("outcomes_ui_submitted", assessmentId, { impact_id: data.id });
        } catch (e) {
          if (statusOut) statusOut.textContent = e.message || "Error";
        } finally {
          submitOut.disabled = false;
        }
      });
    }

    // Symptoms submit
    const submitSym = personalizedPanel.querySelector("#submit_symptoms");
    const statusSym = personalizedPanel.querySelector("#symptoms_status");
    const symAlert = personalizedPanel.querySelector("#symptoms_alert");

    if (submitSym) {
      submitSym.addEventListener("click", async () => {
        const body = {
          assessment_id: assessmentId,
          headache: checked("#sym_headache"),
          vision_changes: checked("#sym_vision_changes"),
          swelling_face_hands: checked("#sym_swelling_face_hands"),
          upper_abdominal_pain: checked("#sym_upper_abdominal_pain"),
          shortness_of_breath: checked("#sym_shortness_of_breath"),
          severe_weakness: checked("#sym_severe_weakness"),
          bp_systolic: val("#bp_systolic"),
          bp_diastolic: val("#bp_diastolic"),
          notes: str("#sym_notes"),
        };

        try {
          submitSym.disabled = true;
          if (statusSym) statusSym.textContent = "Submitting...";
          const res = await fetch(SYMPTOMS_URL, {
            method: "POST",
            headers: { "Content-Type": "application/json" },
            body: JSON.stringify(body)
          });
          const data = await res.json();
          if (!res.ok) throw new Error(data.error || "Failed to submit symptoms");

          if (statusSym) statusSym.textContent = "Saved.";
          if (symAlert) {
            const sev = data.evaluation?.severity || "info";
            symAlert.innerHTML = `
              <div class="rounded-2xl border ${pill(sev)} p-4">
                <div class="font-semibold">${data.evaluation?.title || "Update"}</div>
                <div class="mt-2 text-sm">${data.evaluation?.message || ""}</div>
              </div>
            `;
          }

          await logEvent("symptoms_ui_submitted", assessmentId, {
            report_id: data.report_id,
            alert_id: data.alert_id,
            severity: data.evaluation?.severity
          });
        } catch (e) {
          if (statusSym) statusSym.textContent = e.message || "Error";
        } finally {
          submitSym.disabled = false;
        }
      });
    }

    // Acknowledge alert
    personalizedPanel.querySelectorAll("button[data-ack]").forEach(btn => {
      btn.addEventListener("click", async () => {
        const alertId = Number(btn.getAttribute("data-ack") || 0);
        if (!alertId) return;
        btn.disabled = true;
        btn.textContent = "Acknowledged ✓";
        try {
          await fetch(ACK_ALERT_URL, {
            method: "POST",
            headers: { "Content-Type": "application/json" },
            body: JSON.stringify({ alert_id: alertId })
          });
          await logEvent("alert_ack_clicked", assessmentId, { alert_id: alertId });
        } catch (_) {}
      });
    });

    // Utility
    function val(sel) {
      const el = personalizedPanel.querySelector(sel);
      if (!el) return null;
      const v = el.value;
      if (v === "" || v == null) return null;
      return Number(v);
    }
    function str(sel) {
      const el = personalizedPanel.querySelector(sel);
      if (!el) return null;
      const v = (el.value || "").trim();
      return v ? v : null;
    }
    function checked(sel) {
      const el = personalizedPanel.querySelector(sel);
      return !!(el && el.checked);
    }
    function checkbox(key, label) {
      return `
        <label class="flex items-center gap-2 rounded-xl border border-slate-200 bg-slate-50 p-3 text-sm">
          <input id="sym_${key}" type="checkbox" class="rounded border-slate-300">
          <span>${label}</span>
        </label>
      `;
    }
  }

  form.addEventListener("submit", async (e) => {
    e.preventDefault();
    clearError();

    const fd = new FormData(form);
    const payload = Object.fromEntries(fd.entries());

    for (const k of required) {
      if (!(k in payload) || payload[k] === "" || payload[k] == null) {
        showError(`Please fill: ${k}`);
        return;
      }
    }

    const explain = payload.explain === "1" || payload.explain === "on";
    delete payload.explain;

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

      if (!res.ok) throw new Error(data.error || data.detail || "Prediction failed");

      renderResult(data);

      // Render output stage bundle
      if (data.bundle) {
        // attach alert_id if sent separately
        data.bundle.alert_id = data.alert_id || null;
        renderBundle(data.id, data.bundle);
        await logEvent("bundle_rendered", data.id, { tier: data.risk_tier });
      } else if (personalizedPanel) {
        personalizedPanel.innerHTML = "";
      }

    } catch (err) {
      showError(err.message || "Something went wrong");
    } finally {
      setLoading(false);
    }
  });
})();
