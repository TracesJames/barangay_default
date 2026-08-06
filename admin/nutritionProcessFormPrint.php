<?php

/**
 * Nutrition Portal — Documented Process Form (printable / PDF).
 * Operational SOP + checklists + sign-off for Valencia City Nutrition Portal.
 */

include_once '../connection.php';
include_once '../includes/auth_admin.php';
require_once '../includes/barangay_context.php';
require_once '../includes/staff_permissions.php';
require_once '../includes/nutrition_context.php';

$user_id = (string) $_SESSION['user_id'];
$isSuperAdmin = barangay_user_is_super_admin($con, $user_id);
$isBnsAdmin = barangay_user_is_bns_admin($con, $user_id);
$isCityAdmin = barangay_user_is_city_admin($con, $user_id);
$isNutritionPortalAdmin = barangay_user_is_nutrition_portal_admin($con, $user_id);
$canOpenHub = $isSuperAdmin || $isBnsAdmin || $isCityAdmin || $isNutritionPortalAdmin;

date_default_timezone_set('Asia/Manila');
$generatedAt = date('F j, Y g:i A');
$docVersion = '2026.08.05';
$formNo = 'NP-PROC-001';
$childMaxAge = nutrition_child_max_age_years();
$autoDownload = isset($_GET['download']) && (string) $_GET['download'] === '1';
$autoDownloadDoc = isset($_GET['download']) && in_array((string) $_GET['download'], ['doc', 'docx', 'word'], true);
$pdfFilename = 'Nutrition_Portal_Process_Form_Valencia_City.pdf';
$docFilename = 'Nutrition_Portal_Process_Form_Valencia_City.doc';
$barangayLabel = trim((string) ($barangay ?? ''));
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Nutrition Portal Process Form (PDF) | City of Valencia</title>
  <style>
    @import url('https://fonts.googleapis.com/css2?family=Source+Sans+3:ital,wght@0,400;0,600;0,700;0,800&display=swap');
    :root {
      --ink: #0f172a;
      --muted: #475569;
      --line: #cbd5e1;
      --accent: #166534;
      --accent-soft: #dcfce7;
    }
    * { box-sizing: border-box; }
    body {
      margin: 0;
      font-family: 'Source Sans 3', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
      color: var(--ink);
      background: #e2e8f0;
      font-size: 10.5pt;
      line-height: 1.4;
    }
    .no-print {
      position: sticky;
      top: 0;
      z-index: 50;
      display: flex;
      flex-wrap: wrap;
      gap: .5rem;
      align-items: center;
      padding: .65rem 1rem;
      background: #0f172a;
      color: #e2e8f0;
      box-shadow: 0 2px 8px rgba(0,0,0,.2);
    }
    .no-print button, .no-print a.btn {
      border: 0;
      border-radius: 6px;
      padding: .45rem .9rem;
      font-weight: 600;
      cursor: pointer;
      text-decoration: none;
      font-size: .9rem;
      color: #0f172a;
      background: #e2e8f0;
    }
    .no-print .btn-print { background: #22c55e; color: #052e16; }
    .no-print .btn-pdf { background: #38bdf8; color: #0c4a6e; }
    .no-print .btn-doc { background: #a5b4fc; color: #1e1b4b; }
    .no-print .btn-back { background: transparent; color: #e2e8f0; border: 1px solid #64748b; }
    #guidePdfStatus { font-size: .8rem; color: #94a3b8; margin-left: .35rem; }

    #processPrintRoot {
      max-width: 210mm;
      margin: 1rem auto 2rem;
      background: #fff;
      padding: 14mm 14mm 16mm;
      box-shadow: 0 8px 30px rgba(15, 23, 42, .12);
    }

    .doc-banner {
      display: grid;
      grid-template-columns: 1fr auto;
      gap: .75rem;
      border: 2px solid var(--accent);
      padding: .75rem 1rem;
      margin-bottom: 1rem;
    }
    .doc-banner h1 {
      margin: 0;
      font-size: 1.35rem;
      color: var(--accent);
      letter-spacing: .02em;
    }
    .doc-banner .subtitle { margin: .15rem 0 0; font-weight: 700; font-size: 1rem; }
    .doc-banner .meta-box {
      border: 1px solid var(--line);
      padding: .4rem .65rem;
      font-size: .78rem;
      min-width: 160px;
    }
    .doc-banner .meta-box div { margin: .1rem 0; }
    .muted { color: var(--muted); }
    .small { font-size: .85rem; }

    h2 {
      margin: 1.35rem 0 .55rem;
      padding-bottom: .25rem;
      border-bottom: 2px solid var(--accent);
      font-size: 1.05rem;
      color: var(--accent);
      page-break-after: avoid;
    }
    h3 {
      margin: .9rem 0 .4rem;
      font-size: .95rem;
      color: #14532d;
      page-break-after: avoid;
    }
    p { margin: .35rem 0; }
    ol.process-steps, ul.check-list {
      margin: .35rem 0 .6rem;
      padding-left: 1.25rem;
    }
    ol.process-steps li, ul.check-list li { margin: .25rem 0; }

    table {
      width: 100%;
      border-collapse: collapse;
      margin: .5rem 0 .85rem;
      font-size: .92em;
    }
    th, td {
      border: 1px solid var(--line);
      padding: .35rem .5rem;
      vertical-align: top;
      text-align: left;
    }
    th {
      background: var(--accent-soft);
      color: #14532d;
      font-weight: 700;
    }
    tr.section-row td {
      background: #f1f5f9;
      font-weight: 700;
    }

    .box {
      border: 1px solid var(--line);
      border-radius: 4px;
      padding: .65rem .8rem;
      margin: .5rem 0 .85rem;
      background: #f8fafc;
    }
    .box.note {
      border-left: 4px solid var(--accent);
      background: #f0fdf4;
    }
    .path {
      font-family: ui-monospace, SFMono-Regular, Menlo, Consolas, monospace;
      font-size: .82em;
      background: #f1f5f9;
      padding: .05rem .3rem;
      border-radius: 3px;
    }

    .checkbox {
      display: inline-block;
      width: .85rem;
      height: .85rem;
      border: 1.5px solid #334155;
      margin-right: .4rem;
      vertical-align: -1px;
      border-radius: 2px;
    }

    .sign-grid {
      display: grid;
      grid-template-columns: 1fr 1fr 1fr;
      gap: .75rem;
      margin-top: .75rem;
    }
    .sign-box {
      border: 1px solid var(--line);
      min-height: 110px;
      padding: .5rem .65rem;
      page-break-inside: avoid;
    }
    .sign-box .label { font-size: .78rem; font-weight: 700; color: var(--muted); text-transform: uppercase; }
    .sign-box .line {
      margin-top: 2.4rem;
      border-top: 1px solid #94a3b8;
      padding-top: .25rem;
      font-size: .78rem;
      color: var(--muted);
    }

    .flow {
      display: grid;
      gap: .35rem;
      margin: .5rem 0 1rem;
    }
    .flow-step {
      display: grid;
      grid-template-columns: 2.2rem 1fr;
      gap: .5rem;
      align-items: start;
      page-break-inside: avoid;
    }
    .flow-num {
      width: 2rem;
      height: 2rem;
      border-radius: 50%;
      background: var(--accent);
      color: #fff;
      display: flex;
      align-items: center;
      justify-content: center;
      font-weight: 800;
      font-size: .85rem;
    }
    .flow-body {
      border: 1px solid var(--line);
      border-radius: 4px;
      padding: .45rem .65rem;
      background: #fff;
    }
    .flow-body strong { display: block; margin-bottom: .1rem; }
    .flow-body span { font-size: .88em; color: var(--muted); }

    .footer-meta {
      margin-top: 1.25rem;
      padding-top: .65rem;
      border-top: 1px solid var(--line);
      font-size: .78rem;
      color: var(--muted);
      display: flex;
      justify-content: space-between;
      gap: 1rem;
      flex-wrap: wrap;
    }

    @media print {
      body { background: #fff; }
      .no-print { display: none !important; }
      #processPrintRoot {
        margin: 0;
        padding: 0;
        box-shadow: none;
        max-width: none;
      }
      h2 { page-break-after: avoid; }
      .sign-grid, .flow-step, table { page-break-inside: avoid; }
    }
  </style>
  <?php require __DIR__ . '/../includes/partials/report_fit_assets.php'; ?>
</head>
<body>
  <div class="no-print">
    <a class="btn btn-back" href="<?= $canOpenHub ? 'nutritionSuperDashboard.php' : 'nutritionDashboard.php' ?>">← Back</a>
    <button type="button" class="btn-print" onclick="window.print()">Print</button>
    <button type="button" class="btn-pdf" id="guideDownloadPdfBtn">Download PDF</button>
    <button type="button" class="btn-doc" id="guideDownloadDocBtn">Download Word (.doc)</button>
    <a class="btn btn-pdf" href="?download=1">Auto PDF</a>
    <a class="btn btn-doc" href="?download=doc">Auto Word</a>
    <span id="guidePdfStatus"></span>
  </div>

  <div id="processPrintRoot" data-report-fit="a4">
    <header class="doc-banner">
      <div>
        <p class="muted small" style="margin:0">City of Valencia · Bukidnon · City Nutrition Committee</p>
        <h1>NUTRITION PORTAL</h1>
        <p class="subtitle">Documented Process Form</p>
        <p class="muted small" style="margin:.25rem 0 0">Standard operating process for data entry, school/manual fields, reports, and city consolidation.</p>
      </div>
      <div class="meta-box">
        <div><strong>Form No.:</strong> <?= htmlspecialchars($formNo, ENT_QUOTES, 'UTF-8') ?></div>
        <div><strong>Version:</strong> <?= htmlspecialchars($docVersion, ENT_QUOTES, 'UTF-8') ?></div>
        <div><strong>Generated:</strong> <?= htmlspecialchars($generatedAt, ENT_QUOTES, 'UTF-8') ?></div>
        <?php if ($barangayLabel !== '') : ?>
        <div><strong>Barangay:</strong> <?= htmlspecialchars($barangayLabel, ENT_QUOTES, 'UTF-8') ?></div>
        <?php endif; ?>
      </div>
    </header>

    <section>
      <h2>1. Document control</h2>
      <table>
        <thead>
          <tr>
            <th style="width:22%">Field</th>
            <th>Entry</th>
          </tr>
        </thead>
        <tbody>
          <tr><td>Document title</td><td>Nutrition Portal — Documented Process Form</td></tr>
          <tr><td>System</td><td>Barangay Hub · Nutrition Portal (Valencia City)</td></tr>
          <tr><td>Related guide</td><td>User Guide PDF — <span class="path">nutritionHubGuidePrint.php</span></td></tr>
          <tr><td>Effective date</td><td>________________________</td></tr>
          <tr><td>Review cycle</td><td>☐ Monthly &nbsp; ☐ Quarterly &nbsp; ☐ As needed after system update</td></tr>
          <tr><td>Retention</td><td>Keep with CNC / BNS file for the covering year</td></tr>
        </tbody>
      </table>
      <div class="sign-grid">
        <div class="sign-box">
          <div class="label">Prepared by</div>
          <div class="line">Name / Position / Date</div>
        </div>
        <div class="sign-box">
          <div class="label">Reviewed by</div>
          <div class="line">Name / Position / Date</div>
        </div>
        <div class="sign-box">
          <div class="label">Approved by</div>
          <div class="line">Name / Position / Date</div>
        </div>
      </div>
    </section>

    <section>
      <h2>2. Purpose &amp; scope</h2>
      <p>This form defines the <strong>standard process</strong> for encoding, validating, and reporting nutrition data in the Nutrition Portal so barangay and city reports (BNP C1–C9, MELLPI, e-OPT Plus) stay complete and consistent.</p>
      <div class="box note">
        <strong>In scope:</strong> login &amp; roles, household surveys, child assessments (0–<?= (int) $childMaxAge ?>), BNP Form C1 manual/school fields, barangay reports, MELLPI City Profile, city print pack.<br>
        <strong>Out of scope:</strong> City of Valencia Portal modules (certificates, blotter, etc.) unless used only to register residents linked to assessments.
      </div>
    </section>

    <section>
      <h2>3. Roles</h2>
      <table>
        <thead>
          <tr><th>Role</th><th>Primary process duties</th></tr>
        </thead>
        <tbody>
          <tr><td>BNS / Barangay nutrition staff</td><td>Encode household surveys &amp; assessments; fill Settings → BNP C1 school/manual fields; print barangay BNP / pregnant reports</td></tr>
          <tr><td>BNS Admin / City Admin</td><td>Oversee all barangays via picker; validate completeness; support MELLPI / city prints</td></tr>
          <tr><td>Nutrition Super Admin</td><td>Manage BNS accounts; city hub; MELLPI registration; city consolidated report</td></tr>
          <tr><td>City Super Admin</td><td>May switch between City of Valencia Portal and Nutrition Portal</td></tr>
        </tbody>
      </table>
    </section>

    <section>
      <h2>4. End-to-end process (recommended order)</h2>
      <div class="flow">
        <div class="flow-step">
          <div class="flow-num">1</div>
          <div class="flow-body">
            <strong>Access</strong>
            <span>Login → Nutrition Portal (Hub or assigned barangay)</span>
          </div>
        </div>
        <div class="flow-step">
          <div class="flow-num">2</div>
          <div class="flow-body">
            <strong>Barangay data entry</strong>
            <span>Household Survey (children + pregnant/lactating) · optional New Assessment for registered residents 0–<?= (int) $childMaxAge ?></span>
          </div>
        </div>
        <div class="flow-step">
          <div class="flow-num">3</div>
          <div class="flow-body">
            <strong>School &amp; manual Form C1 fields</strong>
            <span>Settings → BNP Form C1 (Day Care, Elementary, Kinder–G6 status, FIC, workers, stores)</span>
          </div>
        </div>
        <div class="flow-step">
          <div class="flow-num">4</div>
          <div class="flow-body">
            <strong>Barangay validation</strong>
            <span>BNP C1–C9 preview/print · Consolidated · Pregnant families</span>
          </div>
        </div>
        <div class="flow-step">
          <div class="flow-num">5</div>
          <div class="flow-body">
            <strong>City registration</strong>
            <span>MELLPI City Profile Registration (city-level matrices / facilities)</span>
          </div>
        </div>
        <div class="flow-step">
          <div class="flow-num">6</div>
          <div class="flow-body">
            <strong>City submission pack</strong>
            <span>Print City Report = MELLPI CM + BNP C1–C9 + e-OPT Plus</span>
          </div>
        </div>
      </div>
    </section>

    <section>
      <h2>5. Process A — Login &amp; open Nutrition Portal</h2>
      <ol class="process-steps">
        <li>Open <span class="path">login.php</span> and sign in with the assigned account.</li>
        <li>Nutrition Super Admin lands on the city Nutrition Hub dashboard.</li>
        <li>City Super Admin may enter from Super Dashboard → <strong>Nutrition Portal</strong>.</li>
        <li>Confirm sidebar brand shows <strong>Nutrition Portal</strong> (not City of Valencia Portal menus for nutrition-only accounts).</li>
        <li>City users: <strong>Select Barangay</strong> to open a barangay Nutrition Portal workspace.</li>
      </ol>
      <p class="small muted">URLs: <span class="path">nutritionSuperDashboard.php</span> · <span class="path">nutritionDashboard.php</span> · <span class="path">barangayHub.php?picker=1&amp;system=nutrition&amp;view=picker</span></p>
    </section>

    <section>
      <h2>6. Process B — Household Survey (primary data entry)</h2>
      <p><strong>Owner:</strong> BNS / barangay nutrition staff &nbsp;|&nbsp; <strong>Page:</strong> <span class="path">nutritionHouseholdSurvey.php</span></p>
      <ol class="process-steps">
        <li>Open <strong>Household Survey</strong> from the sidebar.</li>
        <li>Start a new survey (or edit an existing record).</li>
        <li>Enter survey date, purok, household head, gender, and required PRF fields (water, toilet, food security, etc.).</li>
        <li>Add family members. For children 0–5 years, enter birthday and gender so weight/height and growth results appear.</li>
        <li>Record pregnant / lactating status where applicable.</li>
        <li>Save and confirm the survey appears in the list and feeds Consolidated / BNP previews.</li>
      </ol>
      <div class="box note small">
        Household surveys drive most BNP individual indicators (e.g. exclusive breastfeeding, complementary feeding), pregnant reports, e-OPT roll-ups, and much of MELLPI live population data.
        They do <strong>not</strong> auto-fill DepEd school facility counts or Kinder–Grade 6 school weighing status (those are Process D).
      </div>
    </section>

    <section>
      <h2>7. Process C — New Assessment (registered resident)</h2>
      <p><strong>Owner:</strong> BNS &nbsp;|&nbsp; <strong>Page:</strong> <span class="path">nutritionAssess.php</span></p>
      <ol class="process-steps">
        <li>Open <strong>New Assessment</strong>.</li>
        <li>Search and select a registered child/resident (ages 0–<?= (int) $childMaxAge ?>).</li>
        <li>Enter assessment date, weight (kg), and height (cm). BMI and suggested status are computed.</li>
        <li>Save. Review under Nutrition Profiles / dashboard at-risk cards as needed.</li>
      </ol>
      <p class="small muted">Use this when linking growth data to an existing barangay resident outside (or in addition to) a household survey member row.</p>
    </section>

    <section>
      <h2>8. Process D — School &amp; other Form C1 manual fields</h2>
      <p><strong>Owner:</strong> BNS / CNC focal &nbsp;|&nbsp; <strong>Page:</strong> <span class="path">nutritionSettings.php</span> → <em>BNP Form C1 — Manual Fields</em></p>
      <div class="box note">
        These lines on BNP Form C1 (All Households) are <strong>not</strong> taken from Resident Registration or Household Survey.
        Enter figures from DepEd / Day Care / school weighing / RHU sources, then save Settings.
      </div>
      <table>
        <thead>
          <tr><th>Form C1 item</th><th>Settings field</th><th>Typical source</th></tr>
        </thead>
        <tbody>
          <tr><td>a. Day Care Centers</td><td>daycare_public / daycare_private</td><td>CSWD / Day Care inventory</td></tr>
          <tr><td>b. Elementary Schools</td><td>elementary_public / elementary_private</td><td>DepEd / barangay inventory</td></tr>
          <tr><td>17. Kindergarten enrolled</td><td>kindergarten</td><td>DepEd</td></tr>
          <tr><td>18. Grade 1 children</td><td>grade1</td><td>DepEd</td></tr>
          <tr><td>19–20. Weighed / coverage %</td><td>school_weighed / school_weighing_pct</td><td>School weighing (start of SY)</td></tr>
          <tr><td>21. Nutritional status (Kinder–G6 public)</td><td>school_sev_wasted … school_ob</td><td>School NS report</td></tr>
          <tr><td>25. Fully immunized (FIC)</td><td>fic</td><td>RHU / EPI</td></tr>
          <tr><td>Workers / stores / IP overrides</td><td>bns_count, bhw_count, midwife_count, sari_sari, ip_*</td><td>Barangay / CNC records</td></tr>
        </tbody>
      </table>
      <ol class="process-steps">
        <li>Collect source documents for the reporting period / school year.</li>
        <li>Open Settings → complete <strong>BNP Form C1 — Manual Fields</strong>.</li>
        <li>Save settings.</li>
        <li>Open BNP → All Households (C1) and verify school rows and FIC print correctly.</li>
      </ol>
      <p class="small muted"><strong>Separate school data:</strong> Until a dedicated School Nutrition module is built, treat these Settings fields as the official school-only dataset for barangay BNP C1. City-level school matrices may also be maintained in MELLPI City Profile Registration.</p>
    </section>

    <section>
      <h2>9. Process E — Barangay reports (BNP / Consolidated / Pregnant)</h2>
      <ol class="process-steps">
        <li>Open <span class="path">nutritionBnpReport.php</span> (or BNP 2026 from the hub).</li>
        <li>Select form type (All Households C1, category forms C2–C9, or Pregnant).</li>
        <li>Review on-screen totals; print via <span class="path">nutritionBnpPrint.php</span> when ready.</li>
        <li>Use Consolidated Survey report for household-level review.</li>
      </ol>
      <table>
        <thead>
          <tr><th>Form</th><th>Focus</th></tr>
        </thead>
        <tbody>
          <tr><td>C1</td><td>All households + manual school/FIC fields</td></tr>
          <tr><td>C2–C5</td><td>Preschool nutritional risk families</td></tr>
          <tr><td>C6–C7</td><td>Pregnant / related</td></tr>
          <tr><td>C8–C9</td><td>School children nutritional risk families (from HH survey children — not DepEd facility counts)</td></tr>
        </tbody>
      </table>
    </section>

    <section>
      <h2>10. Process F — MELLPI City Profile Registration</h2>
      <p><strong>Owner:</strong> City nutrition / Super Admin &nbsp;|&nbsp; <strong>Page:</strong> <span class="path">nutritionMellpiCityProfile.php</span></p>
      <ol class="process-steps">
        <li>From Nutrition Hub open <strong>MELLPI City Profile</strong>.</li>
        <li>Complete community identity and year matrices (preschool / school status, facilities, hazards, etc.).</li>
        <li>Leave blank fields that auto-fill from live survey data when the report is generated (see on-screen hints).</li>
        <li>Save profile before printing the city pack.</li>
      </ol>
    </section>

    <section>
      <h2>11. Process G — Print City Report (submission pack)</h2>
      <p><strong>Page:</strong> <span class="path">nutritionSuperPrintReport.php</span></p>
      <ol class="process-steps">
        <li>Confirm barangays have encoded surveys and Form C1 manual fields.</li>
        <li>Confirm MELLPI City Profile is current.</li>
        <li>Open <strong>Print City Report</strong> (new tab).</li>
        <li>Review MELLPI CM + BNP C1–C9 + e-OPT Plus sections.</li>
        <li>Print or Save as PDF for CNC / NNC filing.</li>
      </ol>
    </section>

    <section>
      <h2>12. Data source map (quick reference)</h2>
      <table>
        <thead>
          <tr><th>Data need</th><th>Where to encode</th><th>Appears in</th></tr>
        </thead>
        <tbody>
          <tr class="section-row"><td colspan="3">From operations / surveys</td></tr>
          <tr><td>Household profile, water/toilet, food security</td><td>Household Survey</td><td>BNP C1, Consolidated</td></tr>
          <tr><td>Preschool anthropometry &amp; status</td><td>Household Survey (members) / Assessment</td><td>BNP, e-OPT, MELLPI live</td></tr>
          <tr><td>Pregnant / lactating</td><td>Household Survey</td><td>Pregnant reports, BNP</td></tr>
          <tr><td>Exclusive BF / complementary feeding</td><td>Household Survey indicators</td><td>BNP C1 items 22–23</td></tr>
          <tr class="section-row"><td colspan="3">Manual / school (not from Registration)</td></tr>
          <tr><td>Day Care &amp; Elementary counts</td><td>Settings → BNP C1</td><td>BNP C1 a–b</td></tr>
          <tr><td>Kinder / G1 / school weighing / NS</td><td>Settings → BNP C1</td><td>BNP C1 17–21</td></tr>
          <tr><td>FIC, BNS/BHW/midwife, sari-sari</td><td>Settings → BNP C1</td><td>BNP C1</td></tr>
          <tr><td>City facility &amp; year matrices</td><td>MELLPI City Profile Registration</td><td>City MELLPI / print pack</td></tr>
        </tbody>
      </table>
    </section>

    <section>
      <h2>13. Monthly process checklist (fill &amp; file)</h2>
      <p class="small muted">Period covered: _______________ &nbsp; to &nbsp; _______________ &nbsp;|&nbsp; Barangay / City: _______________________________</p>
      <table>
        <thead>
          <tr>
            <th style="width:8%">Done</th>
            <th>Activity</th>
            <th style="width:22%">Date done</th>
            <th style="width:22%">Initials</th>
          </tr>
        </thead>
        <tbody>
          <tr><td><span class="checkbox"></span></td><td>Household surveys encoded / updated for target puroks</td><td></td><td></td></tr>
          <tr><td><span class="checkbox"></span></td><td>Child assessments recorded where required</td><td></td><td></td></tr>
          <tr><td><span class="checkbox"></span></td><td>Settings → BNP C1 school &amp; manual fields updated</td><td></td><td></td></tr>
          <tr><td><span class="checkbox"></span></td><td>BNP C1–C9 reviewed (screen or print)</td><td></td><td></td></tr>
          <tr><td><span class="checkbox"></span></td><td>Pregnant families report reviewed</td><td></td><td></td></tr>
          <tr><td><span class="checkbox"></span></td><td>MELLPI City Profile refreshed (city)</td><td></td><td></td></tr>
          <tr><td><span class="checkbox"></span></td><td>City Report printed / PDF archived</td><td></td><td></td></tr>
          <tr><td><span class="checkbox"></span></td><td>Issues logged / forwarded to CNC</td><td></td><td></td></tr>
        </tbody>
      </table>
      <div class="sign-grid">
        <div class="sign-box">
          <div class="label">Encoded by (BNS)</div>
          <div class="line">Signature over printed name / Date</div>
        </div>
        <div class="sign-box">
          <div class="label">Checked by</div>
          <div class="line">Signature over printed name / Date</div>
        </div>
        <div class="sign-box">
          <div class="label">Noted by (CNC / City)</div>
          <div class="line">Signature over printed name / Date</div>
        </div>
      </div>
    </section>

    <section>
      <h2>14. Acknowledgement</h2>
      <p>I have read this Nutrition Portal Process Form and will follow the encoding and reporting steps for my role.</p>
      <table>
        <thead>
          <tr><th style="width:35%">Name</th><th style="width:25%">Role</th><th style="width:20%">Date</th><th>Signature</th></tr>
        </thead>
        <tbody>
          <tr><td style="height:2rem"></td><td></td><td></td><td></td></tr>
          <tr><td style="height:2rem"></td><td></td><td></td><td></td></tr>
          <tr><td style="height:2rem"></td><td></td><td></td><td></td></tr>
        </tbody>
      </table>
    </section>

    <section>
      <h2>15. Quick URL list</h2>
      <table>
        <thead>
          <tr><th>Page</th><th>Path (under <span class="path">admin/</span>)</th></tr>
        </thead>
        <tbody>
          <tr><td>This Process Form (PDF)</td><td><span class="path">nutritionProcessFormPrint.php</span></td></tr>
          <tr><td>User Guide (PDF)</td><td><span class="path">nutritionHubGuidePrint.php</span></td></tr>
          <tr><td>City Hub</td><td><span class="path">nutritionSuperDashboard.php</span></td></tr>
          <tr><td>Household Survey</td><td><span class="path">nutritionHouseholdSurvey.php</span></td></tr>
          <tr><td>New Assessment</td><td><span class="path">nutritionAssess.php</span></td></tr>
          <tr><td>Settings (BNP C1 manual)</td><td><span class="path">nutritionSettings.php</span></td></tr>
          <tr><td>BNP Reports</td><td><span class="path">nutritionBnpReport.php</span></td></tr>
          <tr><td>MELLPI Registration</td><td><span class="path">nutritionMellpiCityProfile.php</span></td></tr>
          <tr><td>City Print Pack</td><td><span class="path">nutritionSuperPrintReport.php</span></td></tr>
        </tbody>
      </table>
    </section>

    <div class="footer-meta">
      <span>Form <?= htmlspecialchars($formNo, ENT_QUOTES, 'UTF-8') ?> · v<?= htmlspecialchars($docVersion, ENT_QUOTES, 'UTF-8') ?></span>
      <span>City of Valencia Nutrition Portal</span>
      <span>Page generated <?= htmlspecialchars($generatedAt, ENT_QUOTES, 'UTF-8') ?></span>
    </div>
  </div>

  <script>
  (function () {
    var pdfFilename = <?= json_encode($pdfFilename) ?>;
    var docFilename = <?= json_encode($docFilename) ?>;
    var autoDownload = <?= $autoDownload ? 'true' : 'false' ?>;
    var autoDownloadDoc = <?= $autoDownloadDoc ? 'true' : 'false' ?>;
    var btn = document.getElementById('guideDownloadPdfBtn');
    var docBtn = document.getElementById('guideDownloadDocBtn');
    var statusEl = document.getElementById('guidePdfStatus');

    function setStatus(text) {
      if (statusEl) statusEl.textContent = text || '';
    }

    function absoluteUrl(src) {
      try { return new URL(src, window.location.href).href; } catch (err) { return src; }
    }

    function collectPageStyles() {
      var chunks = [];
      document.querySelectorAll('style').forEach(function (el) { chunks.push(el.innerHTML || ''); });
      return chunks.join('\n');
    }

    function downloadWordDoc() {
      var root = document.getElementById('processPrintRoot');
      if (!root) { setStatus('Content not found.'); return; }
      setStatus('Preparing Word document…');
      if (docBtn) docBtn.disabled = true;
      var clone = root.cloneNode(true);
      clone.querySelectorAll('img').forEach(function (img) {
        var src = img.getAttribute('src') || '';
        if (src) img.setAttribute('src', absoluteUrl(src));
      });
      var html = [
        '<!DOCTYPE html>',
        '<html xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:w="urn:schemas-microsoft-com:office:word" xmlns="http://www.w3.org/TR/REC-html40">',
        '<head><meta charset="utf-8"><title>Nutrition Portal Process Form</title>',
        '<style>', collectPageStyles(),
        'body{background:#fff!important;margin:0;padding:18px;} .no-print{display:none!important;}',
        '</style></head><body>', clone.innerHTML, '</body></html>'
      ].join('\n');
      try {
        var blob = new Blob(['\ufeff', html], { type: 'application/msword' });
        var url = URL.createObjectURL(blob);
        var a = document.createElement('a');
        a.href = url;
        a.download = docFilename;
        document.body.appendChild(a);
        a.click();
        document.body.removeChild(a);
        URL.revokeObjectURL(url);
        setStatus('Word document downloaded.');
      } catch (err) {
        setStatus('Could not download Word. Use Print → Save as PDF.');
      }
      if (docBtn) docBtn.disabled = false;
    }

    function downloadPdf() {
      if (typeof window.nutritionDownloadPrintPdf !== 'function') {
        setStatus('PDF helper failed to load. Use Print → Save as PDF.');
        return;
      }
      var run = function () {
        return window.nutritionDownloadPrintPdf({
          root: document.getElementById('processPrintRoot'),
          filename: pdfFilename,
          button: btn,
          setStatus: setStatus
        });
      };
      Promise.resolve(run()).catch(function () {
        setStatus('PDF download failed. Use Print → Save as PDF.');
      });
    }

    if (btn) btn.addEventListener('click', downloadPdf);
    if (docBtn) docBtn.addEventListener('click', downloadWordDoc);
    if (autoDownload) setTimeout(downloadPdf, 400);
    if (autoDownloadDoc) setTimeout(downloadWordDoc, 400);
  })();
  </script>
</body>
</html>
