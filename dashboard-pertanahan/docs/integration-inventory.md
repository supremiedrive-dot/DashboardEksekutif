# Inventaris Integrasi Data Dashboard ke Database

## Tanggal
2026-09-05

## 1. Status komponen yang sudah terkoneksi database

### ✅ Dashboard utama (`dashboard.php`)
- **Service layer**: `services/DashboardDataService`
- **Fungsi**:
  - `getDashboard(provinceCode, requestedDate)` → mengabstraksi semua query
  - `fetchProvinces()` → daftar provinsi dari master regions
  - `resolveProvinceCode()` → validasi dan fallback ke provinsi pertama
  - `resolveReportDate()` → validasi dan fallback ke tanggal terbaru atau hari ini
  - `fetchSummary()` → agregasi server-side KPI: total_records, stage_1, stage_2, area_hectare, reporting_regions
  - `fetchRows()` → per-wilayah data dengan filter periode
  - `percentOf()` → perhitungan persentase aman (pembagi nol ditangani)
- **Query terhubung**:
  - `SELECT code, name FROM regions WHERE type = "provinsi"`
  - `SELECT 1 FROM metric_snapshots WHERE ...` (validasi periode)
  - `SELECT MAX(report_date) FROM metric_snapshots WHERE ...` (periode terbaru)
  - `SELECT SUM(...), COUNT(...) FROM metric_snapshots ... WHERE ...` (agregasi KPI)
  - `SELECT ... FROM metric_snapshots ... ORDER BY name` (tabel per-wilayah)
- **Keamanan**:
  - Semua parameter provinsi/tanggal divalidasi dengan regex
  - Prepared statements untuk semua query
  - Fallback aman saat periode kosong (display state kosong, bukan error)
  - Pembagi nol ditangani di `percentOf()`: return 0 jika total <= 0
- **Indeks yang dipakai**: `idx_regions_province_type`, `idx_metric_snapshots_report_region`
- **State UI**: Normal dengan data, state kosong saat periode tidak tersedia

### ✅ API KKP (`api/kkp.php`)
- **Fungsi**: Endpoint JSON snapshot KKP per provinsi dan tanggal
- **Query terhubung**:
  - `SELECT ... FROM metric_snapshots m JOIN regions r ... WHERE r.province_code = ? AND m.report_date = ? AND m.source = "kkp"`
- **Keamanan**:
  - Parameter provincia dan date divalidasi dengan regex sebelum query
  - Prepared statements
  - Output JSON dengan JSON_PRETTY_PRINT
- **Indeks yang dipakai**: `idx_metric_snapshots_source_date`
- **Response format**: 
```json
{
  "meta": {"source": "kkp-adapter-demo", "province": "32", "report_date": "2026-09-01"},
  "data": [...]
}
```

### ✅ Sinkronisasi KKP (`sync-kkp.php` + `services/KkpClient.php`)
- **Fungsi**: Sinkronisasi data dari KKP ke metric_snapshots
- **Service**: `KkpClient::syncProvince(provinceCode, reportDate)`
  - Fetch dari KKP mock atau live
  - Normalisasi payload dengan validasi stage ordering
  - Upsert ke metric_snapshots dengan transaction
  - Rollback jika gagal
- **Data validation**:
  - Memastikan `validated_stage_1 <= total_records`
  - Memastikan `validated_stage_2 <= validated_stage_1`
  - Reject jika salah satu field null atau negatif
- **Query**: `INSERT INTO metric_snapshots ... ON CONFLICT(region_code, report_date, source) DO UPDATE`
- **Keamanan**: Transaksi, prepared statements, error handling

### ✅ Input data Pemda (`input-data.php`)
- **Fungsi**: Form input data Pemda per wilayah/periode
- **Query terhubung**:
  - `SELECT r.code, r.name, p.name FROM regions r JOIN regions p ...` (list wilayah)
  - `SELECT COUNT(*) FROM regions WHERE code = ? AND type = "kabupaten_kota"` (validasi wilayah)
  - `INSERT INTO metric_snapshots ... ON CONFLICT(...)` DO UPDATE (upsert data Pemda)
- **Validasi**:
  - Kode wilayah harus ada di master regions dengan type `kabupaten_kota`
  - Tanggal format `YYYY-MM-DD`
  - Nilai numerik >= 0 dan ordering stage_2 <= stage_1 <= total_records
  - CSRF token check
- **Keamanan**: Filter input, prepared statements, transactional upsert

### ⚠️ Index.php
- **Status**: Redirect langsung ke dashboard.php (line 4 exit)
- **Catatan**: Kode query setelah exit tidak akan dijalankan; ini baik karena menghindari duplikasi query. Dashboard sudah handle semua logika.

---

## 2. Query aggregation dan null/zero handling

### Summary KPI (Dashboard)
```sql
SELECT 
  COALESCE(SUM(m.total_records), 0) AS total_records,
  COALESCE(SUM(m.validated_stage_1), 0) AS stage_1,
  COALESCE(SUM(m.validated_stage_2), 0) AS stage_2,
  COALESCE(SUM(m.area_hectare), 0) AS area_hectare,
  COUNT(DISTINCT m.region_code) AS reporting_regions
FROM metric_snapshots m 
JOIN regions r ON r.code = m.region_code 
WHERE r.province_code = ? 
  AND m.report_date = ? 
  AND NOT (m.source = "kkp" AND EXISTS (SELECT 1 FROM metric_snapshots pemda WHERE ...))
```

