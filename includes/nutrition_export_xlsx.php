<?php

/**
 * Filled Excel exports for e-OPT / BNP and city CNPC comparison.
 */

require_once __DIR__ . '/spreadsheet_export.php';

if (!function_exists('nutrition_export_month_bounds')) {
    /**
     * @return array{0:string,1:string}|null [date_from, date_to] Y-m-d
     */
    function nutrition_export_month_bounds(string $monthYm): ?array
    {
        $monthYm = trim($monthYm);
        if (!preg_match('/^\d{4}-\d{2}$/', $monthYm)) {
            return null;
        }
        $from = $monthYm . '-01';
        $ts = strtotime($from);
        if ($ts === false) {
            return null;
        }
        $to = date('Y-m-t', $ts);

        return [$from, $to];
    }
}

if (!function_exists('nutrition_export_filters_from_request')) {
    /**
     * @param array<string, mixed> $source
     * @return array{purok:string,date_from:string,date_to:string,month:string}
     */
    function nutrition_export_filters_from_request(array $source): array
    {
        $filters = [
            'purok' => trim((string) ($source['purok'] ?? '')),
            'date_from' => trim((string) ($source['date_from'] ?? '')),
            'date_to' => trim((string) ($source['date_to'] ?? '')),
            'month' => trim((string) ($source['month'] ?? '')),
        ];
        if ($filters['month'] !== '') {
            $bounds = nutrition_export_month_bounds($filters['month']);
            if ($bounds !== null) {
                if ($filters['date_from'] === '') {
                    $filters['date_from'] = $bounds[0];
                }
                if ($filters['date_to'] === '') {
                    $filters['date_to'] = $bounds[1];
                }
            }
        }

        return $filters;
    }
}

if (!function_exists('nutrition_export_stream_sheets')) {
    /**
     * @param array<int, array{name:string,rows:array<int,array<int,mixed>>,options?:array<string,mixed>}> $sheets
     */
    function nutrition_export_stream_sheets(array $sheets, string $downloadBase): void
    {
        $tmp = tempnam(sys_get_temp_dir(), 'ntrxlsx_');
        if ($tmp === false) {
            throw new RuntimeException('Could not create temp file for Excel export.');
        }
        $xlsx = $tmp . '.xlsx';
        @unlink($tmp);
        if (!barangay_xlsx_create_file($sheets, $xlsx)) {
            throw new RuntimeException('Could not build Excel workbook.');
        }
        barangay_xlsx_stream_file($xlsx, barangay_xlsx_safe_filename($downloadBase));
    }
}

if (!function_exists('nutrition_export_cell')) {
    /**
     * @return array{v:string,s:int}|string
     */
    function nutrition_export_cell(string $value, string $style = 'default')
    {
        $styleIndex = barangay_xlsx_style($style);
        if ($styleIndex <= 0) {
            return $value;
        }

        return ['v' => $value, 's' => $styleIndex];
    }
}

