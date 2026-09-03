<?php
require_once __DIR__ . '/db.php';
require_role(array('admin', 'pemda'));

$pdo = db();
$cities = $pdo->query('SELECT r.code, r.name, p.name AS province_name FROM regions r JOIN regions p ON p.code = r.province_code WHERE r.type = "kabupaten_kota" ORDER BY p.name, r.name')->fetchAll();
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf()) {
        $error = 'Sesi formulir tidak valid. Muat ulang halaman dan coba lagi.';
    } else {
        $regionCode = $_POST['region_code'] ?? '';
        $reportDate = $_POST['report_date'] ?? '';
        $total = filter_var($_POST['total_records'] ?? null, FILTER_VALIDATE_INT, array('options' => array('min_range' => 0)));
        $stage1 = filter_var($_POST['validated_stage_1'] ?? null, FILTER_VALIDATE_INT, array('options' => array('min_range' => 0)));
        $stage2 = filter_var($_POST['validated_stage_2'] ?? null, FILTER_VALIDATE_INT, array('options' => array('min_range' => 0)));
        $area = filter_var($_POST['area_hectare'] ?? null, FILTER_VALIDATE_FLOAT);
        $validRegion = $pdo->prepare('SELECT COUNT(*) FROM regions WHERE code = ? AND type = "kabupaten_kota"');
        $validRegion->execute(array($regionCode));

        if (!$validRegion->fetchColumn() || !preg_match('/^\\d{4}-\\d{2}-\\d{2}$/', $reportDate)) {
            $error = 'Wilayah atau periode laporan tidak valid.';
        } elseif ($total === false || $stage1 === false || $stage2 === false || $area === false || $area < 0) {
            $error = 'Semua nilai harus berupa angka nol atau lebih.';
        } elseif ($stage1 > $total || $stage2 > $stage1) {
            $error = 'Nilai tahap 2 tidak boleh melebihi tahap 1, dan tahap 1 tidak boleh melebihi total objek.';
        } else {
            $upsert = $pdo->prepare('INSERT INTO metric_snapshots (region_code, report_date, total_records, validated_stage_1, validated_stage_2, area_hectare, source, submitted_by) VALUES (?, ?, ?, ?, ?, ?, "pemda", ?) ON CONFLICT(region_code, report_date, source) DO UPDATE SET total_records = excluded.total_records, validated_stage_1 = excluded.validated_stage_1, validated_stage_2 = excluded.validated_stage_2, area_hectare = excluded.area_hectare, submitted_by = excluded.submitted_by, created_at = CURRENT_TIMESTAMP');
            $upsert->execute(array($regionCode, $reportDate, $total, $stage1, $stage2, $area, $_SESSION['user']['id']));
            $success = 'Data Pemda tersimpan dan akan langsung dihitung pada dashboard provinsi.';
        }
    }
}
?>
<!doctype html>
<html lang="id">
<head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>Input Pemda | <?= h(APP_NAME) ?></title><link rel="stylesheet" href="assets/style.css"></head>
<body>
<header class="topbar"><div><p class="eyebrow">ATR/BPN · MVP</p><strong><?= h(APP_NAME) ?></strong></div><nav aria-label="Navigasi utama"><a href="index.php">Dashboard</a><a class="active" href="input-data.php">Input Pemda</a><a href="logout.php">Keluar</a></nav></header>
<main class="page-shell narrow">
    <section class="page-heading"><div><p class="eyebrow">Kanal pelaporan</p><h1>Input data Pemda</h1><p>Validasi dilakukan sebelum data masuk ke agregasi provinsi.</p></div></section>
    <?php if ($error): ?><p class="alert error" role="alert"><?= h($error) ?></p><?php endif; ?>
    <?php if ($success): ?><p class="alert success" role="status"><?= h($success) ?></p><?php endif; ?>
    <form method="post" class="content-panel form-grid">
        <input type="hidden" name="csrf_token" value="<?= h(csrf_token()) ?>">
        <label class="full">Kabupaten/kota<select name="region_code" required><option value="">Pilih wilayah</option><?php foreach ($cities as $city): ?><option value="<?= h($city['code']) ?>"><?= h($city['province_name'] . ' — ' . $city['name']) ?></option><?php endforeach; ?></select></label>
        <label>Periode laporan<input type="date" name="report_date" value="2026-09-01" required></label>
        <label>Total objek<input type="number" min="0" name="total_records" required></label>
        <label>Validasi tahap 1<input type="number" min="0" name="validated_stage_1" required></label>
        <label>Validasi tahap 2<input type="number" min="0" name="validated_stage_2" required></label>
        <label>Luas terdata (ha)<input type="number" min="0" step="0.1" name="area_hectare" required></label>
        <div class="full form-actions"><button type="submit">Simpan dan agregasikan</button><a href="dashboard.php">Kembali ke dashboard</a></div>
    </form>
</main>
</body>
</html>
