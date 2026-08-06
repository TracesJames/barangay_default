<?php

/**
 * Structured audit logging for Barangay Hub.
 */
if (!function_exists('barangay_audit_ensure_columns')) {
    function barangay_audit_ensure_columns(mysqli $con): void
    {
        static $done = false;
        if ($done) {
            return;
        }
        $done = true;

        $tableOk = function_exists('barangay_table_exists') && barangay_table_exists($con, 'activity_log');
        if (!$tableOk) {
            $con->query(
                "CREATE TABLE IF NOT EXISTS activity_log (
                    id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
                    message TEXT NOT NULL,
                    date VARCHAR(64) NOT NULL DEFAULT '',
                    status VARCHAR(32) NOT NULL DEFAULT '',
                    user_id VARCHAR(64) NULL,
                    barangay_id VARCHAR(64) NULL,
                    entity_type VARCHAR(64) NULL,
                    entity_id VARCHAR(64) NULL,
                    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"
            );
            return;
        }

        $cols = [
            'user_id' => 'ALTER TABLE activity_log ADD COLUMN user_id VARCHAR(64) NULL AFTER status',
            'barangay_id' => 'ALTER TABLE activity_log ADD COLUMN barangay_id VARCHAR(64) NULL AFTER user_id',
            'entity_type' => 'ALTER TABLE activity_log ADD COLUMN entity_type VARCHAR(64) NULL AFTER barangay_id',
            'entity_id' => 'ALTER TABLE activity_log ADD COLUMN entity_id VARCHAR(64) NULL AFTER entity_type',
            'created_at' => 'ALTER TABLE activity_log ADD COLUMN created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP AFTER entity_id',
        ];
        foreach ($cols as $col => $sql) {
            if (!barangay_column_exists($con, 'activity_log', $col)) {
                @$con->query($sql);
            }
        }
    }
}

if (!function_exists('barangay_audit_log')) {
    /**
     * @param array<string, mixed> $opts
     */
    function barangay_audit_log(mysqli $con, string $message, string $status = 'update', array $opts = []): void
    {
        barangay_audit_ensure_columns($con);

        $message = trim($message);
        if ($message === '') {
            return;
        }

        $status = trim($status) !== '' ? trim($status) : 'update';
        $date = date('j-n-Y g:i A');
        $userId = $opts['user_id'] ?? ($_SESSION['user_id'] ?? null);
        $barangayId = $opts['barangay_id'] ?? (function_exists('barangay_session_id') ? barangay_session_id() : null);
        $entityType = $opts['entity_type'] ?? null;
        $entityId = $opts['entity_id'] ?? null;

        $hasUser = barangay_column_exists($con, 'activity_log', 'user_id');
        $hasBrgy = barangay_column_exists($con, 'activity_log', 'barangay_id');
        $hasEntityType = barangay_column_exists($con, 'activity_log', 'entity_type');
        $hasEntityId = barangay_column_exists($con, 'activity_log', 'entity_id');

        if ($hasUser && $hasBrgy && $hasEntityType && $hasEntityId) {
            $stmt = $con->prepare(
                'INSERT INTO activity_log (message, date, status, user_id, barangay_id, entity_type, entity_id)
                 VALUES (?, ?, ?, ?, ?, ?, ?)'
            );
            if ($stmt) {
                $uid = $userId !== null ? (string) $userId : null;
                $bid = $barangayId !== null ? (string) $barangayId : null;
                $et = $entityType !== null ? (string) $entityType : null;
                $eid = $entityId !== null ? (string) $entityId : null;
                $stmt->bind_param('sssssss', $message, $date, $status, $uid, $bid, $et, $eid);
                $stmt->execute();
                $stmt->close();
            }
            return;
        }

        $stmt = $con->prepare('INSERT INTO activity_log (message, date, status) VALUES (?, ?, ?)');
        if ($stmt) {
            $stmt->bind_param('sss', $message, $date, $status);
            $stmt->execute();
            $stmt->close();
        }
    }
}
