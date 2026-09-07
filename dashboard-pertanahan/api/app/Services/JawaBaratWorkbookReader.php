<?php

namespace App\Services;

use App\Support\JawaBaratImportMapping;
use RuntimeException;
use SimpleXMLElement;
use ZipArchive;

class JawaBaratWorkbookReader
{
    public function read(string $path): array
    {
        $this->validateFile($path);
        $zip = new ZipArchive;
        if ($zip->open($path) !== true) throw new RuntimeException('Arsip XLSX tidak valid.');

        try {
            $this->guardArchive($zip);
            $strings = $this->sharedStrings($zip);
            [$percentStyles, $numberFormats] = $this->styles($zip);
            [$sheetName, $sheetEntry] = $this->worksheet($zip);
            $worksheet = $this->xml($zip, $sheetEntry);
            [$rows, $formulaCount] = $this->rows($worksheet, $strings, $percentStyles, $numberFormats);
            $headers = $this->headers($rows);
            $this->validateHeaders($headers);

            $dataRows = [];
            foreach ($rows as $number => $cells) {
                if ($number < 6) continue;
                $ordinal = trim((string)($cells['A']['value'] ?? ''));
                $region = trim((string)($cells['B']['value'] ?? ''));
                if ($ordinal === '' && $region === '') continue;
                if (! ctype_digit($ordinal) || $region === '') continue;
                $dataRows[] = ['row_number'=>$number, 'region_name'=>$region, 'cells'=>$cells];
            }

            return [
                'file_name'=>basename($path), 'checksum'=>hash_file('sha256', $path),
                'sheet_name'=>$sheetName, 'headers'=>$headers,
                'header_checksum'=>hash('sha256', json_encode($headers, JSON_UNESCAPED_UNICODE|JSON_THROW_ON_ERROR)),
                'formula_count'=>$formulaCount, 'rows'=>$dataRows,
            ];
        } finally {
            $zip->close();
        }
    }

    private function validateFile(string $path): void
    {
        if (! is_file($path) || strtolower(pathinfo($path, PATHINFO_EXTENSION)) !== 'xlsx') {
            throw new RuntimeException('File harus berupa XLSX yang tersedia.');
        }
        $size = filesize($path);
        if ($size === false || $size <= 0 || $size > 10 * 1024 * 1024) {
            throw new RuntimeException('Ukuran XLSX tidak valid atau melebihi 10 MB.');
        }
    }

    private function guardArchive(ZipArchive $zip): void
    {
        $expanded = 0;
        for ($index = 0; $index < $zip->numFiles; $index++) {
            $stat = $zip->statIndex($index);
            $expanded += (int)($stat['size'] ?? 0);
            $name = (string)($stat['name'] ?? '');
            if (str_contains($name, '..') || str_starts_with($name, '/') || str_contains($name, '\\')) {
                throw new RuntimeException('Arsip XLSX memiliki path tidak aman.');
            }
        }
        if ($expanded > 64 * 1024 * 1024 || $zip->numFiles > 2000) {
            throw new RuntimeException('Workbook melampaui batas ekstraksi aman.');
        }
    }

    private function sharedStrings(ZipArchive $zip): array
    {
        if ($zip->locateName('xl/sharedStrings.xml') === false) return [];
        $strings = [];
        foreach ($this->xml($zip, 'xl/sharedStrings.xml')->si as $item) {
            $strings[] = implode('', array_map('strval', $item->xpath('.//*[local-name()="t"]')));
        }
        return $strings;
    }

    private function styles(ZipArchive $zip): array
    {
        if ($zip->locateName('xl/styles.xml') === false) return [[], []];
        $xml = $this->xml($zip, 'xl/styles.xml');
        $formats = [];
        foreach ($xml->numFmts->numFmt as $format) {
            $formats[(int)$format['numFmtId']] = (string)$format['formatCode'];
        }
        $percent = [];
        $resolved = [];
        $index = 0;
        foreach ($xml->cellXfs->xf as $style) {
            $formatId = (int)$style['numFmtId'];
            $format = $formats[$formatId] ?? '';
            $percent[$index] = in_array($formatId, [9, 10], true) || str_contains($format, '%');
            $resolved[$index] = $format;
            $index++;
        }
        return [$percent, $resolved];
    }

