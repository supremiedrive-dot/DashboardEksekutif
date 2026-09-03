<?php
require_once __DIR__ . '/db.php';
require_login();
header('Location: dashboard.php');
exit;

$pdo = db();
$provinces = $pdo->query('SELECT code, name FROM regions WHERE type = "provinsi" ORDER BY name')->fetchAll();
$provinceCode = $_GET['province'] ?? $provinces[0]['code'];
$provinceExists = false;
foreach ($provinces as $province) {
    if ($province['code'] === $provinceCode) {
        $provinceExists = true;
        break;
    }
}
if (!$provinceExists) {
    $provinceCode = $provinces[0]['code'];
}

$dateStmt = $pdo->prepare('SELECT MAX(m.report_date) FROM metric_snapshots m JOIN regions r ON r.code = m.region_code WHERE r.province_code = ?');
$dateStmt->execute(array($provinceCode));
$reportDate = $_GET['date'] ?? $dateStmt->fetchColumn();

$summaryStmt = $pdo->prepare('SELECT
    COALESCE(SUM(m.total_records), 0) AS total_records,
    COALESCE(SUM(m.validated_stage_1), 0) AS stage_1,
    COALESCE(SUM(m.validated_stage_2), 0) AS stage_2,
    COALESCE(SUM(m.area_hectare), 0) AS area_hectare,
    COUNT(m.id) AS reporting_regions
    FROM metric_snapshots m JOIN regions r ON r.code = m.region_code
    WHERE r.province_code = ? AND m.report_date = ?');
$summaryStmt->execute(array($provinceCode, $reportDate));
$summary = $summaryStmt->fetch();

$rowsStmt = $pdo->prepare('SELECT r.code, r.name, m.total_records, m.validated_stage_1, m.validated_stage_2, m.area_hectare, m.source
    FROM metric_snapshots m JOIN regions r ON r.code = m.region_code
    WHERE r.province_code = ? AND m.report_date = ? ORDER BY r.name');
$rowsStmt->execute(array($provinceCode, $reportDate));
$rows = $rowsStmt->fetchAll();

$provinceName = '';
foreach ($provinces as $province) {
    if ($province['code'] === $provinceCode) {
        $provinceName = $province['name'];
    }
}
function percent($part, $total): int
{
    return $total > 0 ? (int) round(($part / $total) * 100) : 0;
}
?>
<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= h(APP_NAME) ?></title>
    <link rel="stylesheet" href="assets/style.css">
</head>
<body>
<header class="topbar">
    <div><p class="eyebrow">ATR/BPN · MVP</p><strong><?= h(APP_NAME) ?></strong></div>
    <nav aria-label="Navigasi utama">
        <a class="active" href="index.php">Dashboard</a>
        <?php if (in_array($_SESSION['user']['role'], array('admin', 'pemda'), true)): ?><a href="input-data.php">Input Pemda</a><?php endif; ?>
        <a href="docs.php">Dokumentasi</a>
        <a href="logout.php">Keluar</a>
    </nav>
</header>
<main class="page-shell">
    <section class="page-heading">
        <div>
            <p class="eyebrow">Ringkasan eksekutif</p>
            <h1>Progres pertanahan <?= h($provinceName) ?></h1>
            <p>Data agregat dari kabupaten dan kota per <?= h($reportDate) ?>.</p>
        </div>
        <div class="user-chip"><span><?= h($_SESSION['user']['name']) ?></span><small><?= h(ucfirst($_SESSION['user']['role'])) ?></small></div>
    </section>

    <form class="filter-bar" method="get">
        <label>Provinsi
            <select name="province">
                <?php foreach ($provinces as $province): ?>
                    <option value="<?= h($province['code']) ?>"<?= $province['code'] === $provinceCode ? ' selected' : '' ?>><?= h($province['name']) ?></option>
                <?php endforeach; ?>
            </select>
        </label>
        <label>Periode<input type="date" name="date" value="<?= h($reportDate) ?>"></label>
        <button type="submit">Terapkan</button>
    </form>

    <section class="metric-grid" aria-label="Indikator utama">
        <article class="metric-card"><p>Total objek</p><strong><?= number_format((int) $summary['total_records'], 0, ',', '.') ?></strong><small><?= (int) $summary['reporting_regions'] ?> wilayah pelapor</small></article>
        <article class="metric-card"><p>Validasi tahap 1</p><strong><?= number_format((int) $summary['stage_1'], 0, ',', '.') ?></strong><small><?= percent((int) $summary['stage_1'], (int) $summary['total_records']) ?>% dari total</small></article>
        <article class="metric-card"><p>Validasi tahap 2</p><strong><?= number_format((int) $summary['stage_2'], 0, ',', '.') ?></strong><small><?= percent((int) $summary['stage_2'], (int) $summary['total_records']) ?>% dari total</small></article>
        <article class="metric-card"><p>Luas terdata</p><strong><?= number_format((float) $summary['area_hectare'], 1, ',', '.') ?> ha</strong><small>Akumulasi wilayah pelapor</small></article>
    </section>

    <section class="content-panel">
        <div class="section-heading"><div><h2>Rincian kabupaten dan kota</h2><p>Sumber menunjukkan kanal data terakhir yang digunakan.</p></div><span class="status-dot">Data tervalidasi</span></div>
        <div class="table-wrap"><table>
            <thead><tr><th>Wilayah</th><th class="number">Total objek</th><th class="number">Tahap 1</th><th class="number">Tahap 2</th><th class="number">Luas (ha)</th><th>Sumber</th></tr></thead>
            <tbody>
            <?php foreach ($rows as $row): ?>
                <tr><td><?= h($row['name']) ?></td><td class="number"><?= number_format((int) $row['total_records'], 0, ',', '.') ?></td><td class="number"><?= number_format((int) $row['validated_stage_1'], 0, ',', '.') ?></td><td class="number"><?= number_format((int) $row['validated_stage_2'], 0, ',', '.') ?></td><td class="number"><?= number_format((float) $row['area_hectare'], 1, ',', '.') ?></td><td><span class="tag <?= $row['source'] === 'kkp' ? 'tag-blue' : 'tag-green' ?>"><?= h(strtoupper($row['source'])) ?></span></td></tr>
            <?php endforeach; ?>
            </tbody>
        </table></div>
    </section>
</main>
</body>
</html>
