<?php
declare(strict_types=1);

final class DashboardDataService
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function getDashboard(string $requestedProvinceCode = '', ?string $requestedDate = null): array
    {
        $provinces = $this->fetchProvinces();
        $provinceCode = $this->resolveProvinceCode($requestedProvinceCode, $provinces);
        $reportDate = $this->resolveReportDate($provinceCode, $requestedDate);

        return array(
            'provinces' => $provinces,
            'province_code' => $provinceCode,
            'province_name' => $this->getProvinceName($provinces, $provinceCode),
            'report_date' => $reportDate,
            'summary' => $this->fetchSummary($provinceCode, $reportDate),
            'rows' => $this->fetchRows($provinceCode, $reportDate),
        );
    }

    public function fetchProvinces(): array
    {
        $statement = $this->pdo->query('SELECT code, name FROM provinces WHERE is_active = 1 ORDER BY name');
        return $statement->fetchAll();
    }

    public function resolveProvinceCode(string $requestedProvinceCode, array $provinces): string
    {
        if ($requestedProvinceCode !== '' && preg_match('/^\d{2}$/', $requestedProvinceCode) === 1) {
            foreach ($provinces as $province) {
                if (($province['code'] ?? '') === $requestedProvinceCode) {
                    return $requestedProvinceCode;
                }
            }
        }

        return $provinces[0]['code'] ?? '';
    }

    public function resolveReportDate(string $provinceCode, ?string $requestedDate): string
    {
        if ($requestedDate !== null) {
            if (preg_match('/^\d{4}-\d{2}-\d{2}$/D', $requestedDate) !== 1) {
                throw new InvalidArgumentException('Periode harus berupa tanggal valid dengan format YYYY-MM-DD.');
            }
            list($year, $month, $day) = array_map('intval', explode('-', $requestedDate));
            if ($year < 1000 || !checkdate($month, $day, $year)) {
                throw new InvalidArgumentException('Periode harus berupa tanggal valid dengan format YYYY-MM-DD.');
            }
            return $requestedDate;
        }

        $statement = $this->pdo->prepare('SELECT MAX(m.report_date) FROM metric_snapshots m JOIN regions r ON r.code = m.region_code JOIN provinces p ON p.id = r.province_id WHERE p.code = :province_code');
        $statement->execute(array(':province_code' => $provinceCode));
        $reportDate = $statement->fetchColumn();

        return is_string($reportDate) && $reportDate !== '' ? $reportDate : date('Y-m-d');
    }

    public function fetchSummary(string $provinceCode, string $reportDate): array
    {
        $effectiveMetrics = ' FROM metric_snapshots m JOIN regions r ON r.code = m.region_code JOIN provinces p ON p.id = r.province_id WHERE p.code = :province_code AND m.report_date = :report_date AND NOT (m.source = \'kkp\' AND EXISTS (SELECT 1 FROM metric_snapshots pemda WHERE pemda.region_code = m.region_code AND pemda.report_date = m.report_date AND pemda.source = \'pemda\')) ';
        $statement = $this->pdo->prepare('SELECT COALESCE(SUM(m.total_records), 0) AS total_records, COALESCE(SUM(m.validated_stage_1), 0) AS stage_1, COALESCE(SUM(m.validated_stage_2), 0) AS stage_2, COALESCE(SUM(m.area_hectare), 0) AS area_hectare, COUNT(DISTINCT m.region_code) AS reporting_regions' . $effectiveMetrics);

        $statement->execute(array(':province_code' => $provinceCode, ':report_date' => $reportDate));
        $summary = $statement->fetch();

        return is_array($summary) ? $summary : array(
            'total_records' => 0,
            'stage_1' => 0,
            'stage_2' => 0,
            'area_hectare' => 0,
            'reporting_regions' => 0,
        );
    }

    public function fetchRows(string $provinceCode, string $reportDate): array
    {
        $effectiveMetrics = ' FROM metric_snapshots m JOIN regions r ON r.code = m.region_code JOIN provinces p ON p.id = r.province_id WHERE p.code = :province_code AND m.report_date = :report_date AND NOT (m.source = \'kkp\' AND EXISTS (SELECT 1 FROM metric_snapshots pemda WHERE pemda.region_code = m.region_code AND pemda.report_date = m.report_date AND pemda.source = \'pemda\')) ';
        $statement = $this->pdo->prepare('SELECT r.code, r.name, m.total_records, m.validated_stage_1, m.validated_stage_2, m.area_hectare, m.source' . $effectiveMetrics . ' ORDER BY r.name');
        $statement->execute(array(':province_code' => $provinceCode, ':report_date' => $reportDate));

        return $statement->fetchAll();
    }

    public static function percentOf($part, $total): int
    {
        $rawPart = is_numeric($part) ? (float) $part : 0.0;
        $rawTotal = is_numeric($total) ? (float) $total : 0.0;

        if ($rawTotal <= 0.0) {
            return 0;
        }

        return (int) round(($rawPart / $rawTotal) * 100.0);
    }

    private function getProvinceName(array $provinces, string $provinceCode): string
    {
        foreach ($provinces as $province) {
            if (($province['code'] ?? '') === $provinceCode) {
                return (string) ($province['name'] ?? 'Provinsi');
            }
        }

        return 'Provinsi';
    }
}
