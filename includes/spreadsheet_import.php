<?php

const BARANGAY_SPREADSHEET_MAX_BYTES = 10485760;
const BARANGAY_SPREADSHEET_EXTENSIONS = ['csv', 'xlsx'];

if (!function_exists('barangay_validate_spreadsheet_upload')) {
    function barangay_validate_spreadsheet_upload(array $file): array
    {
        if (!isset($file['error']) || $file['error'] === UPLOAD_ERR_NO_FILE) {
            return ['ok' => false, 'error' => 'No file uploaded.'];
        }
        if ($file['error'] !== UPLOAD_ERR_OK) {
            return ['ok' => false, 'error' => 'Upload failed.'];
        }
        if ($file['size'] > BARANGAY_SPREADSHEET_MAX_BYTES) {
            return ['ok' => false, 'error' => 'File is too large (max 10 MB).'];
        }

        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (!in_array($ext, BARANGAY_SPREADSHEET_EXTENSIONS, true)) {
            return ['ok' => false, 'error' => 'Upload a .csv or .xlsx file.'];
        }

        return ['ok' => true, 'ext' => $ext];
    }
}

if (!function_exists('barangay_normalize_spreadsheet_header')) {
    function barangay_normalize_spreadsheet_header(string $header): string
    {
        $header = strtolower(trim($header));
        $header = preg_replace('/[^a-z0-9]+/', '_', $header) ?? '';
        return trim($header, '_');
    }
}

if (!function_exists('barangay_parse_csv_rows')) {
    /**
     * @return array<int, array<string, string>>
     */
    function barangay_parse_csv_rows(string $path): array
    {
        $handle = fopen($path, 'rb');
        if ($handle === false) {
            throw new RuntimeException('Could not read CSV file.');
        }

        $headers = null;
        $rows = [];

        while (($data = fgetcsv($handle)) !== false) {
            if ($data === [null] || $data === false) {
                continue;
            }

            $data = array_map(static function ($value): string {
                $value = (string) $value;
                if (strncmp($value, "\xEF\xBB\xBF", 3) === 0) {
                    $value = substr($value, 3);
                }
                return trim($value);
            }, $data);

            if ($headers === null) {
                $headers = array_map('barangay_normalize_spreadsheet_header', $data);
                continue;
            }

            if (barangay_spreadsheet_row_is_empty($data)) {
                continue;
            }

            $row = [];
            foreach ($headers as $index => $key) {
                if ($key === '') {
                    continue;
                }
                $row[$key] = trim((string) ($data[$index] ?? ''));
            }
            $rows[] = $row;
        }

        fclose($handle);

        if ($headers === null) {
            throw new RuntimeException('The spreadsheet has no header row.');
        }

        return $rows;
    }
}

if (!function_exists('barangay_spreadsheet_row_is_empty')) {
  /**
   * @param array<int, string|null> $cells
   */
    function barangay_spreadsheet_row_is_empty(array $cells): bool
    {
        foreach ($cells as $cell) {
            if (trim((string) $cell) !== '') {
                return false;
            }
        }
        return true;
    }
}

if (!function_exists('barangay_xlsx_column_index')) {
    function barangay_xlsx_column_index(string $letters): int
    {
        $letters = strtoupper($letters);
        $index = 0;
        $length = strlen($letters);
        for ($i = 0; $i < $length; $i++) {
            $index = $index * 26 + (ord($letters[$i]) - 64);
        }
        return $index - 1;
    }
}

if (!function_exists('barangay_parse_xlsx_rows')) {
    /**
     * @return array<int, array<string, string>>
     */
    function barangay_parse_xlsx_rows(string $path): array
    {
        if (!class_exists('ZipArchive')) {
            throw new RuntimeException('Excel import requires the PHP zip extension.');
        }

        $zip = new ZipArchive();
        if ($zip->open($path) !== true) {
            throw new RuntimeException('Could not open Excel file.');
        }

        $sharedStrings = [];
        $sharedXml = $zip->getFromName('xl/sharedStrings.xml');
        if ($sharedXml !== false) {
            $xml = @simplexml_load_string($sharedXml);
            if ($xml !== false) {
                foreach ($xml->si as $si) {
                    if (isset($si->t)) {
                        $sharedStrings[] = (string) $si->t;
                        continue;
                    }
                    $text = '';
                    foreach ($si->r as $run) {
                        $text .= (string) $run->t;
                    }
                    $sharedStrings[] = $text;
                }
            }
        }

        $sheetXml = $zip->getFromName('xl/worksheets/sheet1.xml');
        $zip->close();
        if ($sheetXml === false) {
            throw new RuntimeException('Excel file has no worksheet data.');
        }

        $sheet = @simplexml_load_string($sheetXml);
        if ($sheet === false || !isset($sheet->sheetData->row)) {
            throw new RuntimeException('Could not read worksheet data.');
        }

        $headers = null;
        $rows = [];

        foreach ($sheet->sheetData->row as $row) {
            $cells = [];
            foreach ($row->c as $cell) {
                $ref = (string) $cell['r'];
                if (!preg_match('/^([A-Z]+)/', $ref, $matches)) {
                    continue;
                }
                $index = barangay_xlsx_column_index($matches[1]);
                $type = (string) ($cell['t'] ?? '');
                if ($type === 's') {
                    $value = $sharedStrings[(int) $cell->v] ?? '';
                } elseif ($type === 'inlineStr') {
                    $value = (string) ($cell->is->t ?? '');
                } else {
                    $value = (string) ($cell->v ?? '');
                }
                $cells[$index] = trim($value);
            }

            if ($cells === []) {
                continue;
            }

            ksort($cells);
            $line = array_values($cells);

            if ($headers === null) {
                $headers = array_map('barangay_normalize_spreadsheet_header', $line);
                continue;
            }

            if (barangay_spreadsheet_row_is_empty($line)) {
                continue;
            }

            $assoc = [];
            foreach ($headers as $index => $key) {
                if ($key === '') {
                    continue;
                }
                $assoc[$key] = trim((string) ($line[$index] ?? ''));
            }
            $rows[] = $assoc;
        }

        if ($headers === null) {
            throw new RuntimeException('The spreadsheet has no header row.');
        }

        return $rows;
    }
}

