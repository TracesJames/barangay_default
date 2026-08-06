<?php

/**
 * Patch DataTables ORDER BY / LIMIT injection patterns in *Table.php files.
 */

$base = dirname(__DIR__);
$dirs = [$base . '/admin', $base . '/secretary', $base . '/resident'];

$orderReplacement = <<<'PHP'
$__orderColumns = __ORDER_COLUMNS__;
$sql .= datatables_order_clause($__orderColumns, $_REQUEST['order'] ?? null, '__DEFAULT_ORDER__');
$sql .= datatables_limit_clause($_REQUEST['start'] ?? 0, $_REQUEST['length'] ?? 10);
PHP;

$defaults = [
    'allResidenceTable.php' => [
        'columns' => "['residence_information.residence_id','residence_information.residence_id','residence_information.first_name','residence_information.age','residence_status.pwd_info','residence_status.single_parent','residence_status.voters','residence_status.status']",
        'default' => ' ORDER BY residence_status.date_added DESC',
    ],
    'allOfficialTable.php' => [
        'columns' => "['official_information.official_id','official_status.position','official_information.first_name','official_status.voters','official_status.single_parent','official_status.status']",
        'default' => ' ORDER BY official_status.position ASC',
    ],
    'archiveResidenceTable.php' => [
        'columns' => "['residence_information.residence_id','residence_information.first_name','residence_information.age']",
        'default' => ' ORDER BY residence_status.date_archive DESC',
    ],
    'blotterRecordTable.php' => [
        'columns' => "['blotter_record.blotter_id','blotter_record.incident_type','blotter_record.date_reported']",
        'default' => ' ORDER BY blotter_record.date_reported DESC',
    ],
    'certificateTable.php' => [
        'columns' => "['certificate_request.request_id','certificate_request.certificate_type','certificate_request.date_requested']",
        'default' => ' ORDER BY certificate_request.date_requested DESC',
    ],
    'userResidenceTable.php' => [
        'columns' => "['users.first_name','users.username','users.contact_number']",
        'default' => ' ORDER BY users.username DESC',
    ],
    'systemLogsTable.php' => [
        'columns' => "['activity_log.date','activity_log.message','activity_log.status']",
        'default' => ' ORDER BY activity_log.id DESC',
    ],
    'endOfficialTable.php' => [
        'columns' => "['official_information.official_id','official_information.first_name','official_status.term_to']",
        'default' => ' ORDER BY official_status.term_to DESC',
    ],
    'userRequestTable.php' => [
        'columns' => "['certificate_request.request_id','certificate_request.certificate_type','certificate_request.status']",
        'default' => ' ORDER BY certificate_request.date_requested DESC',
    ],
];

$vulnerableOrder = "if(isset(\$_REQUEST['order'])){";
$vulnerableOrder2 = "if (isset(\$_REQUEST['order'])) {";

foreach ($dirs as $dir) {
    foreach (glob($dir . '/*Table.php') as $file) {
        $name = basename($file);
        if ($name === 'positionTable.php' || $name === 'userTableAdministrator.php' || $name === 'backupTable.php') {
            continue;
        }

        $content = file_get_contents($file);
        if (!str_contains($content, "\$_REQUEST['order']")) {
            continue;
        }
        if (str_contains($content, 'datatables_order_clause')) {
            continue;
        }

        if (!str_contains($content, 'datatables_helper.php')) {
            $content = str_replace(
                "include_once '../includes/auth_admin.php';",
                "include_once '../includes/auth_admin.php';\nrequire_once '../includes/datatables_helper.php';",
                $content
            );
            $content = str_replace(
                "include_once '../includes/auth_secretary.php';",
                "include_once '../includes/auth_secretary.php';\nrequire_once '../includes/datatables_helper.php';",
                $content
            );
            $content = str_replace(
                "include_once '../includes/auth_resident.php';",
                "include_once '../includes/auth_resident.php';\nrequire_once '../includes/datatables_helper.php';",
                $content
            );
        }

        $config = $defaults[$name] ?? [
            'columns' => "['id']",
            'default' => ' ORDER BY id DESC',
        ];

        $patch = str_replace(
            ['__ORDER_COLUMNS__', '__DEFAULT_ORDER__'],
            [$config['columns'], $config['default']],
            $orderReplacement
        );

        $pattern = '/if\s*\(isset\(\$_REQUEST\[\'order\'\]\)\)\s*\{[\s\S]*?\}else\s*\{[\s\S]*?\}/';
        if (preg_match($pattern, $content)) {
            $content = preg_replace($pattern, $patch, $content, 1);
        }

        $limitPattern = '/if\s*\(\$_REQUEST\[\'length\'\]\s*!=\s*-1\)\s*\{[\s\S]*?\}/';
        $content = preg_replace($limitPattern, '', $content, 1);

        file_put_contents($file, $content);
        echo "Patched: $name\n";
    }
}

echo "Done.\n";