if (!function_exists('nutrition_eopt_export_sheets')) {
    /**
     * @param array<string, mixed> $report
     * @return array<int, array{name:string,rows:array<int,array<int,mixed>>,options?:array<string,mixed>}>
     */
    function nutrition_eopt_export_sheets(array $report): array
    {
        $meta = $report['meta'] ?? [];
        $totals = $report['totals'] ?? [];
        $children = $report['children'] ?? [];

        $summary = [
            [nutrition_export_cell('e-OPT Plus Community Level Tool', 'title')],
            [nutrition_export_cell((string) ($meta['version'] ?? 'Region 10'), 'subtitle')],
            [nutrition_export_cell('Barangay: ' . (string) ($meta['barangay'] ?? ''), 'label')],
            [nutrition_export_cell(
                (string) ($meta['municipality'] ?? '') . ', ' . (string) ($meta['province'] ?? ''),
                'label'
            )],
            [nutrition_export_cell('Generated: ' . (string) ($meta['generated_at'] ?? date('Y-m-d H:i:s')), 'note')],
            [],
            [
                nutrition_export_cell('Indicator', 'th'),
                nutrition_export_cell('Count', 'th'),
            ],
            [nutrition_export_cell('Measured children 0–59 mo', 'td'), nutrition_export_cell((string) (int) ($totals['measured'] ?? 0), 'td')],
            [nutrition_export_cell('Boys / Girls', 'td'), nutrition_export_cell(
                ((int) ($totals['boys'] ?? 0)) . ' / ' . ((int) ($totals['girls'] ?? 0)),
                'td'
            )],
            [nutrition_export_cell('At-risk', 'td'), nutrition_export_cell((string) (int) ($totals['at_risk'] ?? 0), 'td')],
            [nutrition_export_cell('Underweight (UW)', 'td'), nutrition_export_cell((string) (int) ($totals['uw'] ?? 0), 'td')],
            [nutrition_export_cell('Severely underweight (SUW)', 'td'), nutrition_export_cell((string) (int) ($totals['suw'] ?? 0), 'td')],
            [nutrition_export_cell('Stunted (St)', 'td'), nutrition_export_cell((string) (int) ($totals['st'] ?? 0), 'td')],
            [nutrition_export_cell('Severely stunted (SSt)', 'td'), nutrition_export_cell((string) (int) ($totals['sst'] ?? 0), 'td')],
            [nutrition_export_cell('Moderately wasted (MW)', 'td'), nutrition_export_cell((string) (int) ($totals['mw'] ?? 0), 'td')],
            [nutrition_export_cell('Severely wasted (SW)', 'td'), nutrition_export_cell((string) (int) ($totals['sw'] ?? 0), 'td')],
            [nutrition_export_cell('Overweight (OW)', 'td'), nutrition_export_cell((string) (int) ($totals['ow'] ?? 0), 'td')],
            [nutrition_export_cell('Obese (OB)', 'td'), nutrition_export_cell((string) (int) ($totals['ob'] ?? 0), 'td')],
            [nutrition_export_cell('IP children', 'td'), nutrition_export_cell((string) (int) ($totals['ip'] ?? 0), 'td')],
            [nutrition_export_cell('Age 0–23 months', 'td'), nutrition_export_cell((string) (int) ($totals['age_0_23'] ?? 0), 'td')],
        ];

        $listRows = [[
            nutrition_export_cell('#', 'th'),
            nutrition_export_cell('Barangay', 'th'),
            nutrition_export_cell('Purok', 'th'),
            nutrition_export_cell('Child', 'th'),
            nutrition_export_cell('Sex', 'th'),
            nutrition_export_cell('Age (mo)', 'th'),
            nutrition_export_cell('WFA', 'th'),
            nutrition_export_cell('HFA', 'th'),
            nutrition_export_cell('WFL', 'th'),
            nutrition_export_cell('MUAC', 'th'),
            nutrition_export_cell('Date measured', 'th'),
            nutrition_export_cell('Caregiver', 'th'),
        ]];
        $n = 0;
        foreach ($children as $child) {
            if (!is_array($child)) {
                continue;
            }
            $n++;
            $listRows[] = [
                nutrition_export_cell((string) $n, 'td'),
                nutrition_export_cell((string) ($child['barangay'] ?? ''), 'td'),
                nutrition_export_cell((string) ($child['purok'] ?? ''), 'td'),
                nutrition_export_cell((string) ($child['child_name'] ?? ''), 'td'),
                nutrition_export_cell((string) ($child['sex'] ?? ''), 'td'),
                nutrition_export_cell((string) ($child['age_months'] ?? ''), 'td'),
                nutrition_export_cell((string) ($child['wfa'] ?? ''), 'td'),
                nutrition_export_cell((string) ($child['hfa'] ?? ''), 'td'),
                nutrition_export_cell((string) ($child['wfl'] ?? ''), 'td'),
                nutrition_export_cell((string) ($child['muac'] ?? ''), 'td'),
                nutrition_export_cell((string) ($child['date_measured'] ?? ''), 'td'),
                nutrition_export_cell((string) ($child['caregiver'] ?? ''), 'td'),
            ];
        }

        return [
            [
                'name' => 'Summary',
                'rows' => $summary,
                'options' => ['cols' => [36, 14]],
            ],
            [
                'name' => 'Children',
                'rows' => $listRows,
                'options' => [
                    'landscape' => true,
                    'cols' => [6, 16, 12, 22, 8, 10, 8, 8, 8, 8, 14, 22],
                ],
            ],
        ];
    }
}

