<?php

$watermarkUrl = barangay_h($certificateWatermarkUrl ?? barangay_default_logo_url('../'));

?>
<style>
    .certificate-logo-wrap {
      display: flex;
      align-items: center;
      justify-content: center;
      background: transparent !important;
    }

    #barangay_logo,
    #valencia_city,
    .certificate-logo {
      height: 200px;
      width: auto;
      max-width: 500px;
      background: transparent !important;
      background-color: transparent !important;
      border: none;
      box-shadow: none;
      border-radius: 0;
      object-fit: contain;
    }

    #valencia_city {
      mix-blend-mode: screen;
    }

    #barangay_official,
    .punong-barangay-signature {
      display: block;
      margin: 0 auto 4px;
      height: 90px;
      width: auto;
      max-width: 280px;
      background: transparent !important;
      background-color: transparent !important;
      border: none !important;
      box-shadow: none !important;
      border-radius: 0 !important;
      object-fit: contain;
    }

    .punong-barangay-signature-blank {
      display: block;
      margin: 0 auto 4px;
      height: 70px;
      width: 220px;
      border-bottom: 1px solid #111;
    }

    .punong-barangay-block {
      text-align: center;
      min-width: 220px;
    }

    .punong-barangay-block p {
      margin: 0;
      padding: 0;
      line-height: 1.25;
      font-weight: 700;
    }

    .punong-barangay-block .punong-title {
      font-weight: 600;
    }

    .certificate-document {
      position: relative;
      background: #fff;
    }

    .certificate-body {
      position: relative;
      overflow: visible;
      min-height: 520px;
      background: transparent;
    }

    .certificate-watermark {
      position: absolute;
      top: 50%;
      left: 50%;
      width: min(620px, 82%);
      max-width: 620px;
      height: auto;
      transform: translate(-50%, -50%);
      opacity: 0.16;
      pointer-events: none;
      user-select: none;
      z-index: 0;
      object-fit: contain;
    }

    .certificate-body > :not(.certificate-watermark) {
      position: relative;
      z-index: 1;
    }

    @media print {
      .container,
      .certificate-document {
        -webkit-print-color-adjust: exact;
        print-color-adjust: exact;
      }

      .certificate-watermark {
        opacity: 0.14;
      }
    }

    @media screen and (max-width: 768px) {
      #barangay_logo,
      #valencia_city,
      .certificate-logo {
        height: 120px;
        max-width: 36vw;
      }
    }
</style>
