<?php
declare(strict_types=1);

require_once __DIR__ . '/../services/DashboardDataService.php';

// SQLite is explicitly used only as an isolated, in-memory test fixture.
$pdo = new PDO('sqlite::memory:', null, null, array(
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
));
$pdo->exec('CREATE TABLE provinces (id INTEGER, code TEXT, name TEXT, is_active INTEGER)');
$pdo->exec('CREATE TABLE regions (code TEXT, name TEXT, region_type TEXT, province_id INTEGER)');
$pdo->exec('CREATE TABLE metric_snapshots (region_code TEXT, report_date TEXT, total_records INTEGER, validated_stage_1 INTEGER, validated_stage_2 INTEGER, area_hectare REAL, source TEXT)');
$region = $pdo->prepare('INSERT INTO regions VALUES (?, ?, ?, ?)');
$pdo->prepare('INSERT INTO provinces VALUES (?,?,?,?)')->execute([1,'32','Jawa Barat',1]);
$region->execute(array('3273', 'Kota Bandung', 'kabupaten_kota', 1));
$metric = $pdo->prepare('INSERT INTO metric_snapshots VALUES (?, ?, ?, ?, ?, ?, ?)');
$metric->execute(array('3273', '2026-09-01', 10, 8, 5, 2.5, 'kkp'));
$metric->execute(array('3273', '2026-09-01', 20, 15, 10, 5, 'pemda'));
$service = new DashboardDataService($pdo);
$checks = 0;
function expect($expected, $actual, string $message): void
{
    global $checks;
    if ($expected !== $actual) {
        throw new RuntimeException($message);
    }
    $checks++;
}

$empty = $service->getDashboard('32', '2026-08-01');
expect('2026-08-01', $empty['report_date'], 'Explicit empty period must be retained');
expect(array(), $empty['rows'], 'Empty period must have no rows');
foreach (array('total_records', 'stage_1', 'stage_2', 'area_hectare', 'reporting_regions') as $key) {
    expect(0.0, (float) $empty['summary'][$key], 'Empty summary: ' . $key);
}
expect('2026-09-01', $service->resolveReportDate('32', null), 'Absent period uses latest');
expect('2024-02-29', $service->resolveReportDate('32', '2024-02-29'), 'Leap day is valid');
foreach (array('', '2026-02-29', '2026-04-31', '2026-13-01', '0000-01-01', '2026-9-01', "2026-09-01\n", 'invalid') as $invalid) {
    $rejected = false;
    try {
        $service->resolveReportDate('32', $invalid);
    } catch (InvalidArgumentException $exception) {
        $rejected = true;
    }
    expect(true, $rejected, 'Invalid period must not fall back');
}
$current = $service->getDashboard('32', '2026-09-01');
expect(20, (int) $current['summary']['total_records'], 'Pemda replaces KKP');
expect(1, count($current['rows']), 'No duplicate region');
expect(0, DashboardDataService::percentOf(0, 0), 'Zero denominator');
$pdo->exec('DELETE FROM metric_snapshots');
expect(date('Y-m-d'), $service->resolveReportDate('32', null), 'Empty database default');
echo 'PASS: ' . $checks . ' checks' . PHP_EOL;
