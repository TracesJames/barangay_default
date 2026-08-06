<?php

include_once '../connection.php';
include_once '../includes/auth_admin.php';
require_once '../includes/partials/nutrition_init.php';

$activePage = 'bnp_report';
$nutritionPageTitle = 'BNP Reports 2026';

$typeKey = trim((string) ($_GET['type'] ?? 'all_hh'));
$meta = nutrition_bnp_resolve_type($typeKey);
if ($meta === null) {
    $typeKey = 'all_hh';
    $meta = nutrition_bnp_resolve_type($typeKey);
}

$filters = [
    'purok' => trim((string) ($_GET['purok'] ?? '')),
    'date_from' => trim((string) ($_GET['date_from'] ?? '')),
    'date_to' => trim((string) ($_GET['date_to'] ?? '')),
];
$reportMode = nutrition_bnp_normalize_mode($_GET['mode'] ?? 'consolidated');

$bnpReport = nutrition_bnp_build_report($con, (string) $barangay_id, $typeKey, $filters);
$barangayName = (string) $barangay;
$isCityWide = false;
$bnsName = trim((string) ($bnpReport['bns_name'] ?? ''));
if ($bnsName === '') {
    $bnsName = trim((string) ($nutritionSettings['nutrition_officer'] ?? ''));
}
if ($bnsName === '') {
    $bnsAccounts = nutrition_bns_accounts_by_barangay($con);
    $assignedBns = $bnsAccounts[(string) $barangay_id] ?? null;
    if ($assignedBns) {
        $bnsName = trim((string) ($assignedBns['display_name'] ?? ''));
    }
}
$bnpReport['bns_name'] = $bnsName;
$calendarYear = (int) ($bnpReport['calendar_year'] ?? date('Y'));
$punongBarangayName = barangay_punong_barangay_name($con, (string) $barangay_id, (string) $barangay);
$purokOptions = $bnpReport['purok_options'] ?? nutrition_list_household_puroks($con, (string) $barangay_id);
$types = nutrition_bnp_report_types();

$printQuery = http_build_query(array_filter([
    'type' => $typeKey,
    'mode' => $reportMode,
    'purok' => $filters['purok'],
    'date_from' => $filters['date_from'],
    'date_to' => $filters['date_to'],
    'year' => (string) $calendarYear,
]));