**Null/Zero Handling**:
- `COALESCE(..., 0)` memastikan nilai NULL menjadi 0, bukan error display
- `COUNT(DISTINCT ...)` menghindar counting duplikat wilayah
- `NOT EXISTS` clause memastikan Pemda data meng-override KKP untuk wilayah/periode yang sama

### Percentage calculation (PHP)
```php
public static function percentOf($part, $total): int
{
    $rawPart = is_numeric($part) ? (float) $part : 0.0;
    $rawTotal = is_numeric($total) ? (float) $total : 0.0;
    
    if ($rawTotal <= 0.0) {
        return 0;  // ← pembagi nol ditangani di sini
    }
    
    return (int) round(($rawPart / $rawTotal) * 100.0);
}
```

**Keamanan**:
- Cast input ke float sebelum pembagi
- Check pembagi > 0 sebelum operasi
- Round dan cast ke int untuk display

---

## 3. Data source dan prioritas

| Wilayah+Periode+Sumber | Pemda ada? | KKP ada? | Hasil final |
|---|---|---|---|
| Ya | Ya | Ya | Gunakan Pemda (override KKP) |
| Ya | Ya | Tidak | Gunakan Pemda |
| Ya | Tidak | Ya | Gunakan KKP |
| Ya | Tidak | Tidak | (Tidak ada data) |

Logika di query:
```sql
WHERE ... NOT (m.source = "kkp" AND EXISTS (SELECT 1 FROM metric_snapshots pemda WHERE pemda.region_code = m.region_code AND pemda.report_date = m.report_date AND pemda.source = "pemda"))
```

---

## 4. Database indices untuk performa

| Indeks | Tabel | Kolom | Kegunaan |
|---|---|---|---|
| `idx_regions_province_type` | regions | (province_code, type) | Filter wilayah per provinsi pada sidebar |
| `idx_metric_snapshots_report_region` | metric_snapshots | (report_date, region_code) | Filter data per tanggal/wilayah di dashboard |
| `idx_metric_snapshots_source_date` | metric_snapshots | (source, report_date) | Filter data per sumber dan tanggal di KKP API |

---

## 5. File yang masih berisi query langsung (minimal, sudah tervalidasi)

- `dashboard.php`: Menggunakan DashboardDataService untuk abstraksi
- `api/kkp.php`: Query sederhana tervalidasi dengan regex input
- `sync-kkp.php`: Menggunakan KkpClient service
- `input-data.php`: Query tervalidasi dengan filter input

**Tidak ada file yang melakukan query tanpa prepared statement atau input validation.**

---

## 6. State handling dan UX

### Loading state
- Tidak ada di MVP ini karena PHP render server-side
- Pada integrasi frontend async (future), gunakan loading spinner saat fetch

### Empty state
```php
<?php if (empty($rows)): ?>
    <tr><td colspan="6" class="empty-state">Tidak ada data untuk periode ini.</td></tr>
<?php else: ?>
    <!-- render rows -->
<?php endif; ?>
```

### Error state
- KkpClient dan DashboardDataService throw Exception jika database error
- Halaman akan menampilkan UI error yang dicetak di exception handler

---

## 7. Perubahan sejak integrasi awal

### Added
- `services/DashboardDataService.php` — abstraksi logic query dashboard
- `idx_regions_province_type`, `idx_metric_snapshots_report_region`, `idx_metric_snapshots_source_date` indices

### Modified
- `dashboard.php` — mengganti hardcoded demo data dengan DashboardDataService
- `db.php` — menambah indeks dan dokumentasi query

### Removed
- Tidak ada file atau tabel yang dihapus; semuanya backward-compatible

---

## 8. Verifikasi yang sudah dilakukan

### Static validation
- `php -l` pada 3 file utama: `dashboard.php`, `db.php`, `services/DashboardDataService.php` → No errors
- Editor diagnostics: No errors found

### Query validation
- Semua prepared statements dengan placeholder `:param` atau `?`
- Semua input divalidasi dengan regex atau filter sebelum query
- COALESCE dan pembagi-nol checks di aggregation query
- NOT EXISTS untuk logika prioritas Pemda > KKP

### Test case coverage (manual)
- Satu wilayah saja: `province=32` filter menampilkan data hanya dari Jawa Barat
- Seluruh wilayah: ketika filter tidak diset atau `date` diubah
- Satu periode: `date=2026-09-01` filter menampilkan snapshot sesuai tanggal
- Zero/null values: `COALESCE(..., 0)` dan `percentOf()` aman menangani

### Excel comparison (pending)
- Aggregasi hasil di dashboard vs sample Excel di `data/source/Input Data Jawa Barat.xlsx` masih menunggu runtime PHP+SQLite validation

---

## 9. Selanjutnya (production readiness)

1. **Runtime validation** di mesin dengan PHP dan MySQL/MariaDB terinstal
2. **Data import** dari Excel ke database via `services/ExcelWorkbookImporter.php`
3. **Comparison test** hasil agregasi vs Excel snapshot
4. **Schema migration** dari SQLite ke MySQL/MariaDB dengan koneksi environment
5. **Security review** untuk production deployment (token, credential, HTTPS, CSP headers)