if (!function_exists('nutrition_bnp_export_sheets')) {
    /**
     * @param array<string, mixed> $report
     * @return array<int, array{name:string,rows:array<int,array<int,mixed>>,options?:array<string,mixed>}>
     */
    function nutrition_bnp_export_sheets(array $report, string $typeKey): array
    {
        $meta = $report['meta'] ?? [];
        $indicators = $report['indicators'] ?? [];
        $title = trim(((string) ($meta['form'] ?? 'BNP')) . ' · ' . ((string) ($meta['sheet'] ?? $typeKey)));

        $rows = [
            [nutrition_export_cell($title, 'title')],
            [nutrition_export_cell('Barangay Nutrition Profile 2026', 'subtitle')],
            [nutrition_export_cell('Generated: ' . date('Y-m-d H:i:s'), 'note')],
            [],
            [
                nutrition_export_cell('Indicator', 'th'),
                nutrition_export_cell('Value', 'th'),
            ],
        ];

        $flat = static function (array $data, string $prefix = '') use (&$flat, &$rows): void {
            foreach ($data as $key => $value) {
                $label = $prefix === '' ? (string) $key : $prefix . ' / ' . $key;
                if (is_array($value)) {
                    $flat($value, $label);
                    continue;
                }
                if (is_bool($value)) {
                    $value = $value ? 'YES' : 'NO';
                }
                if (is_scalar($value) || $value === null) {
                    $rows[] = [
                        nutrition_export_cell($label, 'td'),
                        nutrition_export_cell((string) ($value ?? ''), 'td'),
                    ];
                }
            }
        };

        if ($indicators !== []) {
            $flat($indicators);
        } else {
            // Pregnant / family profile style payloads.
            foreach (['summary', 'totals', 'counts', 'families'] as $block) {
                if (!empty($report[$block]) && is_array($report[$block])) {
                    $rows[] = [nutrition_export_cell(strtoupper($block), 'section')];
                    $flat($report[$block], $block);
                }
            }
            if (count($rows) <= 5) {
                $flat($report);
            }
        }

        return [[
            'name' => substr(preg_replace('/[^A-Za-z0-9 ]+/', '', $title) ?: 'BNP', 0, 28),
            'rows' => $rows,
            'options' => ['cols' => [42, 18]],
        ]];
    }
}

if (!function_exists('nutrition_cnpc_comparison_rows')) {
    /**
     * Barangay-vs-barangay comparison for CNPC/city hub with date filters.
     *
     * @param array<int, string>|null $limitBarangayIds
     * @param array<string, mixed> $filters
     * @return array<int, array<string, mixed>>
     */
    function nutrition_cnpc_comparison_rows(
        mysqli $con,
        ?array $limitBarangayIds = null,
        array $filters = []
    ): array {
        require_once __DIR__ . '/nutrition_eopt_reports.php';

        $baseRows = nutrition_super_dashboard_rows($con, $limitBarangayIds, $filters);
        $eoptByBarangay = [];
        $children = nutrition_eopt_collect_children($con, null, $filters);
        foreach ($children as $child) {
            if (!is_array($child)) {
                continue;
            }
            $bid = (string) ($child['barangay_id'] ?? '');
            if ($bid === '') {
                continue;
            }
            if ($limitBarangayIds !== null) {
                $allowed = false;
                foreach ($limitBarangayIds as $id) {
                    if ((string) $id === $bid) {
                        $allowed = true;
                        break;
                    }
                }
                if (!$allowed) {
                    continue;
                }
            }
            if (!isset($eoptByBarangay[$bid])) {
                $eoptByBarangay[$bid] = [
                    'eopt_measured' => 0,
                    'eopt_at_risk' => 0,
                    'eopt_uw_suw' => 0,
                    'eopt_st_sst' => 0,
                    'eopt_mw_sw' => 0,
                    'eopt_ow_ob' => 0,
                ];
            }
            $eoptByBarangay[$bid]['eopt_measured']++;
            $wfa = (string) ($child['wfa'] ?? '');
            $hfa = (string) ($child['hfa'] ?? '');
            $wfl = (string) ($child['wfl'] ?? '');
            $muac = (string) ($child['muac'] ?? '');
            $atRisk = in_array($wfa, ['UW', 'SUW', 'OW', 'OB'], true)
                || in_array($hfa, ['St', 'SSt'], true)
                || in_array($wfl, ['MW', 'SW', 'OW', 'Ob'], true)
                || in_array($muac, ['MW', 'SW'], true);
            if ($atRisk) {
                $eoptByBarangay[$bid]['eopt_at_risk']++;
            }
            if (in_array($wfa, ['UW', 'SUW'], true)) {
                $eoptByBarangay[$bid]['eopt_uw_suw']++;
            }
            if (in_array($hfa, ['St', 'SSt'], true)) {
                $eoptByBarangay[$bid]['eopt_st_sst']++;
            }
            if (in_array($wfl, ['MW', 'SW'], true)) {
                $eoptByBarangay[$bid]['eopt_mw_sw']++;
            }
            if (in_array($wfl, ['OW', 'Ob'], true) || in_array($wfa, ['OW', 'OB'], true)) {
                $eoptByBarangay[$bid]['eopt_ow_ob']++;
            }
        }

        foreach ($baseRows as &$row) {
            $bid = (string) ($row['id'] ?? '');
            $e = $eoptByBarangay[$bid] ?? [
                'eopt_measured' => 0,
                'eopt_at_risk' => 0,
                'eopt_uw_suw' => 0,
                'eopt_st_sst' => 0,
                'eopt_mw_sw' => 0,
                'eopt_ow_ob' => 0,
            ];
            $row = array_merge($row, $e);
            $children = (int) ($row['children'] ?? 0);
            $assessed = (int) ($row['assessed'] ?? 0);
            $row['coverage_pct'] = $children > 0 ? round(($assessed / $children) * 100, 1) : 0.0;
        }
        unset($row);

        return $baseRows;
    }
}

