<?php
/**
 * CLI only — kills long-running stuck residence/voter queries.
 * Do not expose over HTTP.
 */
if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit('Forbidden');
}

require_once __DIR__ . '/../connection.php';

$result = $con->query('SHOW FULL PROCESSLIST');
while ($row = $result->fetch_assoc()) {
    $info = (string) ($row['Info'] ?? '');
    $id = (int) $row['Id'];
    $time = (int) $row['Time'];
    if ($id === (int) ($con->thread_id ?? 0)) {
        continue;
    }
    if ($time < 5) {
        continue;
    }
    if (stripos($info, 'residence_status') === false && stripos($info, 'voters') === false) {
        continue;
    }
    echo "Killing query {$id} (time={$time}): " . substr($info, 0, 100) . "\n";
    $con->query('KILL ' . $id);
}

echo "Done killing stuck queries.\n";
