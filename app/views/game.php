<?php
$title = 'Impact Game — MaternalCare AI';
$assessmentId = (int)($assessment['id'] ?? 0);
ob_start();
?>
<main class="max-w-5xl mx-auto px-4 py-12">
  <div class="flex items-end justify-between gap-4 flex-wrap">
    <div>
      <h1 class="text-3xl font-semibold">Quick Impact Game</h1>
      <p class="mt-2 text-sm text-slate-600">A short interactive challenge that turns the impact questions into an immersive score-based experience.</p>
    </div>
    <a href="<?= base_url('dashboard') ?>" class="px-4 py-2 rounded-xl border bg-white hover:bg-slate-50 text-sm">Back to dashboard</a>
  </div>

  <div class="mt-8 grid lg:grid-cols-[1.3fr_.7fr] gap-6">
    <section class="rounded-[2rem] bg-white border border-slate-200 shadow-sm p-6">
      <div class="flex items-center justify-between gap-4">
        <div>
          <div class="text-sm text-slate-500">Assessment #<?= $assessmentId ?></div>
          <div class="text-xl font-semibold mt-1">Protect Mom Mission</div>
        </div>
        <div id="gameLevel" class="px-4 py-2 rounded-xl bg-indigo-50 text-indigo-700 border border-indigo-100 text-sm font-semibold">Level: Starter</div>
      </div>

      <div class="mt-6 h-3 rounded-full bg-slate-100 overflow-hidden">
        <div id="gameProgress" class="h-full w-0 bg-indigo-600 transition-all duration-500"></div>
      </div>

      <div id="gameCard" class="mt-6 rounded-[2rem] border border-slate-200 bg-gradient-to-br from-indigo-600 to-purple-600 text-white p-8 min-h-[320px]"></div>

      <div class="mt-6 flex items-center justify-between gap-3 flex-wrap">
        <div id="gameStatus" class="text-sm text-slate-600">Answer the questions to earn points and unlock higher levels.</div>
        <button id="restartGame" class="px-4 py-2 rounded-xl border border-slate-200 bg-white hover:bg-slate-50 text-sm">Restart</button>
      </div>
    </section>

    <aside class="rounded-[2rem] bg-white border border-slate-200 shadow-sm p-6 space-y-5">
      <div>
        <div class="text-sm text-slate-500">Score</div>
        <div id="gameScore" class="text-4xl font-bold mt-1">0</div>
      </div>
      <div>
        <div class="text-sm text-slate-500">Risk tier</div>
        <div class="mt-1 inline-flex px-3 py-2 rounded-xl bg-slate-100 text-slate-700 border border-slate-200 text-sm font-medium"><?= htmlspecialchars($assessment['risk_level'] ?? 'Unknown') ?></div>
      </div>
      <div>
        <div class="text-sm text-slate-500">Unlocked badges</div>
        <div id="gameBadges" class="mt-3 flex flex-wrap gap-2"></div>
      </div>
      <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4 text-sm text-slate-600">
        Good answers improve your final score and save the same impact metrics used in reporting.
      </div>
    </aside>
  </div>
</main>

