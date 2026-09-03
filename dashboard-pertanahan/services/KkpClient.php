<?php
declare(strict_types=1);

final class KkpClient
{
    private $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function syncProvince(string $provinceCode, string $reportDate): int
    {
        $records = $this->fetchSnapshots($provinceCode, $reportDate);
        $upsert = $this->pdo->prepare('INSERT INTO metric_snapshots (region_code, report_date, total_records, validated_stage_1, validated_stage_2, area_hectare, source) VALUES (?, ?, ?, ?, ?, ?, "kkp") ON CONFLICT(region_code, report_date, source) DO UPDATE SET total_records = excluded.total_records, validated_stage_1 = excluded.validated_stage_1, validated_stage_2 = excluded.validated_stage_2, area_hectare = excluded.area_hectare, created_at = CURRENT_TIMESTAMP');
        $this->pdo->beginTransaction();
        try {
            foreach ($records as $record) {
                $upsert->execute(array($record['region_code'], $record['report_date'], $record['total_records'], $record['validated_stage_1'], $record['validated_stage_2'], $record['area_hectare']));
            }
            $this->pdo->commit();
        } catch (Throwable $exception) {
            $this->pdo->rollBack();
            throw $exception;
        }
        return count($records);
    }

    public function fetchSnapshots(string $provinceCode, string $reportDate): array
    {
        if (KKP_MODE !== 'live') {
            return $this->fetchMockSnapshots($provinceCode, $reportDate);
        }
        if (!kkp_live_configured()) {
            throw new RuntimeException('Konfigurasi KKP belum lengkap. Isi KKP_API_BASE_URL dan KKP_API_BEARER_TOKEN atau gunakan KKP_MODE=mock.');
        }
        if (!function_exists('curl_init')) {
            throw new RuntimeException('Ekstensi PHP cURL belum aktif di server.');
        }

        $url = KKP_API_BASE_URL . KKP_METRICS_ENDPOINT . '?' . http_build_query(array('province_code' => $provinceCode, 'report_date' => $reportDate));
        $curl = curl_init($url);
        curl_setopt_array($curl, array(
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 20,
            CURLOPT_HTTPHEADER => array('Accept: application/json', 'Authorization: Bearer ' . KKP_API_BEARER_TOKEN)
        ));
        $body = curl_exec($curl);
        $status = (int) curl_getinfo($curl, CURLINFO_HTTP_CODE);
        $error = curl_error($curl);
        curl_close($curl);
        if ($body === false || $status < 200 || $status >= 300) {
            throw new RuntimeException('Permintaan ke KKP gagal' . ($error ? ': ' . $error : ' (HTTP ' . $status . ').'));
        }
        $payload = json_decode($body, true);
        if (!is_array($payload)) {
            throw new RuntimeException('Respons KKP bukan JSON yang valid.');
        }
        return $this->normalizePayload($payload, $reportDate);
    }

    private function fetchMockSnapshots(string $provinceCode, string $reportDate): array
    {
        $statement = $this->pdo->prepare('SELECT m.region_code, m.report_date, m.total_records, m.validated_stage_1, m.validated_stage_2, m.area_hectare FROM metric_snapshots m JOIN regions r ON r.code = m.region_code WHERE r.province_code = ? AND m.report_date = ? AND m.source = "kkp" ORDER BY r.name');
        $statement->execute(array($provinceCode, $reportDate));
        return $statement->fetchAll();
    }

    private function normalizePayload(array $payload, string $fallbackDate): array
    {
        $rows = isset($payload['data']) && is_array($payload['data']) ? $payload['data'] : $payload;
        $normalized = array();
        foreach ($rows as $row) {
            if (!is_array($row)) {
                continue;
            }
            $record = array(
                'region_code' => (string) ($row['region_code'] ?? ''),
                'report_date' => (string) ($row['report_date'] ?? $fallbackDate),
                'total_records' => filter_var($row['total_records'] ?? null, FILTER_VALIDATE_INT, array('options' => array('min_range' => 0))),
                'validated_stage_1' => filter_var($row['validated_stage_1'] ?? null, FILTER_VALIDATE_INT, array('options' => array('min_range' => 0))),
                'validated_stage_2' => filter_var($row['validated_stage_2'] ?? null, FILTER_VALIDATE_INT, array('options' => array('min_range' => 0))),
                'area_hectare' => filter_var($row['area_hectare'] ?? null, FILTER_VALIDATE_FLOAT)
            );
            if ($record['region_code'] === '' || $record['total_records'] === false || $record['validated_stage_1'] === false || $record['validated_stage_2'] === false || $record['area_hectare'] === false || $record['validated_stage_1'] > $record['total_records'] || $record['validated_stage_2'] > $record['validated_stage_1']) {
                throw new RuntimeException('Format indikator dari KKP tidak sesuai kontrak kanonis. Periksa pemetaan field.');
            }
            $normalized[] = $record;
        }
        return $normalized;
    }
}
