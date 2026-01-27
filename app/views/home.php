<?php
$title = "MaternalCare AI — Home";
ob_start();
?>

<main class="max-w-7xl mx-auto px-4 py-12">

  <!-- HERO -->
  <section class="grid lg:grid-cols-2 gap-10 items-center">
    <div>
      <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-indigo-50 text-indigo-700 text-xs border border-indigo-100">
        <span class="w-2 h-2 rounded-full bg-indigo-600"></span>
        AI-assisted maternal risk screening
      </div>

      <h1 class="mt-5 text-4xl sm:text-5xl font-semibold tracking-tight">
        Smart choice is health
        <span class="text-indigo-700 italic">over everything</span>
      </h1>

      <p class="mt-5 text-slate-600 text-base leading-relaxed max-w-xl">
        Predict high-risk pregnancy probability using clinical indicators (BP, blood sugar, temperature, BMI, and history).
        Get instant results with a clean, clinician-friendly interface.
      </p>

      <div class="mt-7 flex flex-wrap gap-3">
        <a href="#assess" class="px-5 py-3 rounded-2xl bg-indigo-600 hover:bg-indigo-700 text-white shadow">
          Get Consultation
        </a>
        <a href="/dashboard" class="px-5 py-3 rounded-2xl border border-slate-200 bg-white hover:bg-slate-50">
          Learn More
        </a>
      </div>

      <!-- Quick stats -->
      <div class="mt-10 grid grid-cols-2 sm:grid-cols-4 gap-3">
        <?php
          $stats = [
            ['label'=>'Assessments', 'value'=>'Fast'],
            ['label'=>'Model', 'value'=>'XGBoost'],
            ['label'=>'Explainability', 'value'=>'SHAP-ready'],
            ['label'=>'Deployment', 'value'=>'API + PHP'],
          ];
        ?>
        <?php foreach($stats as $s): ?>
          <div class="p-4 rounded-2xl bg-white/80 border border-slate-200 shadow-sm">
            <div class="text-lg font-semibold"><?= htmlspecialchars($s['value']) ?></div>
            <div class="text-xs text-slate-500 mt-1"><?= htmlspecialchars($s['label']) ?></div>
          </div>
        <?php endforeach; ?>
      </div>
    </div>

    <!-- Hero card -->
    <div class="relative">
      <div class="absolute -inset-2 bg-gradient-to-r from-indigo-200 to-purple-200 blur-2xl opacity-60 rounded-[2rem]"></div>
      <div class="relative rounded-[2rem] bg-white border border-slate-200 shadow-xl overflow-hidden">
        <div class="p-7">
          <div class="flex items-center justify-between">
            <div>
              <div class="text-sm text-slate-500">Latest assessment</div>
              <div class="text-xl font-semibold mt-1">Instant risk probability</div>
            </div>
            <div class="w-12 h-12 rounded-2xl bg-indigo-600/10 grid place-items-center">
              <div class="w-6 h-6 rounded-full bg-indigo-600"></div>
            </div>
          </div>

          <div class="mt-6 p-5 rounded-2xl bg-gradient-to-br from-indigo-50 to-purple-50 border border-indigo-100">
            <div class="text-sm text-slate-600">Result will appear here after submission.</div>
            <div id="heroResult" class="mt-3"></div>
          </div>

          <div class="mt-6 text-xs text-slate-500">
            Tip: Ensure binary fields are 0/1. The system validates inputs before prediction.
          </div>
        </div>

        <div class="px-7 py-5 bg-slate-50 border-t border-slate-200 flex items-center justify-between">
          <div class="text-sm text-slate-600">Trust our clinical indicators</div>
          <a href="#assess" class="text-sm text-indigo-700 hover:text-indigo-900 font-medium">Assess now →</a>
        </div>
      </div>
    </div>
  </section>

  <!-- ASSESSMENT FORM -->
  <section id="assess" class="mt-16 grid lg:grid-cols-2 gap-10 items-start">
    <div class="rounded-[2rem] bg-white border border-slate-200 shadow-sm p-7">
      <h2 class="text-2xl font-semibold">Risk Assessment</h2>
      <p class="mt-2 text-slate-600 text-sm">
        Enter patient indicators and submit for prediction.
      </p>

      <form id="riskForm" class="mt-6 grid grid-cols-1 sm:grid-cols-2 gap-4">
        <!-- Inputs -->
        <?php
          $fields = [
            ["Age","number","Age (years)"],
            ["Systolic BP","number","Systolic BP (mmHg)"],
            ["Diastolic","number","Diastolic (mmHg)"],
            ["BS","number","Blood Sugar (BS)"],
            ["Body Temp","number","Body Temp (°F)"],
            ["BMI","number","BMI"],
            ["Heart Rate","number","Heart Rate (bpm)"],
          ];
          foreach ($fields as $f):
        ?>
          <label class="block">
            <span class="text-xs text-slate-600"><?= htmlspecialchars($f[2]) ?></span>
            <input
              class="mt-1 w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm outline-none focus:ring-2 focus:ring-indigo-200"
              name="<?= htmlspecialchars($f[0]) ?>"
              type="<?= htmlspecialchars($f[1]) ?>"
              step="any"
              required
            />
          </label>
        <?php endforeach; ?>

        <?php
          $binary = [
            ["Previous Complications","Previous Complications (0/1)"],
            ["Preexisting Diabetes","Preexisting Diabetes (0/1)"],
            ["Gestational Diabetes","Gestational Diabetes (0/1)"],
            ["Mental Health","Mental Health (0/1)"],
          ];
          foreach ($binary as $b):
        ?>
          <label class="block">
            <span class="text-xs text-slate-600"><?= htmlspecialchars($b[1]) ?></span>
            <select
              class="mt-1 w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm outline-none focus:ring-2 focus:ring-indigo-200"
              name="<?= htmlspecialchars($b[0]) ?>"
              required
            >
              <option value="0">0 — No</option>
              <option value="1">1 — Yes</option>
            </select>
          </label>
        <?php endforeach; ?>
        <label class="sm:col-span-2 flex items-center gap-2 text-sm text-slate-600">
          <input type="checkbox" name="explain" value="1" class="rounded border-slate-300">
          Include explanation (SHAP)
        </label>
        <div class="sm:col-span-2 flex items-center gap-3 pt-2">
          <button
            id="submitBtn"
            class="inline-flex items-center justify-center gap-2 px-5 py-3 rounded-2xl bg-indigo-600 hover:bg-indigo-700 text-white shadow w-full sm:w-auto"
            type="submit"
          >
            <span id="btnText">Assess Risk</span>
            <span id="btnSpinner" class="hidden w-4 h-4 rounded-full border-2 border-white/50 border-t-white animate-spin"></span>
          </button>

          <a href="/dashboard" class="text-sm text-slate-600 hover:text-slate-900">
            View Dashboard →
          </a>
        </div>

        <div class="sm:col-span-2">
          <div id="formError" class="hidden mt-2 text-sm text-red-700 bg-red-50 border border-red-100 rounded-xl p-3"></div>
          <div id="formResult" class="hidden mt-4 rounded-2xl border p-5"></div>
        </div>
      </form>
    </div>

    <!-- Trust / specialists cards -->
    <div id="specialists" class="space-y-6">
      <div class="rounded-[2rem] bg-gradient-to-br from-indigo-600 to-purple-600 text-white p-7 shadow-xl">
        <h3 class="text-2xl font-semibold">Choose our most expert dedicated specialists</h3>
        <p class="mt-2 text-white/80 text-sm">
          The tool supports decision-making by summarizing risk probability and tier.
        </p>
        <div class="mt-5 inline-flex items-center gap-2 px-4 py-2 rounded-2xl bg-white/15 border border-white/20 text-sm">
          Book your slot →
        </div>
      </div>

      <div class="grid sm:grid-cols-2 gap-4">
        <?php
          $cards = [
            ['title'=>'Cardiology', 'desc'=>'Blood pressure & cardiovascular monitoring.'],
            ['title'=>'Endocrinology', 'desc'=>'Diabetes screening & blood sugar control.'],
            ['title'=>'Obstetrics', 'desc'=>'Maternal care and pregnancy tracking.'],
            ['title'=>'Mental Health', 'desc'=>'Support for stress & wellbeing.'],
          ];
          foreach($cards as $c):
        ?>
          <div class="rounded-2xl bg-white border border-slate-200 p-5 shadow-sm hover:shadow-md transition">
            <div class="text-sm font-semibold"><?= htmlspecialchars($c['title']) ?></div>
            <div class="text-xs text-slate-500 mt-2"><?= htmlspecialchars($c['desc']) ?></div>
            <div class="mt-4 text-xs text-indigo-700 font-medium">View details →</div>
          </div>
        <?php endforeach; ?>
      </div>
    </div>
  </section>

  <!-- ABOUT -->
  <section id="about" class="mt-16 rounded-[2rem] bg-white border border-slate-200 shadow-sm p-7">
    <h2 class="text-2xl font-semibold">System Summary</h2>
    <div class="mt-3 text-slate-600 text-sm leading-relaxed">
      This application follows a microservice approach:
      PHP MVC handles the user interface, validation, database storage, and reporting,
      while the ML model is served through an API for scalable inference.
    </div>
  </section>

</main>

<?php
$content = ob_get_clean();
require __DIR__ . '/layout.php';
