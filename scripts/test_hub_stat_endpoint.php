<?php

chdir(__DIR__ . '/../admin');

$_SERVER['REQUEST_METHOD'] = 'POST';
$_POST['hub_stat'] = 'children';
$_POST['draw'] = '1';
$_POST['start'] = '0';
$_POST['length'] = '25';
$_POST['search'] = ['value' => ''];
$_POST['order'] = [['column' => '1', 'dir' => 'asc']];

ob_start();
include 'hubStatResidentsTable.php';
$output = ob_get_clean();

$data = json_decode($output, true);
echo 'recordsTotal=' . ($data['recordsTotal'] ?? 'n/a') . PHP_EOL;
echo 'data_count=' . (isset($data['data']) ? count($data['data']) : 0) . PHP_EOL;
if (!empty($data['data'][0])) {
    echo 'first_row=' . implode(' | ', $data['data'][0]) . PHP_EOL;
}
if (!empty($data['error'])) {
    echo 'error=' . $data['error'] . PHP_EOL;
}
