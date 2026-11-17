<?php
http_response_code(503);
header('Content-Type: text/html; charset=utf-8');
header('Retry-After: 120');
$detail = '';
if (isset($barangayMaintenanceDetail) && is_string($barangayMaintenanceDetail) && $barangayMaintenanceDetail !== '') {
    $detail = $barangayMaintenanceDetail;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Temporarily unavailable | City of Valencia Portal</title>
  <style>
    :root { color-scheme: light; }
    body {
      margin: 0;
      min-height: 100vh;
      display: flex;
      align-items: center;
      justify-content: center;
      font-family: "Segoe UI", Tahoma, sans-serif;
      background: linear-gradient(160deg, #e8f5e9 0%, #f1f8e9 45%, #e3f2fd 100%);
      color: #1b5e20;
      padding: 1.5rem;
    }
    .card {
      max-width: 32rem;
      width: 100%;
      background: rgba(255,255,255,.92);
      border: 1px solid #c8e6c9;
      border-radius: 12px;
      padding: 2rem 1.75rem;
      box-shadow: 0 10px 30px rgba(27, 94, 32, .08);
    }
    h1 { margin: 0 0 .75rem; font-size: 1.5rem; }
    p { margin: 0 0 .75rem; line-height: 1.5; color: #33691e; }
    .hint { font-size: .9rem; color: #558b2f; }
    code { font-size: .85rem; background: #f1f8e9; padding: .1rem .35rem; border-radius: 4px; }
  </style>
</head>
<body>
  <div class="card">
    <h1>Portal temporarily unavailable</h1>
    <p>The City of Valencia Portal cannot reach its database right now. This is usually because MySQL/MariaDB in XAMPP is stopped or restarting.</p>
    <p class="hint">For IT: start <strong>MySQL</strong> in the XAMPP Control Panel, wait a few seconds, then refresh. If it keeps failing, check Aria logs under <code>C:\xampp\mysql\data\mysql_error.log</code>.</p>
    <?php if ($detail !== '') : ?>
    <p class="hint">Technical detail: <?= htmlspecialchars($detail, ENT_QUOTES, 'UTF-8') ?></p>
    <?php endif; ?>
  </div>
</body>
</html>
<?php
exit;