if (!function_exists('barangay_parse_spreadsheet_rows')) {
    /**
     * @return array<int, array<string, string>>
     */
    function barangay_parse_spreadsheet_rows(string $path, string $ext): array
    {
        if ($ext === 'csv') {
            return barangay_parse_csv_rows($path);
        }
        if ($ext === 'xlsx') {
            return barangay_parse_xlsx_rows($path);
        }
        throw new RuntimeException('Unsupported file type.');
    }
}

if (!function_exists('barangay_parse_xlsx_sheet_rows_from_xml')) {
    /**
     * @return array<int, array<string, string>>
     */
    function barangay_parse_xlsx_sheet_rows_from_xml(string $sheetXml, array $sharedStrings): array
    {
        $sheet = @simplexml_load_string($sheetXml);
        if ($sheet === false || !isset($sheet->sheetData->row)) {
            return [];
        }

        $headers = null;
        $rows = [];

        foreach ($sheet->sheetData->row as $row) {
            $cells = [];
            foreach ($row->c as $cell) {
                $ref = (string) $cell['r'];
                if (!preg_match('/^([A-Z]+)/', $ref, $matches)) {
                    continue;
                }
                $index = barangay_xlsx_column_index($matches[1]);
                $type = (string) ($cell['t'] ?? '');
                if ($type === 's') {
                    $value = $sharedStrings[(int) $cell->v] ?? '';
                } elseif ($type === 'inlineStr') {
                    $value = (string) ($cell->is->t ?? '');
                } else {
                    $value = (string) ($cell->v ?? '');
                }
                $cells[$index] = trim($value);
            }

            if ($cells === []) {
                continue;
            }

            ksort($cells);
            $line = array_values($cells);

            if ($headers === null) {
                $headers = array_map('barangay_normalize_spreadsheet_header', $line);
                continue;
            }

            if (barangay_spreadsheet_row_is_empty($line)) {
                continue;
            }

            $assoc = [];
            foreach ($headers as $index => $key) {
                if ($key === '') {
                    continue;
                }
                $assoc[$key] = trim((string) ($line[$index] ?? ''));
            }
            $rows[] = $assoc;
        }

        return $rows;
    }
}

if (!function_exists('barangay_parse_xlsx_workbook')) {
    /**
     * @return array<string, array<int, array<string, string>>>
     */
    function barangay_parse_xlsx_workbook(string $path, array $skipSheets = []): array
    {
        if (!class_exists('ZipArchive')) {
            throw new RuntimeException('Excel import requires the PHP zip extension.');
        }

        $zip = new ZipArchive();
        if ($zip->open($path) !== true) {
            throw new RuntimeException('Could not open Excel file.');
        }

        $sharedStrings = [];
        $sharedXml = $zip->getFromName('xl/sharedStrings.xml');
        if ($sharedXml !== false) {
            $xml = @simplexml_load_string($sharedXml);
            if ($xml !== false) {
                foreach ($xml->si as $si) {
                    if (isset($si->t)) {
                        $sharedStrings[] = (string) $si->t;
                        continue;
                    }
                    $text = '';
                    foreach ($si->r as $run) {
                        $text .= (string) $run->t;
                    }
                    $sharedStrings[] = $text;
                }
            }
        }

        $rels = @simplexml_load_string((string) $zip->getFromName('xl/_rels/workbook.xml.rels'));
        $workbook = @simplexml_load_string((string) $zip->getFromName('xl/workbook.xml'));
        if ($rels === false || $workbook === false) {
            $zip->close();
            throw new RuntimeException('Invalid Excel workbook metadata.');
        }

        $skipLookup = [];
        foreach ($skipSheets as $sheetName) {
            $skipLookup[strtolower((string) $sheetName)] = true;
        }

        $sheets = [];
        foreach ($workbook->sheets->sheet as $sheet) {
            $name = (string) $sheet['name'];
            if (isset($skipLookup[strtolower($name)])) {
                continue;
            }

            $rid = (string) $sheet->attributes('http://schemas.openxmlformats.org/officeDocument/2006/relationships')['id'];
            $sheetPath = null;
            foreach ($rels->Relationship as $rel) {
                if ((string) $rel['Id'] === $rid) {
                    $sheetPath = 'xl/' . (string) $rel['Target'];
                    break;
                }
            }
            if ($sheetPath === null) {
                continue;
            }

            $sheetXml = $zip->getFromName($sheetPath);
            if ($sheetXml === false) {
                continue;
            }

            $sheets[$name] = barangay_parse_xlsx_sheet_rows_from_xml($sheetXml, $sharedStrings);
        }

        $zip->close();
        return $sheets;
    }
}
