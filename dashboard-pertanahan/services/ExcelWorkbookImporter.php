<?php
declare(strict_types=1);

final class ExcelWorkbookImporter
{
    private PDO $pdo;
    private array $mapping;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
        $this->mapping = require __DIR__ . '/../data/mappings/excel-jawa-barat.php';
    }

    public static function validateUpload(string $path, int $sizeLimit = 10485760): array
    {
        $ok = is_file($path) && strtolower(pathinfo($path, PATHINFO_EXTENSION)) === 'xlsx'
            && filesize($path) <= $sizeLimit;
        return ['ok'=>$ok, 'message'=>$ok ? '' : 'Gunakan file XLSX yang tersedia, maksimal 10 MB.'];
    }

    public function previewFile(string $path, ?string $reportDate = null): array
    {
        $report = ['file'=>basename($path), 'checksum'=>'', 'sheet_name'=>'', 'rows_total'=>0,
            'valid_rows'=>0, 'invalid_rows'=>0, 'data_start_row'=>6, 'header_rows'=>[2,3,4,5],
            'data_rows'=>[], 'preview'=>[], 'errors'=>[], 'dry_run'=>true, 'period'=>$reportDate];
        try {
            if (!self::validateUpload($path)['ok']) { throw new RuntimeException(self::validateUpload($path)['message']); }
            $report['checksum'] = hash_file('sha256', $path);
            $sheet = $this->readWorksheetRows($path);
            $report['sheet_name'] = $sheet['sheet_name'];
            $report['rows_total'] = count($sheet['rows']);
            if ($reportDate === null || !self::validDate($reportDate)) {
                $report['errors'][] = ['row_number'=>0, 'message'=>'Pilih tanggal laporan valid (YYYY-MM-DD); periode tidak ditebak dari indikator.'];
            }
            $regionHeader = $this->normalizeText($sheet['rows'][3]['B']['raw'] ?? '');
            if (!in_array($regionHeader, ['daerah','dareah','wilayah'], true)) {
                throw new RuntimeException('Header wilayah B3 tidak sesuai template Jawa Barat.');
            }
            foreach ($this->mapping as $column => $definition) {
                $header = '';
                foreach ([2,3,4,5] as $row) { $header .= ' ' . ($sheet['rows'][$row][$column]['raw'] ?? ''); }
                if (strpos($this->normalizeText($header), $definition[3]) === false) {
                    throw new RuntimeException('Header kolom ' . $column . ' tidak sesuai mapping: ' . $definition[1]);
                }
            }
            $regions = [];
            foreach ($this->pdo->query('SELECT code,name FROM regions WHERE is_active=1')->fetchAll() as $region) {
                $regions[$this->normalizeText($region['name'])][] = $region['code'];
            }
            $seen = [];
            foreach ($sheet['rows'] as $rowNumber => $cells) {
                if ($rowNumber < 6) { continue; }
                $name = trim($cells['B']['raw'] ?? '');
                if ($name === '') { continue; }
                // Template totals/notes have no numeric row number; never import totals as regions.
                if (!ctype_digit(trim($cells['A']['raw'] ?? ''))) { continue; }
                $errors = [];
                $matches = $regions[$this->normalizeText($name)] ?? [];
                $code = count($matches) === 1 ? $matches[0] : null;
                if ($code === null) { $errors[] = 'Wilayah belum terdaftar atau ambigu: ' . $name; }
                if ($code !== null && isset($seen[$code])) { $errors[] = 'Wilayah duplikat: ' . $name; }
                if ($code !== null) { $seen[$code] = true; }
                $values = [];
                foreach ($this->mapping as $column => $definition) {
                    try {
                        $cell = $cells[$column] ?? ['raw'=>'','numeric'=>false,'percent'=>false];
                        $values[$definition[0]] = $this->parseCell($cell, $definition[2]);
                    } catch (RuntimeException $exception) {
                        $errors[] = $column . ': ' . $exception->getMessage();
                    }
                }
                if ($errors) {
                    $report['invalid_rows']++;
                    foreach ($errors as $message) { $report['errors'][] = ['row_number'=>$rowNumber,'message'=>$message]; }
                } else { $report['valid_rows']++; }
                $entry = ['row_number'=>$rowNumber,'region_name'=>$name,'region_code'=>$code,
                    'period'=>$reportDate,'values'=>$values,'raw_cells'=>$cells];
                $report['data_rows'][] = $entry;
                if (count($report['preview']) < 10) {
                    $report['preview'][] = ['row_number'=>$rowNumber,'region'=>$name,'period'=>$reportDate,'values'=>$values];
                }
            }
            if (!$report['data_rows']) { $report['errors'][] = ['row_number'=>0,'message'=>'Tidak ada baris wilayah untuk diimpor.']; }
        } catch (Throwable $exception) {
            $report['errors'][] = ['row_number'=>0,'message'=>$exception instanceof PDOException ? 'Master wilayah tidak dapat dibaca.' : $exception->getMessage()];
        }
        $report['status'] = $report['errors'] ? 'failed' : 'preview_ok';
        return $report;
    }

    public function importFile(string $path, int $uploadedBy = 0, bool $dryRun = false, ?string $reportDate = null): array
    {
        $report = $this->previewFile($path, $reportDate);
        if ($dryRun || $report['errors']) { return $report; }
        $report['dry_run'] = false;
        if ($this->pdo->getAttribute(PDO::ATTR_DRIVER_NAME) !== 'mysql') {
            throw new RuntimeException('Impor membutuhkan MySQL; tidak ada fallback SQLite.');
        }
        $this->pdo->beginTransaction();
        try {
            $this->pdo->prepare('INSERT INTO data_sources (code,name,source_type) VALUES (?,?,?) ON DUPLICATE KEY UPDATE name=VALUES(name)')
                ->execute(['excel_input_jawa_barat','Input Data Jawa Barat','excel']);
            $sourceId = $this->lookupId('SELECT id FROM data_sources WHERE code=?', ['excel_input_jawa_barat']);
            $this->pdo->prepare('INSERT INTO report_periods (period_code,period_label,period_type,year_number,month_number,period_start,period_end) VALUES (?,?,?,?,?,?,?) ON DUPLICATE KEY UPDATE period_label=VALUES(period_label)')
                ->execute([$reportDate,$reportDate,'custom',(int)substr($reportDate,0,4),(int)substr($reportDate,5,2),$reportDate,$reportDate]);
            $periodId = $this->lookupId('SELECT id FROM report_periods WHERE period_code=?', [$reportDate]);
            $this->pdo->prepare('INSERT INTO import_batches (source_id,file_name,uploaded_by,status,total_rows,started_at) VALUES (?,?,?,?,?,NOW())')
                ->execute([$sourceId,basename($path),$uploadedBy ?: null,'processing',count($report['data_rows'])]);
            $batchId = (int)$this->pdo->lastInsertId();
            $this->pdo->prepare('INSERT INTO indicator_groups (code,name) VALUES (?,?) ON DUPLICATE KEY UPDATE name=VALUES(name)')
                ->execute(['excel_import','Indikator workbook Jawa Barat']);
            $groupId = $this->lookupId('SELECT id FROM indicator_groups WHERE code=?', ['excel_import']);
            $indicatorIds = [];
            foreach ($this->mapping as $column => $definition) {
                [$code,$name,$unit] = $definition;
                $type = in_array($unit,['text','status','date'],true) ? 'string' : ($unit === 'count' ? 'integer' : 'decimal');
                $this->pdo->prepare('INSERT INTO indicators (group_id,code,name,short_name,unit,data_type,source_field_name,source_owner) VALUES (?,?,?,?,?,?,?,?) ON DUPLICATE KEY UPDATE code=VALUES(code)')
                    ->execute([$groupId,$code,$name,$code,$unit,$type,$column,'Excel']);
                $id = $this->lookupId('SELECT id FROM indicators WHERE code=?', [$code]);
                $meta = $this->pdo->prepare('SELECT unit,data_type FROM indicators WHERE id=?');
                $meta->execute([$id]);
                $existing = $meta->fetch();
                if ($existing['unit'] !== $unit) { throw new RuntimeException('Satuan master indikator tidak cocok: ' . $code); }
                $indicatorIds[$code] = $id;
            }
            foreach ($report['data_rows'] as $row) {
                $regionId = $this->lookupId('SELECT id FROM regions WHERE code=?', [$row['region_code']]);
                $this->pdo->prepare('INSERT INTO import_batch_rows (batch_id,row_number,region_code,period_label,raw_row,normalized_flag) VALUES (?,?,?,?,?,1)')
                    ->execute([$batchId,$row['row_number'],$row['region_code'],$reportDate,self::json($row['raw_cells'])]);
                $this->pdo->prepare('INSERT INTO report_snapshots (region_id,period_id,source_id,submitted_by,imported_batch_id,status,raw_summary_json) VALUES (?,?,?,?,?,?,?) ON DUPLICATE KEY UPDATE submitted_by=VALUES(submitted_by), imported_batch_id=VALUES(imported_batch_id), status=VALUES(status), raw_summary_json=VALUES(raw_summary_json), updated_at=NOW()')
                    ->execute([$regionId,$periodId,$sourceId,$uploadedBy ?: null,$batchId,'draft',self::json(['file_checksum'=>$report['checksum'],'row_number'=>$row['row_number']])]);
                $snapshotId = $this->lookupId('SELECT id FROM report_snapshots WHERE region_id=? AND period_id=? AND source_id=?', [$regionId,$periodId,$sourceId]);
                foreach ($this->mapping as $column => $definition) {
                    [$code,$name,$unit] = $definition;
                    $value = $row['values'][$code];
                    $numeric = !in_array($unit,['text','status','date'],true) && $value !== null;
                    $text = !$numeric && $value !== null ? (string)$value : null;
                    $this->pdo->prepare('INSERT INTO indicator_values (snapshot_id,indicator_id,region_id,period_id,source_id,value_decimal,value_percent,value_text,unit,status_text,is_derived,raw_value) VALUES (?,?,?,?,?,?,?,?,?,?,?,?) ON DUPLICATE KEY UPDATE value_decimal=VALUES(value_decimal),value_percent=VALUES(value_percent),value_text=VALUES(value_text),unit=VALUES(unit),status_text=VALUES(status_text),is_derived=VALUES(is_derived),raw_value=VALUES(raw_value),updated_at=NOW()')
                        ->execute([$snapshotId,$indicatorIds[$code],$regionId,$periodId,$sourceId,$numeric ? $value : null,$numeric && $unit==='percent' ? $value : null,$text,$unit,$unit==='status' ? $text : null,!empty($row['raw_cells'][$column]['formula']) ? 1 : 0,self::json($row['raw_cells'][$column] ?? ['raw'=>''])]);
                }
            }
            $this->pdo->prepare('UPDATE import_batches SET status=?,imported_rows=?,finished_at=NOW() WHERE id=?')->execute(['completed',$report['valid_rows'],$batchId]);
            $this->pdo->prepare('INSERT INTO import_audit_logs (batch_id,event_type,log_level,message,context_json) VALUES (?,?,?,?,?)')
                ->execute([$batchId,'import_completed','info','Workbook diimpor sebagai draft.',self::json(['checksum'=>$report['checksum'],'period'=>$reportDate])]);
            $this->pdo->commit();
            $report['batch_id'] = $batchId;
            $report['status'] = 'imported';
        } catch (Throwable $exception) {
            $this->pdo->rollBack();
            $report['status'] = 'failed';
            $report['errors'][] = ['row_number'=>0,'message'=>$exception instanceof PDOException ? 'Impor gagal karena integritas database; seluruh perubahan dibatalkan.' : $exception->getMessage()];
        }
        return $report;
    }

    private function lookupId(string $sql, array $parameters): int
    {
        $query = $this->pdo->prepare($sql); $query->execute($parameters);
        $id = $query->fetchColumn();
        if ($id === false) { throw new RuntimeException('Referensi impor tidak ditemukan.'); }
        return (int)$id;
    }

    private static function json($value): string
    {
        return json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
    }

    private static function validDate(string $value): bool
    {
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/D',$value)) { return false; }
        [$year,$month,$day] = array_map('intval',explode('-',$value));
        return $year >= 1000 && checkdate($month,$day,$year);
    }

    private function normalizeText(string $value): string
    {
        return trim(preg_replace('/\s+/',' ',preg_replace('/[^a-z0-9%]+/',' ',strtolower($value))));
    }

    private function parseCell(array $cell, string $unit)
    {
        $raw = trim($cell['raw']);
        if (!empty($cell['error']) || (!empty($cell['formula']) && $raw === '')) { throw new RuntimeException('Formula/error Excel tidak memiliki nilai cache yang valid.'); }
        if ($raw === '' || in_array(strtolower($raw),['-','na','n/a'],true)) { return null; }
        if ($unit === 'text') { return $raw; }
        if ($unit === 'status') {
            $status = $this->normalizeText($raw);
            return str_replace(' ','_',$status);
        }
        if ($unit === 'date') {
            if (!preg_match('/^(19|20)\d{2}$/D',$raw)) { throw new RuntimeException('Tahun tidak valid.'); }
            return $raw;
        }
        if ($cell['numeric']) {
            if (!is_numeric($raw)) { throw new RuntimeException('Angka Excel tidak valid.'); }
            $value = (float)$raw;
            if ($unit === 'percent' && $cell['percent']) { $value *= 100; }
        } else {
            $clean = preg_replace('/\s+/u','',str_ireplace(['rp','idr','ha','m2','%'],'',$raw));
            if (preg_match('/^\d{1,3}(?:[.,]\d{3})+(?:[.,]\d+)?$/D',$clean)) {
                if (strpos($clean,',') !== false && strpos($clean,'.') !== false) {
                    $decimal = strrpos($clean,',') > strrpos($clean,'.') ? ',' : '.';
                    $clean = str_replace($decimal === ',' ? '.' : ',','',$clean);
                    $clean = str_replace($decimal,'.',$clean);
                } elseif (preg_match('/^\d{1,3}(?:[.,]\d{3})+$/D',$clean)) {
                    $clean = str_replace([',','.'],'',$clean);
                }
            } else { $clean = str_replace(',','.',$clean); }
            if (!is_numeric($clean)) { throw new RuntimeException('Angka teks tidak valid.'); }
            $value = (float)$clean;
        }
        if (!is_finite($value) || $value < 0 || $value >= 1.0e14) { throw new RuntimeException('Angka di luar rentang DECIMAL(18,4).'); }
        if ($unit === 'percent' && $value > 100) { throw new RuntimeException('Persentase melebihi 100.'); }
        if ($unit === 'count' && floor($value) !== $value) { throw new RuntimeException('Jumlah harus bilangan bulat.'); }
        return $value;
    }

    private function xml(ZipArchive $zip, string $entry): SimpleXMLElement
    {
        $raw = $zip->getFromName($entry);
        if ($raw === false || stripos($raw,'<!DOCTYPE') !== false || stripos($raw,'<!ENTITY') !== false) {
            throw new RuntimeException('Bagian workbook tidak tersedia atau XML tidak aman: ' . $entry);
        }
        $previous = libxml_use_internal_errors(true);
        try { $xml = simplexml_load_string($raw, SimpleXMLElement::class, LIBXML_NONET); }
        finally { libxml_clear_errors(); libxml_use_internal_errors($previous); }
        if ($xml === false) { throw new RuntimeException('XML workbook tidak valid.'); }
        return $xml;
    }

    private function readWorksheetRows(string $path): array
    {
        $zip = new ZipArchive();
        if ($zip->open($path) !== true) { throw new RuntimeException('Arsip XLSX tidak valid.'); }
        try {
            $expanded = 0;
            for ($i=0;$i<$zip->numFiles;$i++) { $expanded += $zip->statIndex($i)['size']; }
            if ($expanded > 67108864 || $zip->numFiles > 2000) { throw new RuntimeException('Workbook melampaui batas ukuran ekstraksi.'); }
            $strings=[];
            if ($zip->locateName('xl/sharedStrings.xml') !== false) {
                foreach ($this->xml($zip,'xl/sharedStrings.xml')->si as $si) {
                    $strings[] = implode('',array_map('strval',$si->xpath('.//*[local-name()="t"]')));
                }
            }
            $formats = []; $percentStyles = [];
            if ($zip->locateName('xl/styles.xml') !== false) {
                $styles = $this->xml($zip,'xl/styles.xml');
                foreach ($styles->numFmts->numFmt as $format) { $formats[(int)$format['numFmtId']] = (string)$format['formatCode']; }
                $i=0;
                foreach ($styles->cellXfs->xf as $style) {
                    $id=(int)$style['numFmtId'];
                    $percentStyles[$i++] = in_array($id,[9,10],true) || strpos($formats[$id] ?? '', '%') !== false;
                }
            }
            $workbook = $this->xml($zip,'xl/workbook.xml');
            $relations = $this->xml($zip,'xl/_rels/workbook.xml.rels');
            $sheet = $workbook->sheets->sheet[0];
            if (!$sheet) { throw new RuntimeException('Worksheet tidak ditemukan.'); }
            $id = (string)$sheet->attributes('http://schemas.openxmlformats.org/officeDocument/2006/relationships')['id'];
            $target = '';
            foreach ($relations->Relationship as $relation) {
                if ((string)$relation['Id'] === $id && (string)$relation['TargetMode'] !== 'External') { $target=(string)$relation['Target']; break; }
            }
            if ($target === '' || strpos($target,'..') !== false || strpos($target,'\\') !== false) { throw new RuntimeException('Relasi worksheet tidak valid.'); }
            $entry = $target[0] === '/' ? ltrim($target,'/') : 'xl/' . $target;
            $xml = $this->xml($zip,$entry);
            $rows=[];
            foreach ($xml->sheetData->row as $row) {
                $cells=[];
                foreach ($row->c as $cell) {
                    if (!preg_match('/^([A-Z]+)\d+$/D',(string)$cell['r'],$match)) { continue; }
                    $type=(string)$cell['t']; $raw=(string)$cell->v;
                    if ($type==='s') { $raw=$strings[(int)$raw] ?? ''; }
                    if ($type==='inlineStr') { $raw=implode('',array_map('strval',$cell->xpath('.//*[local-name()="t"]'))); }
                    $cells[$match[1]]=['raw'=>$raw,'numeric'=>$type==='' || $type==='n',
                        'percent'=>$percentStyles[(int)$cell['s']] ?? false,'formula'=>(string)$cell->f,'error'=>$type==='e'];
                }
                $rows[(int)$row['r']]=$cells;
            }
            return ['sheet_name'=>(string)$sheet['name'],'rows'=>$rows];
        } finally { $zip->close(); }
    }
}
