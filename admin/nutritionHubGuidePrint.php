<?php

/**
 * Nutrition Portal User Guide — printable / PDF form.
 * Keep this file updated whenever Nutrition Portal features change.
 * See §11 System update log for the maintainer checklist.
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
$childMaxAge = nutrition_child_max_age_years();
$childAgeLabel = nutrition_children_age_label();
$guideVersion = '2026.08.13b';

if (!barangay_user_can_access_nutrition_portal($con, $user_id)) {
    header('Location: dashboard.php');
    exit;
}

date_default_timezone_set('Asia/Manila');
$generatedAt = date('F j, Y g:i A');
$autoDownload = isset($_GET['download']) && (string) $_GET['download'] === '1';
$autoDownloadDoc = isset($_GET['download']) && in_array((string) $_GET['download'], ['doc', 'docx', 'word'], true);
$pdfFilename = 'Nutrition_Portal_User_Guide_Valencia_City.pdf';
$docFilename = 'Nutrition_Portal_User_Guide_Valencia_City.doc';
$shotBase = '../docs/nutrition-hub/screenshots/';

/**
 * @param string $file Screenshot filename
 * @param string $caption Figure caption
 */
$shot = static function (string $file, string $caption) use ($shotBase): void {
    $src = $shotBase . $file;
    $abs = dirname(__DIR__) . '/docs/nutrition-hub/screenshots/' . $file;
    $exists = is_file($abs);
    echo '<figure class="guide-shot' . ($exists ? '' : ' guide-shot--missing') . '">';
    if ($exists) {
        echo '<img src="' . htmlspecialchars($src, ENT_QUOTES, 'UTF-8') . '" alt="'
            . htmlspecialchars($caption, ENT_QUOTES, 'UTF-8') . '">';
    } else {
        echo '<div class="guide-shot-placeholder">Screenshot pending: <code>'
            . htmlspecialchars($file, ENT_QUOTES, 'UTF-8') . '</code></div>';
    }
    echo '<figcaption>' . htmlspecialchars($caption, ENT_QUOTES, 'UTF-8') . '</figcaption>';
    echo '</figure>';
};
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Nutrition Portal User Guide (PDF) | City of Valencia</title>
  <style>
    @import url('../assets/css/local-fonts.css');
    :root {
      --ink: #0f172a;
      --muted: #475569;
      --line: #cbd5e1;
      --accent: #166534;
      --bg: #f8fafc;
    }
    * { box-sizing: border-box; }
    body {
      margin: 0;
      font-family: 'Source Sans 3', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
      color: var(--ink);
      background: #e2e8f0;
      font-size: 11pt;
      line-height: 1.45;
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
      display: inline-block;
      font-size: .9rem;
    }
    .btn-print { background: #22c55e; color: #052e16; }
    .btn-pdf { background: #38bdf8; color: #0c4a6e; }
    .btn-doc { background: #a855f7; color: #faf5ff; }
    .btn-back { background: #334155; color: #f8fafc; }
    #guidePdfStatus { font-size: .85rem; color: #94a3b8; margin-left: .35rem; }

    #guidePrintRoot {
      width: 210mm;
      max-width: 210mm;
      margin: 1rem auto 2rem;
      background: #fff;
      padding: 14mm 12mm;
      box-shadow: 0 8px 24px rgba(15, 23, 42, .12);
    }

    .guide-cover {
      text-align: center;
      padding: 18mm 8mm 12mm;
      border-bottom: 3px solid var(--accent);
      margin-bottom: 8mm;
      page-break-after: always;
    }
    .guide-cover h1 {
      margin: 0 0 .5rem;
      font-size: 22pt;
      color: var(--accent);
      letter-spacing: .02em;
    }
    .guide-cover .subtitle {
      font-size: 13pt;
      font-weight: 600;
      margin: .35rem 0;
    }
    .guide-cover p { margin: .35rem 0; color: var(--muted); }
    .guide-cover .meta {
      margin-top: 10mm;
      font-size: 10pt;
      border-top: 1px solid var(--line);
      padding-top: 4mm;
    }

    h2 {
      margin: 7mm 0 3mm;
      font-size: 14pt;
      color: var(--accent);
      border-bottom: 2px solid var(--accent);
      padding-bottom: 2mm;
      page-break-after: avoid;
    }
    h3 {
      margin: 5mm 0 2mm;
      font-size: 12pt;
      color: #14532d;
      page-break-after: avoid;
    }
    p, li { color: var(--ink); }
    ol, ul { margin: 2mm 0 3mm; padding-left: 5.5mm; }
    li { margin-bottom: 1.2mm; }
    .muted { color: var(--muted); font-size: 10pt; }
    code, .path {
      font-family: Consolas, "Courier New", monospace;
      font-size: 9.5pt;
      background: var(--bg);
      padding: .1rem .3rem;
      border-radius: 3px;
    }

    table {
      width: 100%;
      border-collapse: collapse;
      margin: 2mm 0 4mm;
      font-size: 9.5pt;
    }
    th, td {
      border: 1px solid var(--line);
      padding: 2.2mm 2.5mm;
      text-align: left;
      vertical-align: top;
    }
    th {
      background: #ecfdf5;
      color: #14532d;
      font-weight: 700;
    }

    .guide-shot {
      margin: 3mm 0 5mm;
      page-break-inside: avoid;
    }
    .guide-shot img {
      display: block;
      width: 100%;
      max-height: 95mm;
      object-fit: contain;
      object-position: top;
      border: 1px solid var(--line);
      background: #fff;
    }
    .guide-shot-placeholder {
      border: 1px dashed #94a3b8;
      background: var(--bg);
      color: var(--muted);
      padding: 8mm 4mm;
      text-align: center;
      font-size: 10pt;
    }
    .guide-shot figcaption {
      margin-top: 1.5mm;
      font-size: 9pt;
      color: var(--muted);
      font-style: italic;
    }

    .toc ol { margin-top: 2mm; }
    .section-break { page-break-before: always; }
    .steps-box {
      background: #f0fdf4;
      border: 1px solid #bbf7d0;
      border-radius: 4px;
      padding: 3mm 4mm;
      margin: 2mm 0 4mm;
    }
    .steps-box strong { color: #14532d; }

    .footer-note {
      margin-top: 8mm;
      padding-top: 3mm;
      border-top: 1px solid var(--line);
      font-size: 9pt;
      color: var(--muted);
      text-align: center;
    }

    @media print {
      @page { size: A4 portrait; margin: 12mm 10mm; }
      html, body { width: 210mm; max-width: 210mm; background: #fff; }
      body { margin: 0; padding: 0; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
      .no-print { display: none !important; }
      #guidePrintRoot {
        width: 210mm;
        margin: 0;
        padding: 0;
        box-shadow: none;
      }
      .guide-cover { page-break-after: always; }
    }
  </style>
  <?php require __DIR__ . '/../includes/partials/report_fit_assets.php'; ?>
</head>
<body>
  <div class="no-print">
    <a class="btn btn-back" href="nutritionSuperDashboard.php">← Back to Nutrition Portal</a>
    <button type="button" class="btn-print" onclick="window.print()">Print</button>
    <button type="button" class="btn-pdf" id="guideDownloadPdfBtn">Download PDF</button>
    <button type="button" class="btn-doc" id="guideDownloadDocBtn">Download Word (.doc)</button>
    <a class="btn btn-pdf" href="?download=1">Auto PDF</a>
    <a class="btn btn-doc" href="?download=doc">Auto Word</a>
    <span id="guidePdfStatus"></span>
  </div>

  <div id="guidePrintRoot" data-report-fit="a4">
    <header class="guide-cover">
      <p class="muted">City of Valencia · Bukidnon</p>
      <h1>NUTRITION PORTAL</h1>
      <p class="subtitle">User Guide · Step-by-Step Manual</p>
      <p>City Nutrition Hub · Barangay Nutrition Profiling · BNP C1–C9 · MELLPI · e-OPT Plus</p>
      <p class="muted">Guide version <?= htmlspecialchars($guideVersion, ENT_QUOTES, 'UTF-8') ?></p>
      <div class="meta">
        <p><strong>Document type:</strong> PDF / Word (.doc) / Print Form</p>
        <p><strong>Generated:</strong> <?= htmlspecialchars($generatedAt, ENT_QUOTES, 'UTF-8') ?></p>
        <p><strong>Audience:</strong> SSA, Nutrition Super Admin (SA), Nutrition Admin (A), CNPC, BNS, City Super Admin, City Admin</p>
      </div>
    </header>

    <section class="toc">
      <h2>Table of contents</h2>
      <ol>
        <li>Overview &amp; portals</li>
        <li>Roles &amp; access</li>
        <li>Getting started — login</li>
        <li>City Nutrition Portal (Hub)</li>
        <li>Barangay Nutrition Portal</li>
        <li>BNP Template 2026 (C1–C9)</li>
        <li>City print report (MELLPI + BNP + e-OPT)</li>
        <li>End-to-end workflow</li>
        <li>Quick URL reference</li>
        <li>Screenshot checklist</li>
        <li>System update log</li>
      </ol>
    </section>

    <section>
      <h2>1. Overview &amp; portals</h2>
      <p>Valencia City uses two separate portals. Branding follows the account and the active portal:</p>
      <table>
        <thead>
          <tr><th>Portal</th><th>Brand name</th><th>Purpose</th></tr>
        </thead>
        <tbody>
          <tr>
            <td><strong>City of Valencia Portal</strong></td>
            <td>City of Valencia Portal</td>
            <td>Barangay administration (residents, officials, certificates, blotter, settings)</td>
          </tr>
          <tr>
            <td><strong>Nutrition Portal</strong> (city hub)</td>
            <td>Nutrition Portal</td>
            <td>City dashboard, MELLPI CM, city print reports, BNS accounts, barangay picker</td>
          </tr>
          <tr>
            <td><strong>Nutrition Portal</strong> (barangay)</td>
            <td>Nutrition Portal · barangay name</td>
            <td>Household surveys, child assessments (ages 0–<?= (int) $childMaxAge ?>), BNP C1–C9, pregnant reports</td>
          </tr>
        </tbody>
      </table>
      <p class="muted"><strong>Important:</strong> Nutrition-only accounts never open the City of Valencia (barangay admin) menus. Profile editing stays inside Nutrition Portal (<span class="path">nutritionAccountProfile.php</span>).</p>
    </section>

    <section>
      <h2>2. Roles &amp; access</h2>
      <table>
        <thead>
          <tr><th>Role</th><th>Staff code</th><th>Nutrition Portal</th><th>City Portal</th><th>Household surveys</th></tr>
        </thead>
        <tbody>
          <tr>
            <td><strong>SSA</strong></td>
            <td><span class="path">ssa</span></td>
            <td>Full (both hubs)</td>
            <td>Yes</td>
            <td>Add · full edit · delete · name edit</td>
          </tr>
          <tr>
            <td><strong>Nutrition Super Admin (SA)</strong></td>
            <td><span class="path">nutrition_super_admin</span></td>
            <td>Full city hub</td>
            <td><strong>No</strong></td>
            <td>Add · full edit · delete · name edit</td>
          </tr>
          <tr>
            <td><strong>Nutrition Admin (A)</strong></td>
            <td><span class="path">barangay_nutrition_scholar_admin</span></td>
            <td>Nutrition only</td>
            <td><strong>No</strong></td>
            <td><strong>Add · edit · delete</strong></td>
          </tr>
          <tr>
            <td><strong>CNPC</strong></td>
            <td><span class="path">city_nutrition_program_coordinator</span></td>
            <td>Assigned barangays</td>
            <td>No</td>
            <td><strong>Edit · delete</strong> — cannot add</td>
          </tr>
          <tr>
            <td><strong>BNS</strong></td>
            <td><span class="path">barangay_nutrition_scholar</span></td>
            <td>One assigned barangay</td>
            <td>No</td>
            <td><strong>Add only</strong> — no edit / delete</td>
          </tr>
          <tr>
            <td>Barangay Super Admin (SA)</td>
            <td><span class="path">super_admin</span></td>
            <td><strong>No</strong></td>
            <td>Full (Barangay only)</td>
            <td>Add · full edit · delete</td>
          </tr>
          <tr>
            <td>Barangay Admin (A)</td>
            <td><span class="path">admin</span></td>
            <td><strong>No</strong></td>
            <td>Full view + add (Barangay only)</td>
            <td>Add / view — <strong>no edit / delete</strong></td>
          </tr>
        </tbody>
      </table>

      <h3>Household survey actions</h3>
      <table>
        <thead><tr><th>Action</th><th>SSA / Nutrition SA</th><th>Nutrition Admin (A)</th><th>CNPC</th><th>BNS</th></tr></thead>
        <tbody>
          <tr><td>View / generate reports</td><td>Yes</td><td>Yes</td><td>Yes</td><td>Yes</td></tr>
          <tr><td>Add new survey</td><td>Yes</td><td>Yes</td><td><strong>No</strong></td><td>Yes</td></tr>
          <tr><td>Edit survey / names</td><td>Yes</td><td>Yes</td><td>Yes</td><td>No</td></tr>
          <tr><td>Delete survey</td><td>Yes</td><td>Yes</td><td>Yes</td><td>No</td></tr>
          <tr><td>Create / manage user accounts</td><td>Yes (SSA global · SA hub-scoped)</td><td><strong>No</strong></td><td><strong>No</strong></td><td><strong>No</strong></td></tr>
          <tr><td>Settings (officer name)</td><td>Yes</td><td>View only</td><td>Per role</td><td>No</td></tr>
        </tbody>
      </table>

      <h3>Account management</h3>
      <table>
        <thead><tr><th>Role</th><th>Create / manage staff accounts</th></tr></thead>
        <tbody>
          <tr><td><strong>SSA</strong></td><td>Yes — system-wide across all levels, hubs, and portals</td></tr>
          <tr><td><strong>Super Admin (SA)</strong></td><td>Yes — within assigned hub only (Barangay Hub or Nutrition Portal)</td></tr>
          <tr><td><strong>Admin (A), CNPC, BNS</strong></td><td><strong>No</strong> — Household Survey data and reports only</td></tr>
        </tbody>
      </table>
      <p class="muted">Nutrition SA may create Nutrition Admin (A), CNPC, and BNS. Nutrition Super Admin accounts are SSA-only. Barangay Hub SA manages Barangay Hub roles only (not Nutrition).</p>
    </section>

    <section class="section-break">
      <h2>3. Getting started — login</h2>
      <div class="steps-box">
        <strong>Steps</strong>
        <ol>
          <li>Open <span class="path">login.php</span> (brand: <strong>City of Valencia Portal</strong>).</li>
          <li>Sign in with the correct credentials.</li>
          <li><strong>Nutrition Super Admin</strong> (<span class="path">nutrition.superadmin</span>) lands directly on <strong>Nutrition Portal</strong> city dashboard.</li>
          <li><strong>SSA only:</strong> from City of Valencia Super Admin, click <strong>Switch to Nutrition Hub</strong>.</li>
          <li>Confirm the left sidebar shows <strong>Nutrition Portal</strong> (not City of Valencia Portal menus).</li>
        </ol>
      </div>
      <?php $shot('01-login.png', 'Figure 1 — Login page (City of Valencia Portal)'); ?>
      <?php $shot('02-super-dashboard-entry.png', 'Figure 2 — Entry to Nutrition Portal'); ?>
    </section>

    <section class="section-break">
      <h2>4. City Nutrition Portal (Hub)</h2>

      <h3>4.1 Super Admin Nutrition Dashboard</h3>
      <p class="muted">URL: <span class="path">admin/nutritionSuperDashboard.php</span></p>
      <div class="steps-box">
        <strong>Steps</strong>
        <ol>
          <li>Confirm sidebar brand is <strong>Nutrition Portal</strong>.</li>
          <li>Review <strong>City Overview</strong> cards (see table below).</li>
          <li>Use Quick Actions or the barangay table to open a barangay, MELLPI, or print reports.</li>
          <li><strong>Nutrition Admin (A):</strong> click <strong>Encode Survey</strong> on a barangay row (or <strong>Open</strong> then sidebar → Household Survey). A barangay must be selected before encoding.</li>
          <li>My Profile opens Nutrition Account Profile (stays inside Nutrition Portal).</li>
        </ol>
      </div>
      <?php $shot('03-nutrition-hub-dashboard.png', 'Figure 3 — Nutrition Portal city dashboard'); ?>
      <?php $shot('04-nutrition-hub-sidebar.png', 'Figure 4 — Nutrition Portal sidebar'); ?>

      <h3>4.2 City Overview figures</h3>
      <table>
        <thead><tr><th>Card</th><th>Meaning</th></tr></thead>
        <tbody>
          <tr><td>Barangays</td><td>Number of barangays in Valencia City</td></tr>
          <tr><td><?= htmlspecialchars($childAgeLabel, ENT_QUOTES, 'UTF-8') ?></td><td>Residents aged <strong>0–<?= (int) $childMaxAge ?></strong> (nutrition only; City of Valencia Portal still uses 0–17 for barangay children stats)</td></tr>
          <tr><td>Assessed</td><td>Children with at least one nutrition assessment</td></tr>
          <tr><td>Pending Assessment</td><td>Children not yet assessed</td></tr>
          <tr><td>At-Risk Cases</td><td>Sum of latest assessment statuses that are not Normal (see below)</td></tr>
          <tr><td>Assessments This Month</td><td>Assessments recorded in the current month</td></tr>
          <tr><td>Household Surveys</td><td>Registered household nutrition surveys</td></tr>
          <tr><td>Pregnant</td><td>All pregnant individuals from household surveys (heads + members)</td></tr>
          <tr><td>Teenage Pregnant</td><td>Pregnant individuals marked status <strong>Teenage</strong> (BNP column B)</td></tr>
        </tbody>
      </table>

      <h3>4.3 At-Risk Cases categories</h3>
      <p>At-Risk Cases come from <strong>child nutrition assessments</strong> (not from pregnant survey flags). The total is the sum of:</p>
      <ol>
        <li>Underweight</li>
        <li>Wasted</li>
        <li>Severely Wasted</li>
        <li>Stunted</li>
        <li>Overweight</li>
        <li>Obese</li>
      </ol>
      <p class="muted">Pregnant / Teenage Pregnant are separate cards from household surveys.</p>

      <table>
        <thead><tr><th>Menu item</th><th>Action</th></tr></thead>
        <tbody>
          <tr><td>Super Admin Dashboard</td><td>City overview</td></tr>
          <tr><td>Select Barangay</td><td>Open nutrition barangay picker</td></tr>
          <tr><td>MELLPI City Profile</td><td>Register MELLPI PRO FORM CM</td></tr>
          <tr><td>Print City Report</td><td>MELLPI + BNP C1–C9 + e-OPT Plus</td></tr>
          <tr><td>User Guide (PDF)</td><td>This printable manual (keep updated when the system changes)</td></tr>
          <tr><td>Users → Nutrition SA / Admin (A) / CNPC / BNS</td><td>Nutrition staff accounts (see §2)</td></tr>
          <tr><td>My Profile</td><td>Nutrition Account Profile only</td></tr>
        </tbody>
      </table>

      <h3>4.4 Select a barangay (Nutrition picker)</h3>
      <p class="muted">URL: <span class="path">barangayHub.php?picker=1&amp;system=nutrition&amp;view=picker</span></p>
      <div class="steps-box">
        <strong>Steps</strong>
        <ol>
          <li>Sidebar → <strong>Select Barangay</strong> (or Quick Action).</li>
          <li>Use search and filters: All · With Surveys · Has Pending · At-Risk · Teenage Pregnant · No Surveys Yet.</li>
          <li>Each card shows children, assessed, surveys, teenage pregnant, and coverage %.</li>
          <li>Click <strong>Open Nutrition Dashboard</strong> for that barangay.</li>
        </ol>
      </div>
      <p class="muted">Nutrition Super Admin does not see the City of Valencia Portal switch on this page.</p>
      <?php $shot('05-select-barangay.png', 'Figure 5 — Nutrition barangay picker'); ?>

      <h3>4.5 MELLPI City Profile</h3>
      <div class="steps-box">
        <strong>Steps</strong>
        <ol>
          <li>Hub sidebar → <strong>MELLPI City Profile</strong>.</li>
          <li>Complete community, population, preschool/school status, pregnant status, BNS, hazards, land use.</li>
          <li>Live fields may autofill from surveys; fill remaining blanks.</li>
          <li>Save, then open City Report to preview.</li>
        </ol>
      </div>
      <?php $shot('06-mellpi-city-profile.png', 'Figure 6 — MELLPI City Profile'); ?>

      <h3>4.6 City pregnant families &amp; nutrition staff accounts</h3>
      <div class="steps-box">
        <strong>Pregnant families</strong>
        <ol>
          <li>Dashboard → <strong>Pregnant Families</strong> / Teenage Pregnant card.</li>
          <li>Print or download the city-wide pregnant families profile (BNP columns A–E, including Teenage).</li>
        </ol>
        <strong>Nutrition staff accounts (SSA / Nutrition SA only)</strong>
        <ol>
          <li>Users → <strong>Nutrition Super Admin (SA)</strong> · <strong>Nutrition Admin (A)</strong> · <strong>CNPC Accounts</strong> · <strong>BNS Accounts</strong>.</li>
          <li>Create or edit accounts; assign barangay(s) for BNS and CNPC.</li>
          <li>Nutrition SA manages nutrition roles only (not Barangay Hub staff). Nutrition SA peers are SSA-only.</li>
          <li><strong>Admin (A), CNPC, and BNS cannot create user accounts</strong> — survey data and reports only.</li>
          <li>Share role limits from §2 with each new account.</li>
        </ol>
      </div>
      <?php $shot('11-pregnant-families-city.png', 'Figure 7 — City pregnant families print'); ?>
      <?php $shot('12-bns-accounts.png', 'Figure 8 — BNS accounts'); ?>
    </section>

    <section class="section-break">
      <h2>5. Barangay Nutrition Portal</h2>

      <h3>5.1 Dashboard</h3>
      <div class="steps-box">
        <strong>Steps</strong>
        <ol>
          <li>Open <strong>Dashboard</strong> (sidebar brand: Nutrition Portal).</li>
          <li>Review overview cards: <?= htmlspecialchars($childAgeLabel, ENT_QUOTES, 'UTF-8') ?>, Assessed, Pending, At-Risk, This Month, Pregnant, Teenage Pregnant.</li>
          <li>Use Quick Actions: Household Survey, New Assessment, Consolidated Report, Profiles.</li>
        </ol>
      </div>
      <?php $shot('13-barangay-dashboard.png', 'Figure 9 — Barangay nutrition dashboard'); ?>
      <?php $shot('14-barangay-sidebar.png', 'Figure 10 — Barangay nutrition sidebar'); ?>

      <h3>5.1a Barangay sidebar menu</h3>
      <table>
        <thead><tr><th>Section</th><th>Item</th><th>Notes</th></tr></thead>
        <tbody>
          <tr><td>Overview</td><td>Dashboard</td><td>—</td></tr>
          <tr><td>Data Entry</td><td>Household Survey</td><td>Shown when role can add (hidden for CNPC). Admin A / SA / BNS / SSA</td></tr>
          <tr><td>Reports</td><td>BNP Reports 2026</td><td>—</td></tr>
          <tr><td>Reports</td><td>e-OPT Plus Tool</td><td>On-screen + print pack</td></tr>
          <tr><td>Reports</td><td>MELLPI PRO Form</td><td>Per-barangay profile</td></tr>
          <tr><td>Reports</td><td>Consolidated Report</td><td>Name edit / full edit per §2</td></tr>
          <tr><td>Reports</td><td>Families with Pregnant, Nutrition Profiles, Generate Report</td><td>—</td></tr>
          <tr><td>Help</td><td>User Guide (PDF)</td><td>This manual — <span class="path">nutritionHubGuidePrint.php</span></td></tr>
          <tr><td>Help</td><td>Process Form (PDF)</td><td>SOP checklist — <span class="path">nutritionProcessFormPrint.php</span></td></tr>
          <tr><td>Account</td><td>Account Profile</td><td>—</td></tr>
          <tr><td>Account</td><td>Settings</td><td>Hidden for BNS and Nutrition Admin (A)</td></tr>
          <tr><td>Switch</td><td>All Barangays, Switch Barangay, City of Valencia Portal, Logout</td><td>Nutrition SA / Admin A / CNPC / SSA: All Barangays + Switch Barangay. City Portal hidden for nutrition-only roles</td></tr>
        </tbody>
      </table>

      <h3>5.2 Household Survey (primary data entry)</h3>
      <p><strong>Who can do what:</strong> <strong>Add:</strong> SSA, Nutrition SA, Nutrition Admin (A), BNS. <strong>Edit / delete:</strong> SSA, Nutrition SA, Nutrition Admin (A), CNPC. <strong>CNPC cannot add</strong> new surveys (no Encode Survey / sidebar Household Survey). Full edit via Consolidated Report → green <strong>Edit</strong> or <span class="path">nutritionHouseholdSurvey.php?edit=…</span>.</p>
      <p>Include preschool/school-age children (weight, height, status) and pregnant/lactating flags. Pregnant nutrition status options: Normal, <strong>Teenage</strong>, Underweight, Overweight, Old Age. This data drives BNP, e-OPT, pregnant reports, and MELLPI live fields.</p>
      <div class="steps-box">
        <strong>Steps (add: BNS / SSA / Nutrition SA / Nutrition Admin A · edit: + CNPC)</strong>
        <ol>
          <li><strong>To add:</strong> Super Dashboard → <strong>Encode Survey</strong> (or sidebar → <strong>Household Survey</strong>). BNS: sidebar → <strong>Household Survey</strong>. CNPC skips add — open Consolidated Report to edit.</li>
          <li>Add a new survey (or edit existing if you have edit rights).</li>
          <li>Enter household head (or search/prefill resident).</li>
          <li>Set purok (number and/or letters, e.g. 1, 1A, A), 4Ps, water/toilet, food security, PRF fields.</li>
          <li>Add family members: children + pregnant/lactating as needed (mark Teenage when applicable).</li>
          <li>Save and confirm it appears in the list.</li>
        </ol>
      </div>
      <?php $shot('15-household-survey-list.png', 'Figure 11 — Household survey list'); ?>
      <?php $shot('16-household-survey-form.png', 'Figure 12 — Household survey form'); ?>

      <h3>5.3 Consolidated Report (view, edit names, full edit)</h3>
      <p class="muted">URL: <span class="path">nutritionBarangaySurvey.php</span></p>
      <div class="steps-box">
        <ol>
          <li>Sidebar → <strong>Consolidated Report</strong>.</li>
          <li>Filter by purok/date if needed.</li>
          <li><strong>SSA / Nutrition SA / Nutrition Admin (A) / CNPC:</strong> green <strong>Edit</strong> to reopen full survey form, or use name-edit tools; may <strong>delete</strong> surveys.</li>
          <li><strong>BNS:</strong> view / reports only on this page (no edit / delete).</li>
          <li>Print or review all household surveys for the barangay.</li>
        </ol>
      </div>
      <?php $shot('20-consolidated-report.png', 'Figure 13 — Consolidated report'); ?>

      <h3>5.4 MELLPI PRO Form (barangay)</h3>
      <p class="muted">URL: <span class="path">nutritionMellpiBarangayProfile.php</span></p>
      <div class="steps-box">
        <ol>
          <li>Sidebar → <strong>MELLPI PRO Form</strong>.</li>
          <li>Complete barangay MELLPI profile sections and save.</li>
          <li>Use with BNP and e-OPT barangay reporting.</li>
        </ol>
      </div>

      <h3>5.5 e-OPT Plus Tool</h3>
      <p class="muted">On-screen: <span class="path">nutritionBnpReport.php?type=eopt</span> · Print: <span class="path">nutritionEoptPrint.php</span></p>
      <div class="steps-box">
        <ol>
          <li>Sidebar → <strong>e-OPT Plus Tool</strong>.</li>
          <li>Review Nut_StatusTool, Form 1A/1B summaries, at-risk lists, monitoring tables.</li>
          <li>Open <span class="path">nutritionEoptPrint.php</span> for the full barangay print pack.</li>
          <li><strong>Form 1B</strong> prints on <strong>A4 landscape</strong> (age bands, 0–59 Prev, F1K, IP children).</li>
        </ol>
      </div>

      <h3>5.6 New Assessment</h3>
      <p>Child assessments are for residents aged <strong>0–<?= (int) $childMaxAge ?></strong> years (Nutrition Portal only).</p>
      <div class="steps-box">
        <ol>
          <li>Sidebar → <strong>New Assessment</strong>.</li>
          <li>Search child (0–<?= (int) $childMaxAge ?>) → enter date, weight, height → save.</li>
          <li>Verify under Nutrition Profiles / At-Risk filters.</li>
        </ol>
      </div>
      <?php $shot('17-new-assessment.png', 'Figure 14 — New assessment'); ?>

      <h3>5.7 Reports, profiles, settings</h3>
      <div class="steps-box">
        <ol>
          <li><strong>Families with Pregnant</strong> — barangay C7-style report (includes Teenage column B); Print / Download PDF.</li>
          <li><strong>Nutrition Profiles</strong> — browse by status.</li>
          <li><strong>Settings</strong> — officer/BNS name (hidden for BNS and Nutrition Admin A).</li>
          <li><strong>Account Profile</strong> — nutrition user details.</li>
          <li><strong>Process Form (PDF)</strong> — SOP checklist under Help.</li>
        </ol>
      </div>
      <?php $shot('21-pregnant-families-brgy.png', 'Figure 15 — Barangay pregnant families'); ?>
      <?php $shot('22-nutrition-profiles.png', 'Figure 16 — Nutrition profiles'); ?>
      <?php $shot('23-settings.png', 'Figure 17 — Settings'); ?>
    </section>

    <section class="section-break">
      <h2>6. BNP Template 2026 (C1–C9)</h2>
      <p class="muted">URL: <span class="path">nutritionBnpReport.php</span> · Print: <span class="path">nutritionBnpPrint.php</span></p>
      <div class="steps-box">
        <ol>
          <li>Sidebar → <strong>BNP Reports 2026</strong>.</li>
          <li>Choose form type C1–C9.</li>
          <li>Optional filters: purok, date range, mode.</li>
          <li>Click <strong>Print Form</strong> or <strong>Download PDF</strong>.</li>
        </ol>
      </div>
      <?php $shot('18-bnp-reports.png', 'Figure 18 — BNP Reports UI'); ?>
      <?php $shot('19-bnp-print.png', 'Figure 19 — BNP print form'); ?>

      <table>
        <thead><tr><th>Form</th><th>Key</th><th>Title</th></tr></thead>
        <tbody>
          <tr><td>C1</td><td>all_hh</td><td>All Households</td></tr>
          <tr><td>C2</td><td>uw_suw_ps</td><td>Families with UW / SUW preschool</td></tr>
          <tr><td>C3</td><td>st_sst_ps</td><td>Families with Stunted / Severely Stunted preschool</td></tr>
          <tr><td>C4</td><td>w_sw_ps</td><td>Families with Wasted / Severely Wasted preschool</td></tr>
          <tr><td>C5</td><td>ow_ob_ps</td><td>Families with Overweight / Obese preschool</td></tr>
          <tr><td>C6</td><td>lactating</td><td>Families with Lactating</td></tr>
          <tr><td>C7</td><td>pregnant</td><td>Families with Pregnant (A Normal · B Teenage · C Underweight · D Overweight · E Others)</td></tr>
          <tr><td>C8</td><td>w_sw_sc</td><td>Families with Wasted / Severely Wasted school children</td></tr>
          <tr><td>C9</td><td>ow_ob_sc</td><td>Families with Overweight / Obese school children</td></tr>
        </tbody>
      </table>
      <p class="muted">Barangay signatories: BNS (prepared), Midwife (checked), Punong Barangay (noted). City: City Nutrition Head and City Mayor / CNC Chairperson.</p>
      <p class="muted"><strong>C1 — Total Actual Population</strong> comes from <strong>registered household surveys</strong> (household member counts), not residence-status alone. Complete household surveys so C1 reflects true household size.</p>
    </section>

    <section class="section-break">
      <h2>7. City print report (MELLPI + BNP + e-OPT)</h2>
      <p class="muted">URL: <span class="path">nutritionSuperPrintReport.php</span></p>
      <div class="steps-box">
        <ol>
          <li>Hub → <strong>Print City Report</strong>.</li>
          <li>Review sections in order: Cover → MELLPI → BNP C1–C9 → e-OPT Plus → city signatures.</li>
          <li>Most sections are A4 portrait; <strong>e-OPT Form 1B uses A4 landscape</strong> — use browser Print for best results.</li>
          <li>Use browser Print or <strong>Download PDF</strong> / <span class="path">?download=1</span>.</li>
        </ol>
      </div>
      <p class="muted">e-OPT measured preschooler counts (0–59 months with OPT data) may differ from BNP C1 total household population.</p>
      <?php $shot('07-city-print-report-cover.png', 'Figure 20 — City report cover'); ?>
      <?php $shot('08-city-print-mellpi.png', 'Figure 21 — City report MELLPI'); ?>
      <?php $shot('09-city-print-bnp.png', 'Figure 22 — City report BNP'); ?>
      <?php $shot('10-city-print-eopt.png', 'Figure 23 — City report e-OPT'); ?>
    </section>

    <section class="section-break">
      <h2>8. End-to-end workflow</h2>
      <div class="steps-box">
        <strong>Recommended order</strong>
        <ol>
          <li>Create nutrition staff: BNS, CNPC, Nutrition Admin (A) as needed (SSA / Nutrition SA).</li>
          <li>Per barangay: encode household surveys (BNS / Nutrition Admin A / SA) with children and pregnant/lactating data.</li>
          <li>Complete <strong>MELLPI PRO Form</strong> per barangay and <strong>MELLPI City Profile</strong>.</li>
          <li>Assess children aged 0–<?= (int) $childMaxAge ?> as needed.</li>
          <li>Review BNP C1–C9, e-OPT Plus, and pregnant reports.</li>
          <li>CNPC / Nutrition Admin (A) / SA: edit or delete surveys on Consolidated Report as needed. BNS: add-only (no edit/delete).</li>
          <li>Print <strong>City Report</strong> (MELLPI + BNP + e-OPT) for CNC / NNC.</li>
        </ol>
      </div>
      <p><strong>Flow:</strong> Login → Nutrition Portal Hub → Select Barangay → Household Survey → Assessments → BNP / Consolidated → MELLPI → City Print Report.</p>
    </section>

    <section>
      <h2>9. Quick URL reference</h2>
      <table>
        <thead><tr><th>Page</th><th>Path (under admin/)</th></tr></thead>
        <tbody>
          <tr><td>Nutrition Portal Hub</td><td>nutritionSuperDashboard.php</td></tr>
          <tr><td>This PDF guide</td><td>nutritionHubGuidePrint.php</td></tr>
          <tr><td>Nutrition barangay picker</td><td>barangayHub.php?picker=1&amp;system=nutrition&amp;view=picker</td></tr>
          <tr><td>MELLPI</td><td>nutritionMellpiCityProfile.php</td></tr>
          <tr><td>City print</td><td>nutritionSuperPrintReport.php</td></tr>
          <tr><td>City pregnant</td><td>nutritionSuperPregnantFamiliesPrint.php</td></tr>
          <tr><td>Nutrition account profile</td><td>nutritionAccountProfile.php</td></tr>
          <tr><td>Barangay nutrition dashboard</td><td>nutritionDashboard.php</td></tr>
          <tr><td>Household survey</td><td>nutritionHouseholdSurvey.php</td></tr>
          <tr><td>New assessment (0–<?= (int) $childMaxAge ?>)</td><td>nutritionAssess.php</td></tr>
          <tr><td>Consolidated report</td><td>nutritionBarangaySurvey.php</td></tr>
          <tr><td>MELLPI barangay</td><td>nutritionMellpiBarangayProfile.php</td></tr>
          <tr><td>e-OPT on-screen</td><td>nutritionBnpReport.php?type=eopt</td></tr>
          <tr><td>e-OPT print (barangay)</td><td>nutritionEoptPrint.php</td></tr>
          <tr><td>Process Form (PDF)</td><td>nutritionProcessFormPrint.php</td></tr>
          <tr><td>Pregnant (barangay)</td><td>nutritionBnpReport.php?type=pregnant</td></tr>
          <tr><td>Profiles</td><td>nutritionProfiles.php</td></tr>
          <tr><td>Settings</td><td>nutritionSettings.php</td></tr>
          <tr><td>Markdown source</td><td>../docs/NUTRITION_HUB.md</td></tr>
        </tbody>
      </table>
    </section>

    <section>
      <h2>10. Screenshot checklist</h2>
      <p class="muted">Save PNGs into <span class="path">docs/nutrition-hub/screenshots/</span> using these names. Missing files show as placeholders above. Recapture after major UI updates.</p>
      <table>
        <thead><tr><th>#</th><th>Filename</th><th>Capture target</th></tr></thead>
        <tbody>
          <tr><td>01</td><td>01-login.png</td><td>Login (City of Valencia Portal)</td></tr>
          <tr><td>02</td><td>02-super-dashboard-entry.png</td><td>Entry to Nutrition Portal</td></tr>
          <tr><td>03</td><td>03-nutrition-hub-dashboard.png</td><td>Hub dashboard (include Pregnant / Teenage cards)</td></tr>
          <tr><td>04</td><td>04-nutrition-hub-sidebar.png</td><td>Nutrition Portal sidebar</td></tr>
          <tr><td>05</td><td>05-select-barangay.png</td><td>Nutrition picker with filters</td></tr>
          <tr><td>06</td><td>06-mellpi-city-profile.png</td><td>MELLPI form</td></tr>
          <tr><td>07–10</td><td>07…10 city-print-*.png</td><td>City print sections</td></tr>
          <tr><td>11–12</td><td>11…12</td><td>City pregnant / BNS accounts</td></tr>
          <tr><td>13–23</td><td>13…23</td><td>Barangay Nutrition Portal pages</td></tr>
        </tbody>
      </table>
    </section>

    <section class="section-break">
      <h2>11. System update log</h2>
      <p>Keep this guide in sync whenever Nutrition Portal features change. Current guide version: <strong><?= htmlspecialchars($guideVersion, ENT_QUOTES, 'UTF-8') ?></strong>.</p>
      <table>
        <thead><tr><th>Update</th><th>Guide sections to revise</th></tr></thead>
        <tbody>
          <tr><td>Portal branding (City of Valencia Portal / Nutrition Portal)</td><td>§1, §3, cover, screenshots 01–05</td></tr>
          <tr><td>Nutrition Super Admin (nutrition-only access)</td><td>§2, §3, §4.1</td></tr>
          <tr><td>Nutrition Admin (A) — add · edit · delete surveys</td><td>§2, §4.1, §5.2–5.3, §8</td></tr>
          <tr><td>Nutrition Admin (A) Super Dashboard → Encode Survey</td><td>§4.1, §5.2</td></tr>
          <tr><td>CNPC — edit · delete only (no add)</td><td>§2, §5.2–5.3, §8</td></tr>
          <tr><td>BNS — add only (no edit / delete)</td><td>§2, §5.2–5.3, §8</td></tr>
          <tr><td>CNPC role + assigned barangays</td><td>§2, §4.6, §5</td></tr>
          <tr><td>Children age 0–<?= (int) $childMaxAge ?> (nutrition only)</td><td>§4.2, §5.1, §5.6, §8</td></tr>
          <tr><td>Pregnant &amp; Teenage Pregnant city cards</td><td>§4.2, §4.6, §5.1</td></tr>
          <tr><td>At-Risk assessment categories</td><td>§4.3</td></tr>
          <tr><td>Nutrition barangay picker filters / coverage</td><td>§4.4, screenshot 05</td></tr>
          <tr><td>Account Profile inside Nutrition Portal</td><td>§1, §4.1, URL reference</td></tr>
          <tr><td>MELLPI barangay + e-OPT sidebar items</td><td>§5.4–5.5, URLs</td></tr>
          <tr><td>Full household survey edit (SSA / Nutrition SA)</td><td>§2, §5.2–5.3</td></tr>
          <tr><td>BNP C1 actual population from surveys</td><td>§6</td></tr>
          <tr><td>e-OPT Form 1B landscape + Nut_StatusTool columns</td><td>§5.5, §7, screenshots 07–10</td></tr>
          <tr><td>BNP / MELLPI / e-OPT print layout</td><td>§6, §7, screenshots 07–10, 18–19</td></tr>
        </tbody>
      </table>
      <div class="steps-box">
        <strong>Maintainer checklist after any Nutrition update</strong>
        <ol>
          <li>Update <span class="path">admin/nutritionHubGuidePrint.php</span> (this file).</li>
          <li>Update <span class="path">docs/NUTRITION_HUB.md</span> if present.</li>
          <li>Recapture affected screenshots under <span class="path">docs/nutrition-hub/screenshots/</span>.</li>
          <li>Bump the guide version on the cover.</li>
          <li>Print / Download PDF once to verify no broken sections.</li>
        </ol>
      </div>
      <p class="footer-note">
        City of Valencia · Nutrition Portal — User Guide<br>
        Source: docs/NUTRITION_HUB.md · Screenshots: docs/nutrition-hub/screenshots/ · Version <?= htmlspecialchars($guideVersion, ENT_QUOTES, 'UTF-8') ?>
      </p>
    </section>
  </div>

  <script src="../assets/plugins/jsPDF/html2canvas.min.js"></script>
  <script src="../assets/plugins/jsPDF/jspdf.umd.min.js"></script>
  <script src="../assets/js/nutrition-print-pdf.js?v=20260730a"></script>
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
      try {
        return new URL(src, window.location.href).href;
      } catch (err) {
        return src;
      }
    }

    function collectPageStyles() {
      var chunks = [];
      document.querySelectorAll('style').forEach(function (el) {
        chunks.push(el.innerHTML || '');
      });
      return chunks.join('\n');
    }

    function downloadWordDoc() {
      var root = document.getElementById('guidePrintRoot');
      if (!root) {
        setStatus('Guide content not found.');
        return;
      }

      setStatus('Preparing Word document…');
      if (docBtn) docBtn.disabled = true;

      var clone = root.cloneNode(true);
      clone.querySelectorAll('img').forEach(function (img) {
        var src = img.getAttribute('src') || '';
        if (src) img.setAttribute('src', absoluteUrl(src));
      });

      var styles = collectPageStyles();
      var html = [
        '<!DOCTYPE html>',
        '<html xmlns:o="urn:schemas-microsoft-com:office:office"',
        ' xmlns:w="urn:schemas-microsoft-com:office:word"',
        ' xmlns="http://www.w3.org/TR/REC-html40">',
        '<head>',
        '<meta charset="utf-8">',
        '<meta http-equiv="Content-Type" content="text/html; charset=utf-8">',
        '<title>Nutrition Portal User Guide · City of Valencia</title>',
        '<!--[if gte mso 9]><xml><w:WordDocument><w:View>Print</w:View><w:Zoom>100</w:Zoom></w:WordDocument></xml><![endif]-->',
        '<style>',
        styles,
        'body { background: #fff !important; margin: 0; padding: 18px; }',
        '.report-viewport, .report-scale-wrap { transform: none !important; width: auto !important; height: auto !important; }',
        '#guidePrintRoot, [data-report-fit] { box-shadow: none !important; margin: 0 !important; max-width: none !important; width: auto !important; }',
        '.no-print { display: none !important; }',
        'img { max-width: 100%; height: auto; }',
        '</style>',
        '</head>',
        '<body>',
        clone.innerHTML,
        '</body>',
        '</html>'
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
        console.error(err);
        setStatus('Could not download Word file. Try Print → Save as PDF.');
      }

      if (docBtn) docBtn.disabled = false;
    }

    function downloadPdf() {
      if (typeof window.nutritionDownloadPrintPdf !== 'function') {
        setStatus('PDF helper failed to load. Use Print → Save as PDF instead.');
        return;
      }
      var run = function () {
        return window.nutritionDownloadPrintPdf({
          root: document.getElementById('guidePrintRoot'),
          filename: pdfFilename,
          button: btn,
          setStatus: setStatus,
          scale: 1
        });
      };
      if (window.barangayReportFit && typeof window.barangayReportFit.withPrintLayout === 'function') {
        window.barangayReportFit.withPrintLayout(run);
      } else {
        run();
      }
    }

    if (btn) btn.addEventListener('click', downloadPdf);
    if (docBtn) docBtn.addEventListener('click', downloadWordDoc);

    if (autoDownload) {
      window.addEventListener('load', function () {
        setTimeout(downloadPdf, 500);
      });
    }
    if (autoDownloadDoc) {
      window.addEventListener('load', function () {
        setTimeout(downloadWordDoc, 600);
      });
    }
  })();
  </script>
</body>
</html>