if (!function_exists('nutrition_cnpc_comparison_export_sheets')) {
    /**
     * @param array<int, array<string, mixed>> $rows
     * @return array<int, array{name:string,rows:array<int,array<int,mixed>>,options?:array<string,mixed>}>
     */
    function nutrition_cnpc_comparison_export_sheets(array $rows, string $dateFrom = '', string $dateTo = ''): array
    {
        $period = trim($dateFrom . ($dateFrom !== '' && $dateTo !== '' ? ' to ' : '') . $dateTo);
        if ($period === '') {
            $period = 'All dates';
        }
        $out = [
            [nutrition_export_cell('City of Valencia · Barangay Nutrition Comparison', 'title')],
            [nutrition_export_cell('Period: ' . $period, 'subtitle')],
            [nutrition_export_cell('Generated: ' . date('Y-m-d H:i:s'), 'note')],
            [],
            [
                nutrition_export_cell('Barangay', 'th'),
                nutrition_export_cell('Zone', 'th'),
                nutrition_export_cell('Children', 'th'),
                nutrition_export_cell('Assessed', 'th'),
                nutrition_export_cell('Coverage %', 'th'),
                nutrition_export_cell('Pending', 'th'),
                nutrition_export_cell('At-Risk', 'th'),
                nutrition_export_cell('Surveys', 'th'),
                nutrition_export_cell('Pregnant', 'th'),
                nutrition_export_cell('e-OPT measured', 'th'),
                nutrition_export_cell('e-OPT at-risk', 'th'),
                nutrition_export_cell('UW/SUW', 'th'),
                nutrition_export_cell('St/SSt', 'th'),
                nutrition_export_cell('MW/SW', 'th'),
                nutrition_export_cell('OW/OB', 'th'),
                nutrition_export_cell('BNS', 'th'),
            ],
        ];
        foreach ($rows as $row) {
            $out[] = [
                nutrition_export_cell((string) ($row['barangay'] ?? ''), 'td'),
                nutrition_export_cell((string) ($row['zone'] ?? ''), 'td'),
                nutrition_export_cell((string) (int) ($row['children'] ?? 0), 'td'),
                nutrition_export_cell((string) (int) ($row['assessed'] ?? 0), 'td'),
                nutrition_export_cell((string) ($row['coverage_pct'] ?? 0), 'td'),
                nutrition_export_cell((string) (int) ($row['pending'] ?? 0), 'td'),
                nutrition_export_cell((string) (int) ($row['at_risk'] ?? 0), 'td'),
                nutrition_export_cell((string) (int) ($row['surveys'] ?? 0), 'td'),
                nutrition_export_cell((string) (int) ($row['pregnant'] ?? 0), 'td'),
                nutrition_export_cell((string) (int) ($row['eopt_measured'] ?? 0), 'td'),
                nutrition_export_cell((string) (int) ($row['eopt_at_risk'] ?? 0), 'td'),
                nutrition_export_cell((string) (int) ($row['eopt_uw_suw'] ?? 0), 'td'),
                nutrition_export_cell((string) (int) ($row['eopt_st_sst'] ?? 0), 'td'),
                nutrition_export_cell((string) (int) ($row['eopt_mw_sw'] ?? 0), 'td'),
                nutrition_export_cell((string) (int) ($row['eopt_ow_ob'] ?? 0), 'td'),
                nutrition_export_cell((string) ($row['bns_username'] ?? ''), 'td'),
            ];
        }

        return [[
            'name' => 'Comparison',
            'rows' => $out,
            'options' => [
                'landscape' => true,
                'cols' => [16, 8, 10, 10, 10, 10, 10, 10, 10, 12, 12, 10, 10, 10, 10, 14],
            ],
        ]];
    }
}