<script>
(function(){
  const assessmentId = <?= $assessmentId ?>;
  const BASE = (window.APP_BASE || '').replace(/\/$/, '');
  const OUTCOMES_URL = `${BASE}/api/outcomes`;
  const cards = [
    {
      prompt: 'A pregnant patient has severe headache and blurred vision. What should happen first?',
      answers: [
        { label: 'Wait until the next clinic day', correct: false, metric: { warning_sign_recognition: 1 } },
        { label: 'Seek urgent medical review immediately', correct: true, metric: { warning_sign_recognition: 5, care_seeking_intention: 5 } },
        { label: 'Only drink water and rest', correct: false, metric: { warning_sign_recognition: 2 } },
      ]
    },
    {
      prompt: 'Which habit best helps reduce complications after a risk assessment?',
      answers: [
        { label: 'Skipping follow-up visits when you feel okay', correct: false, metric: { knowledge_score: 1 } },
        { label: 'Keeping ANC appointments and monitoring symptoms', correct: true, metric: { knowledge_score: 5, self_efficacy: 5 } },
        { label: 'Stopping all medication without advice', correct: false, metric: { knowledge_score: 1 } },
      ]
    },
    {
      prompt: 'You notice swelling of the face and hands. What does that mean?',
      answers: [
        { label: 'It can be a warning sign worth reporting quickly', correct: true, metric: { warning_sign_recognition: 5, care_seeking_intention: 4 } },
        { label: 'It is always normal and never needs follow-up', correct: false, metric: { warning_sign_recognition: 1 } },
        { label: 'Ignore it unless there is pain', correct: false, metric: { warning_sign_recognition: 2 } },
      ]
    },
    {
      prompt: 'Final challenge: what is the best mindset after receiving a high-risk result?',
      answers: [
        { label: 'Panic and avoid the hospital', correct: false, metric: { self_efficacy: 1 } },
        { label: 'Use the plan, seek support, and act early', correct: true, metric: { self_efficacy: 5, care_seeking_intention: 5 } },
        { label: 'Hide symptoms and hope they go away', correct: false, metric: { self_efficacy: 1 } },
      ]
    }
  ];

  let idx = 0, score = 0;
  let totals = { knowledge_score: 0, warning_sign_recognition: 0, self_efficacy: 0, care_seeking_intention: 0 };
  const card = document.getElementById('gameCard');
  const scoreEl = document.getElementById('gameScore');
  const progressEl = document.getElementById('gameProgress');
  const levelEl = document.getElementById('gameLevel');
  const statusEl = document.getElementById('gameStatus');
  const badgesEl = document.getElementById('gameBadges');
  document.getElementById('restartGame').addEventListener('click', restart);

  function levelFromScore(s){ if (s >= 35) return 'Champion'; if (s >= 25) return 'Advanced'; if (s >= 15) return 'Rising'; return 'Starter'; }
  function badgesFromScore(s){ const b=[]; if(s>=10)b.push('Fast Learner'); if(s>=20)b.push('Care Seeker'); if(s>=30)b.push('Warning Sign Pro'); if(s>=35)b.push('Maternal Hero'); return b; }
  function updateMeta(){ scoreEl.textContent = score; const pct = Math.round((idx / cards.length) * 100); progressEl.style.width = pct + '%'; levelEl.textContent = 'Level: ' + levelFromScore(score); badgesEl.innerHTML = badgesFromScore(score).map(b => `<span class="px-3 py-1 rounded-full bg-emerald-50 text-emerald-700 border border-emerald-100 text-xs font-medium">${b}</span>`).join(''); }

  function render(){
    updateMeta();
    if(idx >= cards.length){ finish(); return; }
    const q = cards[idx];
    card.innerHTML = `
      <div class="text-sm text-white/75">Question ${idx+1} of ${cards.length}</div>
      <div class="mt-4 text-2xl font-semibold leading-snug">${q.prompt}</div>
      <div class="mt-8 grid gap-3">${q.answers.map((a,i)=>`<button data-i="${i}" class="answerBtn text-left px-5 py-4 rounded-2xl bg-white/10 hover:bg-white/20 border border-white/20 transition">${a.label}</button>`).join('')}</div>
    `;
    card.querySelectorAll('.answerBtn').forEach(btn => btn.addEventListener('click', () => choose(Number(btn.dataset.i))));
  }

  function choose(i){
    const q = cards[idx];
    const ans = q.answers[i];
    totals = {
      knowledge_score: Math.max(totals.knowledge_score, ans.metric.knowledge_score || 0),
      warning_sign_recognition: Math.max(totals.warning_sign_recognition, ans.metric.warning_sign_recognition || 0),
      self_efficacy: Math.max(totals.self_efficacy, ans.metric.self_efficacy || 0),
      care_seeking_intention: Math.max(totals.care_seeking_intention, ans.metric.care_seeking_intention || 0),
    };
    score += ans.correct ? 10 : 3;
    statusEl.textContent = ans.correct ? 'Great choice — points added.' : 'Not the best option, but you still learned something.';
    idx += 1;
    setTimeout(render, 450);
  }

  async function finish(){
    updateMeta();
    progressEl.style.width = '100%';
    const level = levelFromScore(score);
    card.innerHTML = `
      <div class="text-sm text-white/75">Mission complete</div>
      <div class="mt-4 text-3xl font-bold">You scored ${score} points</div>
      <div class="mt-3 text-white/85">Final level: ${level}</div>
      <div class="mt-6 rounded-2xl bg-white/10 border border-white/20 p-4 text-sm leading-7">
        Knowledge: ${totals.knowledge_score || 1}/5<br>
        Warning-sign recognition: ${totals.warning_sign_recognition || 1}/5<br>
        Self-efficacy: ${totals.self_efficacy || 1}/5<br>
        Care-seeking intention: ${totals.care_seeking_intention || 1}/5
      </div>
      <button id="saveGame" class="mt-8 px-5 py-3 rounded-2xl bg-white text-indigo-700 font-semibold">Save my game score</button>
    `;
    document.getElementById('saveGame').addEventListener('click', save);
    statusEl.textContent = 'Save your game score to store the impact outcomes.';
  }

  async function save(){
    const body = {
      assessment_id: assessmentId,
      knowledge_score: totals.knowledge_score || 1,
      warning_sign_recognition: totals.warning_sign_recognition || 1,
      self_efficacy: totals.self_efficacy || 1,
      care_seeking_intention: totals.care_seeking_intention || 1,
      game_score: score,
      game_level: levelFromScore(score),
      notes: 'Submitted via quick impact game'
    };
    const btn = document.getElementById('saveGame');
    btn.disabled = true; btn.textContent = 'Saving...';
    try {
      const res = await fetch(OUTCOMES_URL, { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify(body) });
      const data = await res.json();
      if(!res.ok) throw new Error(data.error || 'Failed to save score');
      btn.textContent = 'Saved ✓';
      statusEl.textContent = 'Game results saved successfully.';
    } catch (e) {
      btn.disabled = false; btn.textContent = 'Save my game score';
      statusEl.textContent = e.message || 'Unable to save game score.';
    }
  }

  function restart(){ idx = 0; score = 0; totals = { knowledge_score: 0, warning_sign_recognition: 0, self_efficacy: 0, care_seeking_intention: 0 }; statusEl.textContent = 'Answer the questions to earn points and unlock higher levels.'; render(); }
  render();
})();
</script>
<?php $content = ob_get_clean(); require __DIR__ . '/layout.php'; ?>
