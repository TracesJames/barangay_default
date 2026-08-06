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

if (!function_exists('barangay_xlsx_build_sheet_xml')) {
    /**
     * @param array<int, array<int, string>> $rows
     */
    function barangay_xlsx_build_sheet_xml(array $rows): string
    {
        $sheetRows = '';
        foreach ($rows as $rowIndex => $cells) {
            $rowNumber = $rowIndex + 1;
            $sheetRows .= '<row r="' . $rowNumber . '">';
            foreach ($cells as $colIndex => $value) {
                $ref = barangay_xlsx_column_letter($colIndex) . $rowNumber;
                $value = (string) $value;
                if ($value === '') {
                    continue;
                }
                $sheetRows .= '<c r="' . $ref . '" t="inlineStr"><is><t>'
                    . barangay_xlsx_xml_escape($value)
                    . '</t></is></c>';
            }
            $sheetRows .= '</row>';
        }

        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
            . '<sheetData>' . $sheetRows . '</sheetData>'
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
            $zip->addFromString($sheetPath, barangay_xlsx_build_sheet_xml($sheet['rows']));
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
            . '<fonts count="1"><font><sz val="11"/><name val="Calibri"/></font></fonts>'
            . '<fills count="1"><fill><patternFill patternType="none"/></fill></fills>'
            . '<borders count="1"><border/></borders>'
            . '<cellStyleXfs count="1"><xf numFmtId="0" fontId="0" fillId="0" borderId="0"/></cellStyleXfs>'
            . '<cellXfs count="1"><xf numFmtId="0" fontId="0" fillId="0" borderId="0" xfId="0"/></cellXfs>'
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
