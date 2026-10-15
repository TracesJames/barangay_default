<?php

if (!function_exists('barangay_xlsx_column_letter')) {
    function barangay_xlsx_column_letter(int $index): string
    {
        $index += 1;
        $letters = '';
        while ($index > 0) {
            $mod = ($index - 1) % 26;
            $letters = chr(65 + $mod) . $letters;
            $index = intdiv($index - 1, 26);
        }
        return $letters;
    }
}

if (!function_exists('barangay_xlsx_xml_escape')) {
    function barangay_xlsx_xml_escape(string $value): string
    {
        return htmlspecialchars($value, ENT_XML1 | ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('barangay_xlsx_style')) {
    /**
     * Cell style index in the shared styles.xml written by barangay_xlsx_create_file().
     */
    function barangay_xlsx_style(string $name): int
    {
        static $map = [
            'default' => 0,
            'title' => 1,
            'subtitle' => 2,
            'section' => 3,
            'label' => 4,
            'input' => 5,
            'option' => 6,
            'th' => 7,
            'td' => 8,
            'note' => 9,
            'subsection' => 10,
            'input_prefill' => 11,
            'signature' => 12,
            'group_th' => 13,
        ];

        return $map[$name] ?? 0;
    }
}

if (!function_exists('barangay_xlsx_cell_value')) {
    /**
     * Cells may be a plain string or ['v' => string, 's' => style index].
     *
     * @param mixed $cell
     */
    function barangay_xlsx_cell_value($cell): string
    {
        return is_array($cell) ? (string) ($cell['v'] ?? '') : (string) $cell;
    }
}

if (!function_exists('barangay_xlsx_build_sheet_xml')) {
    /**
     * @param array<int, array<int, string|array{v?:string,s?:int}>> $rows
     * @param array{
     *   cols?: array<int, float>,
     *   merges?: array<int, string>,
     *   heights?: array<int, float>,
     *   hidden_cols?: array<int, int>,
     *   landscape?: bool,
     *   fit_width?: bool
     * } $options
     */
    function barangay_xlsx_build_sheet_xml(array $rows, array $options = []): string
    {
        $heights = $options['heights'] ?? [];
        $sheetRows = '';
        foreach ($rows as $rowIndex => $cells) {
            $rowNumber = $rowIndex + 1;
            $rowAttr = '';
            if (isset($heights[$rowIndex])) {
                $rowAttr = ' ht="' . (float) $heights[$rowIndex] . '" customHeight="1"';
            }
            $sheetRows .= '<row r="' . $rowNumber . '"' . $rowAttr . '>';
            foreach ($cells as $colIndex => $cell) {
                $ref = barangay_xlsx_column_letter($colIndex) . $rowNumber;
                $value = barangay_xlsx_cell_value($cell);
                $style = is_array($cell) ? (int) ($cell['s'] ?? 0) : 0;
                $styleAttr = $style > 0 ? ' s="' . $style . '"' : '';
                if ($value === '') {
                    if ($style > 0) {
                        $sheetRows .= '<c r="' . $ref . '"' . $styleAttr . '/>';
                    }
                    continue;
                }
                $sheetRows .= '<c r="' . $ref . '"' . $styleAttr . ' t="inlineStr"><is><t xml:space="preserve">'
                    . barangay_xlsx_xml_escape($value)
                    . '</t></is></c>';
            }
            $sheetRows .= '</row>';
        }

        $fitWidth = !empty($options['fit_width']);
        $sheetPr = $fitWidth ? '<sheetPr><pageSetUpPr fitToPage="1"/></sheetPr>' : '';

        $cols = '';
        $hiddenCols = array_flip(array_map('intval', $options['hidden_cols'] ?? []));
        if (!empty($options['cols']) || $hiddenCols !== []) {
            $widths = $options['cols'] ?? [];
            foreach (array_keys($hiddenCols) as $i) {
                $widths[$i] = $widths[$i] ?? 8;
            }
            ksort($widths);
            $cols = '<cols>';
            foreach ($widths as $i => $width) {
                $n = $i + 1;
                $cols .= '<col min="' . $n . '" max="' . $n . '" width="' . (float) $width . '" customWidth="1"'
                    . (isset($hiddenCols[$i]) ? ' hidden="1"' : '') . '/>';
            }
            $cols .= '</cols>';
        }

        $merges = '';
        if (!empty($options['merges'])) {
            $merges = '<mergeCells count="' . count($options['merges']) . '">';
            foreach ($options['merges'] as $range) {
                $merges .= '<mergeCell ref="' . barangay_xlsx_xml_escape($range) . '"/>';
            }
            $merges .= '</mergeCells>';
        }

        $page = '';
        if ($fitWidth || !empty($options['landscape'])) {
            $page = '<pageMargins left="0.4" right="0.4" top="0.5" bottom="0.5" header="0.3" footer="0.3"/>'
                . '<pageSetup paperSize="9"'
                . (!empty($options['landscape']) ? ' orientation="landscape"' : ' orientation="portrait"')
                . ($fitWidth ? ' fitToWidth="1" fitToHeight="0"' : '')
                . '/>';
        }
        if ($fitWidth) {
            $page .= '<ignoredErrors><ignoredError sqref="A1:Z500" numberStoredAsText="1"/></ignoredErrors>';
        }

        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
            . $sheetPr
            . '<sheetViews><sheetView workbookViewId="0"' . ($fitWidth ? ' showGridLines="0"' : '') . '/></sheetViews>'
            . $cols
            . '<sheetData>' . $sheetRows . '</sheetData>'
            . $merges
            . $page
            . '</worksheet>';
    }
}

if (!function_exists('barangay_xlsx_create_file')) {
    /**
     * @param array<int, array{name:string,rows:array<int,array<int,string>>}> $sheets
     */
    function barangay_xlsx_create_file(array $sheets, string $outputPath): bool
    {
        if (!class_exists('ZipArchive')) {
            throw new RuntimeException('Excel export requires the PHP zip extension.');
        }

        $zip = new ZipArchive();
        if ($zip->open($outputPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            return false;
        }

        $contentTypes = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">'
            . '<Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>'
            . '<Default Extension="xml" ContentType="application/xml"/>'
            . '<Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>'
            . '<Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/>';

        $workbookSheets = '';
        $workbookRels = '';
        foreach ($sheets as $index => $sheet) {
            $sheetNumber = $index + 1;
            $sheetPath = 'xl/worksheets/sheet' . $sheetNumber . '.xml';
            $contentTypes .= '<Override PartName="/' . $sheetPath . '" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>';
            $workbookSheets .= '<sheet name="' . barangay_xlsx_xml_escape($sheet['name']) . '" sheetId="' . $sheetNumber . '" r:id="rId' . $sheetNumber . '"/>';
            $workbookRels .= '<Relationship Id="rId' . $sheetNumber . '" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet' . $sheetNumber . '.xml"/>';
            $zip->addFromString($sheetPath, barangay_xlsx_build_sheet_xml($sheet['rows'], $sheet['options'] ?? []));
        }

        $contentTypes .= '</Types>';
        $zip->addFromString('[Content_Types].xml', $contentTypes);
        $zip->addFromString(
            '_rels/.rels',
            '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            . '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/>'
            . '</Relationships>'
        );
        $zip->addFromString(
            'xl/_rels/workbook.xml.rels',
            '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            . $workbookRels
            . '<Relationship Id="rId' . (count($sheets) + 1) . '" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/>'
            . '</Relationships>'
        );
        $zip->addFromString(
            'xl/workbook.xml',
            '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" '
            . 'xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">'
            . '<sheets>' . $workbookSheets . '</sheets>'
            . '</workbook>'
        );
        $zip->addFromString(
            'xl/styles.xml',
            '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
            . '<fonts count="10">'
            . '<font><sz val="11"/><name val="Calibri"/></font>'
            . '<font><b/><sz val="15"/><color rgb="FF14532D"/><name val="Calibri"/></font>'
            . '<font><sz val="10"/><color rgb="FF4B5563"/><name val="Calibri"/></font>'
            . '<font><b/><sz val="11"/><color rgb="FFFFFFFF"/><name val="Calibri"/></font>'
            . '<font><sz val="8"/><color rgb="FF6B7280"/><name val="Calibri"/></font>'
            . '<font><sz val="10"/><name val="Calibri"/></font>'
            . '<font><b/><sz val="9"/><name val="Calibri"/></font>'
            . '<font><i/><sz val="9"/><color rgb="FF4B5563"/><name val="Calibri"/></font>'
            . '<font><b/><sz val="10"/><color rgb="FF14532D"/><name val="Calibri"/></font>'
            . '<font><b/><sz val="10"/><name val="Calibri"/></font>'
            . '</fonts>'
            . '<fills count="5">'
            . '<fill><patternFill patternType="none"/></fill>'
            . '<fill><patternFill patternType="gray125"/></fill>'
            . '<fill><patternFill patternType="solid"><fgColor rgb="FF1F6F43"/><bgColor indexed="64"/></patternFill></fill>'
            . '<fill><patternFill patternType="solid"><fgColor rgb="FFF3F4F6"/><bgColor indexed="64"/></patternFill></fill>'
            . '<fill><patternFill patternType="solid"><fgColor rgb="FFE8F5E9"/><bgColor indexed="64"/></patternFill></fill>'
            . '</fills>'
            . '<borders count="4">'
            . '<border/>'
            . '<border><left/><right/><top/><bottom style="thin"><color rgb="FF111111"/></bottom><diagonal/></border>'
            . '<border><left style="thin"><color rgb="FF9CA3AF"/></left><right style="thin"><color rgb="FF9CA3AF"/></right><top style="thin"><color rgb="FF9CA3AF"/></top><bottom style="thin"><color rgb="FF9CA3AF"/></bottom><diagonal/></border>'
            . '<border><left/><right/><top style="thin"><color rgb="FF111111"/></top><bottom/><diagonal/></border>'
            . '</borders>'
            . '<cellStyleXfs count="1"><xf numFmtId="0" fontId="0" fillId="0" borderId="0"/></cellStyleXfs>'
            . '<cellXfs count="14">'
            . '<xf numFmtId="0" fontId="0" fillId="0" borderId="0" xfId="0"/>'
            . '<xf numFmtId="0" fontId="1" fillId="0" borderId="0" xfId="0" applyFont="1" applyAlignment="1"><alignment horizontal="center" vertical="center"/></xf>'
            . '<xf numFmtId="0" fontId="2" fillId="0" borderId="0" xfId="0" applyFont="1" applyAlignment="1"><alignment horizontal="center" vertical="center"/></xf>'
            . '<xf numFmtId="0" fontId="3" fillId="2" borderId="0" xfId="0" applyFont="1" applyFill="1" applyAlignment="1"><alignment vertical="center"/></xf>'
            . '<xf numFmtId="0" fontId="4" fillId="0" borderId="0" xfId="0" applyFont="1" applyAlignment="1"><alignment vertical="bottom"/></xf>'
            . '<xf numFmtId="0" fontId="5" fillId="0" borderId="1" xfId="0" applyFont="1" applyBorder="1" applyAlignment="1"><alignment vertical="bottom"/></xf>'
            . '<xf numFmtId="0" fontId="5" fillId="0" borderId="0" xfId="0" applyFont="1" applyAlignment="1"><alignment vertical="center"/></xf>'
            . '<xf numFmtId="0" fontId="6" fillId="3" borderId="2" xfId="0" applyFont="1" applyFill="1" applyBorder="1" applyAlignment="1"><alignment horizontal="center" vertical="center" wrapText="1"/></xf>'
            . '<xf numFmtId="0" fontId="5" fillId="0" borderId="2" xfId="0" applyFont="1" applyBorder="1" applyAlignment="1"><alignment vertical="center" wrapText="1"/></xf>'
            . '<xf numFmtId="0" fontId="7" fillId="0" borderId="0" xfId="0" applyFont="1" applyAlignment="1"><alignment vertical="top" wrapText="1"/></xf>'
            . '<xf numFmtId="0" fontId="8" fillId="0" borderId="0" xfId="0" applyFont="1" applyAlignment="1"><alignment vertical="center"/></xf>'
            . '<xf numFmtId="0" fontId="9" fillId="0" borderId="1" xfId="0" applyFont="1" applyBorder="1" applyAlignment="1"><alignment vertical="bottom"/></xf>'
            . '<xf numFmtId="0" fontId="4" fillId="0" borderId="3" xfId="0" applyFont="1" applyBorder="1" applyAlignment="1"><alignment horizontal="center" vertical="top"/></xf>'
            . '<xf numFmtId="0" fontId="6" fillId="4" borderId="2" xfId="0" applyFont="1" applyFill="1" applyBorder="1" applyAlignment="1"><alignment horizontal="center" vertical="center" wrapText="1"/></xf>'
            . '</cellXfs>'
            . '</styleSheet>'
        );

        $zip->close();
        return true;
    }
}

if (!function_exists('barangay_xlsx_stream_file')) {
    function barangay_xlsx_stream_file(string $filePath, string $downloadName): void
    {
        if (!is_file($filePath)) {
            throw new RuntimeException('Excel file could not be created.');
        }

        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="' . rawurlencode($downloadName) . '"; filename*=UTF-8\'\'' . rawurlencode($downloadName));
        header('Content-Length: ' . (string) filesize($filePath));
        header('Cache-Control: max-age=0');
        readfile($filePath);
        @unlink($filePath);
        exit;
    }
}

if (!function_exists('barangay_xlsx_safe_filename')) {
    function barangay_xlsx_safe_filename(string $name, string $suffix = '.xlsx'): string
    {
        $name = preg_replace('/[^A-Za-z0-9._-]+/', '_', $name) ?? 'export';
        $name = trim($name, '._-');
        if ($name === '') {
            $name = 'export';
        }
        return $name . $suffix;
    }
}
