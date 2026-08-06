<?php

$watermarkUrl = barangay_h(barangay_default_logo_url('../'));

?>
<style>
  body {
    font-family: "Times New Roman", Times, serif;
    color: #111;
    background: #fff;
  }

  .nutrition-report-document {
    position: relative;
    background: #fff;
  }

  .nutrition-report-body {
    position: relative;
    overflow: visible;
    min-height: 520px;
    background: transparent;
  }

  .nutrition-report-watermark {
    position: absolute;
    top: 50%;
    left: 50%;
    width: min(620px, 82%);
    max-width: 620px;
    height: auto;
    transform: translate(-50%, -50%);
    opacity: 0.14;
    pointer-events: none;
    user-select: none;
    z-index: 0;
    object-fit: contain;
  }

  .nutrition-report-body > :not(.nutrition-report-watermark) {
    position: relative;
    z-index: 1;
  }

  .nutrition-report-logo-wrap {
    display: flex;
    align-items: center;
    justify-content: center;
    background: transparent !important;
  }

  #nutrition_city_logo,
  .nutrition-report-logo {
    height: 180px;
    width: auto;
    max-width: 420px;
    background: transparent !important;
    border: none;
    box-shadow: none;
    border-radius: 0;
    object-fit: contain;
  }

  .nutrition-signatory-img {
    height: 120px;
    width: auto;
    max-width: 260px;
    background: transparent !important;
    border: none;
    box-shadow: none;
    object-fit: contain;
  }

  .nutrition-report-title {
    font-size: 34px;
    font-weight: 800;
    letter-spacing: 0.04em;
    text-align: center;
    margin: 0 0 8px;
  }

  .nutrition-report-subtitle {
    font-size: 18px;
    text-align: center;
    margin-bottom: 18px;
    color: #333;
  }

  .nutrition-report-meta {
    font-size: 14px;
    color: #444;
    margin-bottom: 18px;
  }

  .nutrition-report-table {
    width: 100%;
    border-collapse: collapse;
    margin-bottom: 18px;
    font-size: 13px;
  }

  .nutrition-report-table th,
  .nutrition-report-table td {
    border: 1px solid #333;
    padding: 7px 8px;
    text-align: left;
    vertical-align: top;
  }

  .nutrition-report-table th {
    background: #e8f5e9;
    font-weight: 700;
  }

  .nutrition-report-stats {
    display: grid;
    grid-template-columns: repeat(4, minmax(0, 1fr));
    gap: 10px;
    margin-bottom: 18px;
  }

  .nutrition-report-stat {
    border: 1px solid #333;
    padding: 10px;
    text-align: center;
    background: #f8fff9;
  }

  .nutrition-report-stat strong {
    display: block;
    font-size: 22px;
    line-height: 1.1;
  }

  .nutrition-report-stat span {
    font-size: 11px;
    text-transform: uppercase;
    letter-spacing: 0.04em;
  }

  .nutrition-report-paragraph {
    font-size: 16px;
    line-height: 1.55;
    text-align: justify;
    margin-bottom: 12px;
  }

  .nutrition-signature-grid {
    display: grid;
    grid-template-columns: repeat(3, minmax(0, 1fr));
    gap: 18px 12px;
    margin-top: 16px;
    text-align: center;
    font-size: 12px;
  }

  .nutrition-signature-card {
    min-height: 170px;
    page-break-inside: avoid;
  }

  .nutrition-signature-card .barangay-label {
    font-weight: 700;
    font-size: 12px;
    margin-bottom: 4px;
  }

  .nutrition-signature-card .role-label {
    font-size: 11px;
    color: #333;
  }

  .nutrition-admin-signature {
    margin-top: 24px;
    text-align: center;
    page-break-inside: avoid;
  }

  .nutrition-report-note {
    font-size: 11px;
    text-align: center;
    margin-top: 18px;
    color: #444;
  }

  .no-print {
    margin: 12px;
  }

  @media print {
    .no-print {
      display: none !important;
    }

    .nutrition-report-document,
    .container {
      -webkit-print-color-adjust: exact;
      print-color-adjust: exact;
    }

    .nutrition-report-watermark {
      opacity: 0.12;
    }
  }

  @media (max-width: 768px) {
    .nutrition-report-stats {
      grid-template-columns: repeat(2, minmax(0, 1fr));
    }

    .nutrition-signature-grid {
      grid-template-columns: repeat(2, minmax(0, 1fr));
    }

    #nutrition_city_logo,
    .nutrition-report-logo {
      height: 120px;
      max-width: 70vw;
    }

    .nutrition-report-title {
      font-size: clamp(20px, 6vw, 34px);
    }

    .nutrition-report-table {
      font-size: 11px;
    }
  }

  @media (max-width: 480px) {
    .nutrition-report-stats,
    .nutrition-signature-grid {
      grid-template-columns: 1fr;
    }
  }
</style>
<?php
if (!isset($reportFitSkipAssets) || !$reportFitSkipAssets) {
    $reportFitAssetBase = $reportFitAssetBase ?? '../assets';
    require __DIR__ . '/partials/report_fit_assets.php';
}
?>