require __DIR__ . '/../includes/partials/nutrition_layout_start.php';
?>
        <?php
        $nutritionPageIcon = 'fa-file-alt';
        $nutritionPageHeading = 'BNP Template 2026 Reports';
        $nutritionPageDescription = 'Official Barangay Nutrition Profile forms (C1–C9) for ' . $barangay . '.';
        $printHref = 'nutritionBnpPrint.php' . ($printQuery !== '' ? '?' . $printQuery : '');
        $downloadHref = 'nutritionBnpPrint.php?' . http_build_query(array_filter([
            'type' => $typeKey,
            'mode' => $reportMode,
            'purok' => $filters['purok'],
            'date_from' => $filters['date_from'],
            'date_to' => $filters['date_to'],
            'year' => (string) $calendarYear,
            'download' => '1',
        ]));
        $nutritionPageActions = '
            <a href="' . barangay_h($printHref) . '" target="_blank" class="btn btn-success btn-sm">
              <i class="fas fa-print mr-1"></i> Print Form
            </a>
            <a href="' . barangay_h($downloadHref) . '" target="_blank" class="btn btn-primary btn-sm ml-1">
              <i class="fas fa-file-pdf mr-1"></i> Download PDF
            </a>';
        require __DIR__ . '/../includes/partials/nutrition_page_header.php';
        ?>

        <div class="card nutrition-panel mb-3">
          <div class="card-body">
            <div class="d-flex flex-wrap gap-2 mb-3">
              <?php foreach ($types as $key => $type) :
                  $href = 'nutritionBnpReport.php?' . http_build_query([
                      'type' => $key,
                      'mode' => $reportMode,
                      'purok' => $filters['purok'],
                      'date_from' => $filters['date_from'],
                      'date_to' => $filters['date_to'],
                  ]);
                  $active = $key === $typeKey;
                  ?>
              <a href="<?= barangay_h($href) ?>" class="btn btn-sm <?= $active ? 'btn-success' : 'btn-outline-light' ?>">
                <?= barangay_h((string) $type['form']) ?> · <?= barangay_h((string) $type['sheet']) ?>
              </a>
              <?php endforeach; ?>
            </div>

            <form method="get" class="form-row align-items-end">
              <input type="hidden" name="type" value="<?= barangay_h($typeKey) ?>">
              <div class="form-group col-md-2 mb-2">
                <label for="purok">Purok</label>
                <select class="form-control" id="purok" name="purok">
                  <option value="">All puroks</option>
                  <?php foreach ($purokOptions as $purok) : ?>
                  <option value="<?= barangay_h((string) $purok) ?>" <?= $filters['purok'] === (string) $purok ? 'selected' : '' ?>><?= barangay_h((string) $purok) ?></option>
                  <?php endforeach; ?>
                </select>
              </div>
              <div class="form-group col-md-2 mb-2">
                <label for="date_from">From</label>
                <input type="date" class="form-control" id="date_from" name="date_from" value="<?= barangay_h($filters['date_from']) ?>">
              </div>
              <div class="form-group col-md-2 mb-2">
                <label for="date_to">To</label>
                <input type="date" class="form-control" id="date_to" name="date_to" value="<?= barangay_h($filters['date_to']) ?>">
              </div>
              <?php require __DIR__ . '/../includes/partials/nutrition_bnp_mode_toggle.php'; ?>
              <div class="form-group col-md-3 mb-2">
                <button type="submit" class="btn btn-success btn-block"><i class="fas fa-filter mr-1"></i> Apply</button>
              </div>
            </form>
          </div>
        </div>

        <div class="card nutrition-panel">
          <div class="card-body">
            <style>
              .bnp-sheet {
                background:#fff;
                color:#111;
                border-radius:10px;
                padding:1.25rem 1.35rem 1.5rem;
                box-shadow:0 0 0 1px rgba(15,23,42,.08), 0 12px 28px rgba(0,0,0,.18);
              }
              .bnp-header-banner {
                display:grid;
                gap:1rem;
                align-items:center;
                margin:0 0 1rem;
                padding-bottom:.85rem;
                border-bottom:2px solid #166534;
              }
              .bnp-header-banner--city {
                grid-template-columns:minmax(110px,140px) minmax(0,1fr) minmax(110px,140px);
              }
              .bnp-header-banner--barangay {
                grid-template-columns:minmax(110px,140px) minmax(0,1fr) minmax(180px,240px);
              }
              .bnp-logo-side { display:flex; align-items:center; gap:.75rem; }
              .bnp-logo-side-left { justify-content:flex-start; }
              .bnp-logo-side-right { justify-content:flex-end; }
              .bnp-header-center { text-align:center; min-width:0; }
              .bnp-logo-cell { text-align:center; }
              .bnp-logo-circle {
                width:88px;
                height:88px;
                border-radius:50%;
                overflow:hidden;
                display:inline-flex;
                align-items:center;
                justify-content:center;
                background:transparent;
                vertical-align:top;
              }
              .bnp-logo-img {
                width:100%;
                height:100%;
                object-fit:contain;
                object-position:center;
                background:transparent;
                border:0;
                display:block;
              }
              .bnp-logo-caption {
                margin-top:.35rem;
                font-size:.68rem;
                font-weight:700;
                color:#14532d;
                line-height:1.2;
              }
              .bnp-form,
              .bnp-pregnant-report { color:#111; background:transparent; }
              .bnp-form-code { font-size:.85rem; color:#555; margin-bottom:.25rem; }
              .bnp-title { font-size:1.35rem; font-weight:800; letter-spacing:.04em; text-align:center; color:#111; }
              .bnp-subtitle,.bnp-cy { text-align:center; font-weight:700; color:#111; }
              .bnp-focus { text-align:center; font-weight:800; text-decoration:underline; margin:.4rem 0 .35rem; color:#111; }
              .bnp-mode-title { text-align:center; font-weight:800; letter-spacing:.06em; color:#b91c1c; margin:0 0 .5rem; font-size:1.05rem; }
              .bnp-bns { margin-bottom:1rem; color:#111; }
              .bnp-table { width:100%; border-collapse:collapse; font-size:.92rem; margin-bottom:1rem; background:#fff; color:#111; }
              .bnp-table th,.bnp-table td { border:1px solid #444; padding:.35rem .5rem; color:#111; background:#fff; }
              .bnp-table th { background:#f3f4f6; text-align:center; }
              .bnp-num { text-align:center; width:18%; }
              .bnp-section td { background:#eef6ee; }
              .bnp-sub td { background:#f8fafc; }
              .bnp-section-title { margin:.75rem 0 .35rem; color:#111; }
              .bnp-occupation { margin:1rem 0; color:#111; }
              .bnp-sign { display:grid; grid-template-columns:repeat(3,1fr); gap:1rem; margin-top:1.25rem; font-size:.9rem; color:#111; }
              .bnp-footnote { margin-top:.75rem; color:#555; font-size:.85rem; }
              .bnp-mode { text-align:center; margin:.4rem 0 .6rem; color:#111; }
              .bnp-mode label { margin:0 .75rem; font-weight:600; cursor:pointer; user-select:none; color:#111; }
              .bnp-mode input[type="checkbox"] { cursor:pointer; }
              .bnp-household-caption { color:#111; }
            </style>
            <div class="bnp-sheet">
            <?php
            $layout = (string) ($meta['layout'] ?? '');
            $modeSelectable = true;
            $bnpModeSwitchQuery = [
                'type' => $typeKey,
                'purok' => $filters['purok'],
                'date_from' => $filters['date_from'],
                'date_to' => $filters['date_to'],
            ];
            if ($layout === 'all_hh') {
                require __DIR__ . '/../includes/partials/nutrition_bnp_all_hh.php';
            } elseif ($layout === 'pregnant') {
                $pregnantReport = $bnpReport;
                $isCityWide = false;
                require __DIR__ . '/../includes/partials/nutrition_pregnant_families_report.php';
            } else {
                require __DIR__ . '/../includes/partials/nutrition_bnp_family_profile.php';
            }
            ?>
            </div>
          </div>
        </div>
<?php
require __DIR__ . '/../includes/partials/nutrition_layout_end.php';
