<?php
declare(strict_types=1);

require_once __DIR__ . '/../db.php';
require_login();

header('Content-Type: application/json; charset=utf-8');

$provinceCode = $_GET['province'] ?? '';
$reportDate = $_GET['date'] ?? '';
if ($provinceCode === '' || !preg_match('/^\\d{2}$/', $provinceCode) || !preg_match('/^\\d{4}-\\d{2}-\\d{2}$/', $reportDate)) {
    http_response_code(422);
    echo json_encode(array('error' => 'Parameter province dan date wajib diisi dengan format yang benar.'));
    exit;
}

$stmt = db()->prepare('SELECT r.code AS region_code, r.name AS region_name, m.report_date, m.total_records, m.validated_stage_1, m.validated_stage_2, m.area_hectare, m.source
    FROM metric_snapshots m JOIN regions r ON r.code = m.region_code
    WHERE r.province_code = ? AND m.report_date = ? AND m.source = "kkp" ORDER BY r.name');
$stmt->execute(array($provinceCode, $reportDate));

echo json_encode(array(
    'meta' => array('source' => 'kkp-adapter-demo', 'province' => $provinceCode, 'report_date' => $reportDate),
    'data' => $stmt->fetchAll()
), JSON_PRETTY_PRINT);