    private function worksheet(ZipArchive $zip): array
    {
        $workbook = $this->xml($zip, 'xl/workbook.xml');
        $relations = $this->xml($zip, 'xl/_rels/workbook.xml.rels');
        $targetSheet = null;
        foreach ($workbook->sheets->sheet as $sheet) {
            if ((string)$sheet['name'] === JawaBaratImportMapping::SHEET) {
                $targetSheet = $sheet;
                break;
            }
        }
        if (! $targetSheet) throw new RuntimeException('Sheet JAWA BARAT (4 Agst) tidak ditemukan.');
        $relationId = (string)$targetSheet->attributes(
            'http://schemas.openxmlformats.org/officeDocument/2006/relationships'
        )['id'];
        $target = null;
        foreach ($relations->Relationship as $relation) {
            if ((string)$relation['Id'] === $relationId && (string)$relation['TargetMode'] !== 'External') {
                $target = (string)$relation['Target'];
                break;
            }
        }
        if (! $target || str_contains($target, '..') || str_contains($target, '\\')) {
            throw new RuntimeException('Relasi worksheet tidak valid.');
        }
        $entry = str_starts_with($target, '/') ? ltrim($target, '/') : 'xl/'.$target;
        return [(string)$targetSheet['name'], $entry];
    }

    private function rows(SimpleXMLElement $worksheet, array $strings, array $percentStyles, array $numberFormats): array
    {
        $rows = [];
        $formulaCount = 0;
        foreach ($worksheet->sheetData->row as $row) {
            $number = (int)$row['r'];
            foreach ($row->c as $cell) {
                $reference = (string)$cell['r'];
                if (! preg_match('/^([A-Z]+)\d+$/D', $reference, $match)) continue;
                $column = $match[1];
                $type = (string)$cell['t'];
                $hasValue = count($cell->v) > 0;
                $value = $hasValue ? (string)$cell->v : '';
                if ($type === 's') $value = $strings[(int)$value] ?? '';
                if ($type === 'inlineStr') {
                    $value = implode('', array_map('strval', $cell->xpath('.//*[local-name()="t"]')));
                }
                $hasFormula = count($cell->f) > 0;
                $formula = $hasFormula ? (string)$cell->f : null;
                $formulaType = $hasFormula ? ((string)$cell->f['t'] ?: 'normal') : null;
                $sharedIndex = $hasFormula && (string)$cell->f['si'] !== '' ? (int)$cell->f['si'] : null;
                if ($hasFormula) {
                    $formulaCount++;
                }
                $style = (int)$cell['s'];
                $rows[$number][$column] = [
                    'reference'=>$reference, 'value'=>$value,
                    'raw_value'=>$hasFormula ? null : $value,
                    'cached_value'=>$hasFormula && $hasValue ? $value : null,
                    'formula'=>$formula, 'formula_type'=>$formulaType,
                    'shared_formula_index'=>$sharedIndex,
                    'numeric'=>$type === '' || $type === 'n', 'error'=>$type === 'e',
                    'percent_style'=>$percentStyles[$style] ?? false,
                    'number_format'=>$numberFormats[$style] ?? null,
                ];
            }
        }
        return [$rows, $formulaCount];
    }

    private function headers(array $rows): array
    {
        $headers = [];
        foreach (JawaBaratImportMapping::columns() as $column => $_mapping) {
            $parts = [];
            foreach ([1, 2, 3, 4, 5] as $row) {
                $value = trim((string)($rows[$row][$column]['value'] ?? ''));
                if ($value !== '' && ! in_array($value, $parts, true)) $parts[] = $value;
            }
            $headers[$column] = implode(' | ', $parts);
        }
        return $headers;
    }

    private function validateHeaders(array $headers): void
    {
        foreach (JawaBaratImportMapping::columns() as $column => $mapping) {
            if (! str_contains($this->normalize($headers[$column]), $mapping['header'])) {
                throw new RuntimeException("Header kolom {$column} tidak sesuai mapping version.");
            }
        }
    }

    private function normalize(string $value): string
    {
        return trim(preg_replace('/\s+/', ' ', preg_replace('/[^a-z0-9%]+/', ' ', strtolower($value))));
    }

    private function xml(ZipArchive $zip, string $entry): SimpleXMLElement
    {
        $raw = $zip->getFromName($entry);
        if ($raw === false || stripos($raw, '<!DOCTYPE') !== false || stripos($raw, '<!ENTITY') !== false) {
            throw new RuntimeException('Bagian workbook tidak tersedia atau XML tidak aman.');
        }
        $previous = libxml_use_internal_errors(true);
        try {
            $xml = simplexml_load_string($raw, SimpleXMLElement::class, LIBXML_NONET|LIBXML_COMPACT);
        } finally {
            libxml_clear_errors();
            libxml_use_internal_errors($previous);
        }
        if ($xml === false) throw new RuntimeException('XML workbook tidak valid.');
        return $xml;
    }
}
