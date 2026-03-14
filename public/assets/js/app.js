(function () {
  const form = document.getElementById('riskForm');
  if (!form) return;

  const submitBtn = document.getElementById('submitBtn');
  const btnText = document.getElementById('btnText');
  const btnSpinner = document.getElementById('btnSpinner');
  const formError = document.getElementById('formError');
  const formResult = document.getElementById('formResult');
  const heroResult = document.getElementById('heroResult');
  const personalizedPanel = document.getElementById('personalizedPanel');

  const BASE = (window.APP_BASE || '').replace(/\/$/, '');
  const PREDICT_URL = `${BASE}/api/predict`;
  const INTERACT_URL = `${BASE}/api/interactions`;
  const SYMPTOMS_URL = `${BASE}/api/symptoms`;
  const ACK_ALERT_URL = `${BASE}/api/alerts/ack`;

  const required = ['Age','Systolic BP','Diastolic','BS','Body Temp','BMI','Previous Complications','Preexisting Diabetes','Gestational Diabetes','Mental Health','Heart Rate'];

  function setLoading(isLoading) {
    submitBtn.disabled = isLoading;
    if (btnText) btnText.textContent = isLoading ? 'Assessing...' : 'Assess Risk';
    if (btnSpinner) btnSpinner.classList.toggle('hidden', !isLoading);
  }

  function showError(msg) {
    formError.textContent = msg;
    formError.classList.remove('hidden');
  }

  function clearError() {
    formError.classList.add('hidden');
    formError.textContent = '';
  }

  async function logEvent(eventType, assessmentId, meta) {
    try {
      await fetch(INTERACT_URL, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ event_type: eventType, assessment_id: assessmentId, meta: meta || {} })
      });
    } catch (_) {}
  }

  function renderContributors(list) {
    if (!Array.isArray(list) || list.length === 0) return '';
    return `
      <div class="mt-4 text-sm">
        <div class="font-semibold text-slate-800">Top contributing factors</div>
        <div class="mt-2 rounded-xl border border-slate-200 bg-white p-3">
          <ul class="space-y-2">
            ${list.map(x => `<li class="flex items-center justify-between gap-4"><span class="text-slate-700">${x.feature}</span><span class="font-mono text-xs text-slate-500">${Number(x.shap_value).toFixed(4)}</span></li>`).join('')}
          </ul>
        </div>
      </div>`;
  }

  function renderResult(data) {
    const prob = (data.probability_high_risk * 100).toFixed(1) + '%';
    const tier = data.risk_tier;
    let box = 'border-slate-200 bg-slate-50 text-slate-900';
    if (tier === 'High') box = 'border-red-100 bg-red-50 text-red-900';
    if (tier === 'Moderate') box = 'border-amber-100 bg-amber-50 text-amber-900';
    if (tier === 'Low') box = 'border-emerald-100 bg-emerald-50 text-emerald-900';

    const html = `
      <div class="rounded-2xl border ${box} p-5">
        <div class="text-sm opacity-80">Prediction result</div>
        <div class="mt-2 text-2xl font-semibold">${tier} Risk</div>
        <div class="mt-1 text-sm">Probability: <span class="font-semibold">${prob}</span></div>
        <div class="mt-3 text-xs opacity-70">Saved (Assessment ID: ${data.id})</div>
        <div class="mt-4"><a href="${BASE}/game?assessment_id=${data.id}" class="inline-flex px-4 py-2 rounded-xl bg-slate-900 text-white text-sm hover:bg-slate-800">Play quick impact game</a></div>
        ${renderContributors(data.top_contributors)}
      </div>`;

    formResult.innerHTML = html;
    formResult.classList.remove('hidden');
    if (heroResult) heroResult.innerHTML = html;
  }

  function pill(severity) {
    if (severity === 'urgent') return 'bg-red-50 text-red-700 border-red-100';
    if (severity === 'warning') return 'bg-amber-50 text-amber-800 border-amber-100';
    return 'bg-slate-50 text-slate-700 border-slate-200';
  }

  function checkbox(key, label) {
    return `<label class="flex items-center gap-2 rounded-xl border border-slate-200 bg-slate-50 p-3 text-sm"><input id="sym_${key}" type="checkbox" class="rounded border-slate-300"><span>${label}</span></label>`;
  }

  function renderBundle(assessmentId, bundle) {
    if (!personalizedPanel) return;
    if (!bundle) {
      personalizedPanel.innerHTML = '';
      return;
    }

    const alert = bundle.alert;
    const educationGroups = bundle.education || {};
    const actions = bundle.recommended_actions || [];
    const disclaimer = bundle.disclaimer || '';

    const educationHtml = Object.keys(educationGroups).map((type) => {
      const items = educationGroups[type] || [];
      return `
        <div class="rounded-2xl border border-slate-200 bg-white p-5">
          <div class="text-sm font-semibold text-slate-900">${type.replace(/_/g, ' ').toUpperCase()}</div>
          <div class="mt-3 space-y-3">
            ${items.map(it => `<div class="rounded-xl border border-slate-200 bg-slate-50 p-4"><div class="font-semibold text-slate-900">${it.title}</div><div class="mt-2 text-sm text-slate-600 leading-relaxed">${it.body}</div></div>`).join('')}
          </div>
        </div>`;
    }).join('');

    const actionsHtml = actions.map((a) => `
      <li class="rounded-xl border border-slate-200 bg-white p-4">
        <div class="font-semibold text-slate-900">${a.title}</div>
        <div class="mt-1 text-sm text-slate-600">${a.detail}</div>
        <button data-action="${a.title}" class="mt-3 text-xs px-3 py-2 rounded-xl border border-slate-200 hover:bg-slate-50">Mark as helpful</button>
      </li>`).join('');

    const alertHtml = alert ? `
      <div class="rounded-2xl border ${pill(alert.severity)} p-5">
        <div class="flex items-center justify-between gap-3">
          <div><div class="text-xs opacity-80">${alert.severity.toUpperCase()} ALERT</div><div class="text-lg font-semibold mt-1">${alert.title}</div></div>
          ${bundle.alert_id ? `<button data-ack="${bundle.alert_id}" class="text-xs px-3 py-2 rounded-xl border border-slate-200 bg-white hover:bg-slate-50">Acknowledge</button>` : ''}
        </div>
        <div class="mt-3 text-sm leading-relaxed">${alert.message}</div>
      </div>` : '';

    // const gameHtml = `
    //   <div class="rounded-2xl border border-slate-200 bg-white p-5">
    //     <div class="flex items-center justify-between gap-3 flex-wrap">
    //       <div>
    //         <div class="text-lg font-semibold">Quick impact game</div>
    //         <div class="text-sm text-slate-600 mt-1">The impact questions now run as an immersive game with score, levels and saved progress.</div>
    //       </div>
    //       <a href="${BASE}/game?assessment_id=${assessmentId}" class="px-5 py-3 rounded-2xl bg-slate-900 hover:bg-slate-800 text-white text-sm">Open game</a>
    //     </div>
    //     <div class="mt-4 text-xs text-slate-500">${disclaimer}</div>
    //   </div>`;

    const html = `
      <section class="mt-10 space-y-6">
        <div class="flex items-end justify-between flex-wrap gap-3">
          <div><div class="text-sm text-slate-500">Output stage</div><div class="text-2xl font-semibold">Personalized interventions</div></div>
          <button id="open_symptoms_inline" class="text-xs px-3 py-2 rounded-xl border border-slate-200 bg-white hover:bg-slate-50">Log symptoms</button>
        </div>
        ${alertHtml}
        <div class="grid lg:grid-cols-2 gap-6">
          ${educationHtml || '<div class="rounded-2xl border border-slate-200 bg-white p-5">No education content found.</div>'}
          <div class="rounded-2xl border border-slate-200 bg-white p-5"><div class="text-lg font-semibold">Recommended actions</div><ul class="mt-4 space-y-3">${actionsHtml}</ul></div>
        </div>
       
        <div id="inlineSymptoms" class="hidden rounded-2xl border border-slate-200 bg-white p-5">
          <div class="text-lg font-semibold">Symptom check-in</div>
          <div class="text-sm text-slate-600 mt-1">Log symptoms to trigger early warning alerts.</div>
          <div class="mt-4 grid sm:grid-cols-2 gap-3">
            ${checkbox('headache','Headache')}
            ${checkbox('vision_changes','Vision changes')}
            ${checkbox('swelling_face_hands','Swelling (face/hands)')}
            ${checkbox('upper_abdominal_pain','Upper abdominal pain')}
            ${checkbox('shortness_of_breath','Shortness of breath')}
            ${checkbox('severe_weakness','Severe weakness')}
          </div>
          <div class="mt-4 grid sm:grid-cols-2 gap-4">
            <label class="block"><div class="text-xs text-slate-600">BP systolic (optional)</div><input id="bp_systolic" type="number" step="any" class="mt-1 w-full rounded-xl border border-slate-200 px-3 py-2 text-sm"></label>
            <label class="block"><div class="text-xs text-slate-600">BP diastolic (optional)</div><input id="bp_diastolic" type="number" step="any" class="mt-1 w-full rounded-xl border border-slate-200 px-3 py-2 text-sm"></label>
            <label class="block sm:col-span-2"><div class="text-xs text-slate-600">Notes (optional)</div><input id="sym_notes" type="text" maxlength="500" class="mt-1 w-full rounded-xl border border-slate-200 px-3 py-2 text-sm"></label>
          </div>
          <div class="mt-4 flex items-center gap-3"><button id="submit_symptoms" class="px-5 py-3 rounded-2xl bg-indigo-600 hover:bg-indigo-700 text-white text-sm">Submit symptom report</button><span id="symptoms_status" class="text-sm text-slate-600"></span></div>
          <div id="symptoms_alert" class="mt-4"></div>
        </div>
      </section>`;

    personalizedPanel.innerHTML = html;

    personalizedPanel.querySelectorAll('button[data-action]').forEach(btn => {
      btn.addEventListener('click', async () => {
        const name = btn.getAttribute('data-action');
        btn.textContent = 'Saved ✓';
        btn.disabled = true;
        await logEvent('action_marked_helpful', assessmentId, { action: name });
      });
    });

    const openBtn = personalizedPanel.querySelector('#open_symptoms_inline');
    const box = personalizedPanel.querySelector('#inlineSymptoms');
    if (openBtn && box) openBtn.addEventListener('click', () => box.classList.toggle('hidden'));

    const submitSym = personalizedPanel.querySelector('#submit_symptoms');
    const statusSym = personalizedPanel.querySelector('#symptoms_status');
    const symAlert = personalizedPanel.querySelector('#symptoms_alert');
    if (submitSym) {
      submitSym.addEventListener('click', async () => {
        const body = {
          assessment_id: assessmentId,
          headache: checked('#sym_headache'),
          vision_changes: checked('#sym_vision_changes'),
          swelling_face_hands: checked('#sym_swelling_face_hands'),
          upper_abdominal_pain: checked('#sym_upper_abdominal_pain'),
          shortness_of_breath: checked('#sym_shortness_of_breath'),
          severe_weakness: checked('#sym_severe_weakness'),
          bp_systolic: val('#bp_systolic'),
          bp_diastolic: val('#bp_diastolic'),
          notes: str('#sym_notes')
        };

        try {
          submitSym.disabled = true;
          if (statusSym) statusSym.textContent = 'Submitting...';
          const res = await fetch(SYMPTOMS_URL, { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify(body) });
          const data = await res.json();
          if (!res.ok) throw new Error(data.error || 'Failed to submit symptoms');
          if (statusSym) statusSym.textContent = 'Saved.';
          if (symAlert) {
            const sev = data.evaluation?.severity || 'info';
            symAlert.innerHTML = `<div class="rounded-2xl border ${pill(sev)} p-4"><div class="font-semibold">${data.evaluation?.title || 'Update'}</div><div class="mt-2 text-sm">${data.evaluation?.message || ''}</div></div>`;
          }
          await logEvent('symptoms_ui_submitted', assessmentId, { report_id: data.report_id, alert_id: data.alert_id, severity: data.evaluation?.severity });
        } catch (e) {
          if (statusSym) statusSym.textContent = e.message || 'Error';
        } finally {
          submitSym.disabled = false;
        }
      });
    }

    personalizedPanel.querySelectorAll('button[data-ack]').forEach(btn => {
      btn.addEventListener('click', async () => {
        const alertId = Number(btn.getAttribute('data-ack') || 0);
        if (!alertId) return;
        btn.disabled = true;
        btn.textContent = 'Acknowledged ✓';
        try {
          await fetch(ACK_ALERT_URL, { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ alert_id: alertId }) });
          await logEvent('alert_ack_clicked', assessmentId, { alert_id: alertId });
        } catch (_) {}
      });
    });

    function val(sel) { const el = personalizedPanel.querySelector(sel); if (!el) return null; const v = el.value; return v === '' || v == null ? null : Number(v); }
    function str(sel) { const el = personalizedPanel.querySelector(sel); if (!el) return null; const v = (el.value || '').trim(); return v || null; }
    function checked(sel) { const el = personalizedPanel.querySelector(sel); return !!(el && el.checked); }
  }

  form.addEventListener('submit', async (e) => {
    e.preventDefault();
    clearError();

    const fd = new FormData(form);
    const payload = Object.fromEntries(fd.entries());
    for (const k of required) {
      if (!(k in payload) || payload[k] === '' || payload[k] == null) {
        showError(`Please fill: ${k}`);
        return;
      }
    }

    const explain = payload.explain === '1' || payload.explain === 'on';
    delete payload.explain;
    payload.__explain = explain ? 1 : 0;
    payload.__top_k = 6;

    setLoading(true);
    try {
      const res = await fetch(PREDICT_URL, { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify(payload) });
      const data = await res.json();
      if (!res.ok) throw new Error(data.error || data.detail || 'Prediction failed');
      renderResult(data);
      if (data.bundle) {
        data.bundle.alert_id = data.alert_id || null;
        renderBundle(data.id, data.bundle);
        await logEvent('bundle_rendered', data.id, { tier: data.risk_tier });
      } else if (personalizedPanel) {
        personalizedPanel.innerHTML = '';
      }
    } catch (err) {
      showError(err.message || 'Something went wrong');
    } finally {
      setLoading(false);
    }
  });
})();
