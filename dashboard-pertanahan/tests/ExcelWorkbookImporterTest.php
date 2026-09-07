<?php
declare(strict_types=1);
require_once __DIR__ . '/../database/Connection.php';
require_once __DIR__ . '/../services/ExcelWorkbookImporter.php';
$pdo = DatabaseConnection::create();
if (getenv('APP_ENV') !== 'testing' || substr(getenv('DB_NAME'), -5) !== '_test') {
    throw new RuntimeException('Set APP_ENV=testing dan DB_NAME berakhiran _test.');
}
$checks = 0;
function checkImport(bool $ok, string $message): void
{
    global $checks;
    if (!$ok) { throw new RuntimeException($message); }
    $checks++;
}
function countRows(PDO $pdo, string $table): int
{
    if (!in_array($table,['report_snapshots','indicator_values','import_batches','metric_snapshots'],true)) { throw new LogicException('Table not allowed'); }
    return (int)$pdo->query('SELECT COUNT(*) FROM ' . $table)->fetchColumn();
}
$importer = new ExcelWorkbookImporter($pdo);
$source = __DIR__ . '/../data/source/Input Data Jawa Barat.xlsx';
$hash = hash_file('sha256',$source);
$preview = $importer->previewFile($source,'2099-01-01');
checkImport($preview['status']==='preview_ok' && $preview['valid_rows']===25, 'Real workbook preview: ' . json_encode($preview['errors']));
$first = $preview['preview'][0]['values'];
checkImport($first['sertifikat_pct']===51.0, 'Excel percentage must scale 0.51 to 51');
checkImport($first['cakupan_znt']===97.68, 'Unformatted percent already uses 0-100');
checkImport($first['nib_nop_status']==='belum_terkoneksi', 'Negative status preserved');
checkImport($first['aset_pemda_sudah_luas_m2']===3900359.71, 'Native decimal preserved');
checkImport($preview['preview'][1]['values']['aset_pemda_sudah_luas_m2']===1868493.0, 'Text thousands normalized');
checkImport($first['nib_nop_keterangan']==='NIB > NOP', 'Raw text preserved');
checkImport($importer->previewFile($source)['status']==='failed', 'Missing period rejected');
checkImport($importer->previewFile($source,'2099-02-29')['status']==='failed', 'Invalid calendar rejected');
$metrics = countRows($pdo,'metric_snapshots');
$result = $importer->importFile($source,0,false,'2099-01-01');
checkImport($result['status']==='imported','Actual import failed: ' . json_encode($result['errors']));
$snapshots = countRows($pdo,'report_snapshots'); $values = countRows($pdo,'indicator_values');
$result = $importer->importFile($source,0,false,'2099-01-01');
checkImport($result['status']==='imported' && countRows($pdo,'report_snapshots')===$snapshots && countRows($pdo,'indicator_values')===$values,'Repeat import must not duplicate snapshots or values');
checkImport(countRows($pdo,'metric_snapshots')===$metrics,'Excel indicators must not overwrite dashboard metrics');
$query=$pdo->prepare('SELECT v.value_decimal,v.raw_value,s.status FROM indicator_values v JOIN indicators i ON i.id=v.indicator_id JOIN report_snapshots s ON s.id=v.snapshot_id JOIN regions r ON r.id=v.region_id JOIN report_periods p ON p.id=v.period_id WHERE r.code=? AND i.code=? AND p.period_code=?');
$query->execute(['3204','sertifikat_pct','2099-01-01']); $stored=$query->fetch();
checkImport((float)$stored['value_decimal']===51.0 && json_decode($stored['raw_value'],true)['raw']==='0.51','Stored value and raw cell must both survive');
checkImport($stored['status']==='draft','Imported data is not automatically approved');
$before = countRows($pdo,'import_batches');
$failure=$importer->importFile($source,PHP_INT_MAX,false,'2099-01-02');
checkImport($failure['status']==='failed' && countRows($pdo,'import_batches')===$before,'Foreign-key failure rolls back batch');
$dir=__DIR__ . '/../tmp/importer-tests';
if (!is_dir($dir)) { mkdir($dir,0700,true); }
$fixture=$dir . '/blank-cell.xlsx'; copy($source,$fixture);
$zip=new ZipArchive(); $zip->open($fixture);
$xml=simplexml_load_string($zip->getFromName('xl/worksheets/sheet1.xml'));
foreach ($xml->sheetData->row as $row) {
    foreach ($row->c as $cell) {
        if ((string)$cell['r']==='D6') { unset($cell->v); unset($cell->f); }
    }
}
$zip->addFromString('xl/worksheets/sheet1.xml',$xml->asXML()); $zip->close();
$blank=$importer->importFile($fixture,0,false,'2099-01-01');
checkImport($blank['status']==='imported','Blank-cell reimport');
$query->execute(['3204','lahan_sertipikat','2099-01-01']);
checkImport($query->fetch()['value_decimal']===null,'Blank-cell reimport clears stale value');
// Restore the test period to the source values for repeatable follow-up checks.
checkImport($importer->importFile($source,0,false,'2099-01-01')['status']==='imported','Restore test period');
checkImport(hash_file('sha256',$source)===$hash,'Source workbook unchanged');
echo 'PASS: ' . $checks . " importer checks on MySQL\n";
