<?php
require_once __DIR__ . '/db.php';
require_login();
$pdo = db();
$provinces = $pdo->query('SELECT code, name FROM regions WHERE type = "provinsi" ORDER BY name')->fetchAll();
$provinceCode = $_GET['province'] ?? $provinces[0]['code'];
$provinceName = $provinces[0]['name'];
$matchedProvince = false;
foreach ($provinces as $province) { if ($province['code'] === $provinceCode) { $provinceName = $province['name']; $matchedProvince = true; } }
if (!$matchedProvince) { $provinceCode = $provinces[0]['code']; }
$dateStatement = $pdo->prepare('SELECT MAX(m.report_date) FROM metric_snapshots m JOIN regions r ON r.code = m.region_code WHERE r.province_code = ?');
$dateStatement->execute(array($provinceCode));
$reportDate = $_GET['date'] ?? $dateStatement->fetchColumn();
$effectiveMetrics = ' FROM metric_snapshots m JOIN regions r ON r.code = m.region_code WHERE r.province_code = ? AND m.report_date = ? AND NOT (m.source = "kkp" AND EXISTS (SELECT 1 FROM metric_snapshots pemda WHERE pemda.region_code = m.region_code AND pemda.report_date = m.report_date AND pemda.source = "pemda")) ';
$summaryStatement = $pdo->prepare('SELECT COALESCE(SUM(m.total_records), 0) AS total_records, COALESCE(SUM(m.validated_stage_1), 0) AS stage_1, COALESCE(SUM(m.validated_stage_2), 0) AS stage_2, COALESCE(SUM(m.area_hectare), 0) AS area_hectare, COUNT(m.id) AS reporting_regions' . $effectiveMetrics);
$summaryStatement->execute(array($provinceCode, $reportDate));
$summary = $summaryStatement->fetch();
$rowsStatement = $pdo->prepare('SELECT r.code, r.name, m.total_records, m.validated_stage_1, m.validated_stage_2, m.area_hectare, m.source' . $effectiveMetrics . ' ORDER BY r.name');
$rowsStatement->execute(array($provinceCode, $reportDate));
$rows = $rowsStatement->fetchAll();
function dashboard_percent($part, $total): int { return $total > 0 ? (int) round(($part / $total) * 100) : 0; }
?>
<!doctype html>
<html lang="id">
<head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title><?= h(APP_NAME) ?></title><link href="https://fonts.googleapis.com/icon?family=Material+Icons+Sharp" rel="stylesheet"><link rel="stylesheet" href="assets/dashboard.css"></head>
<body>
<div class="container">
    <aside id="sidebar" aria-label="Navigasi samping">
        <div class="toggle"><div class="logo"><img src="assets/logo.png" alt="Logo ATR/BPN"><h2>DASHBOARD<span class="danger"> PERTANAHAN</span></h2></div><button class="icon-button close" id="close-btn" aria-label="Tutup menu"><span class="material-icons-sharp">close</span></button></div>
        <nav class="sidebar"><a href="dashboard.php" class="active"><span class="material-icons-sharp">dashboard</span><span>Dashboard</span></a><?php if (in_array($_SESSION['user']['role'], array('admin', 'pemda'), true)): ?><a href="input-data.php"><span class="material-icons-sharp">task_alt</span><span>Input Pemda</span></a><a href="sync-kkp.php"><span class="material-icons-sharp">sync</span><span>Sinkronisasi KKP</span></a><?php endif; ?><a href="docs.php"><span class="material-icons-sharp">receipt_long</span><span>Penjelasan Teknis</span></a><a href="api/kkp.php?province=<?= h($provinceCode) ?>&amp;date=<?= h($reportDate) ?>"><span class="material-icons-sharp">api</span><span>API KKP</span></a><a class="logout" href="logout.php"><span class="material-icons-sharp">logout</span><span>Keluar</span></a></nav>
    </aside>
    <main>
        <section class="mobile-nav"><button class="icon-button" id="menu-btn" aria-label="Buka menu"><span class="material-icons-sharp">menu</span></button><strong>Dashboard Pertanahan</strong></section>
        <div class="heading-row"><div><p class="eyebrow">RINGKASAN EKSEKUTIF</p><h1>Dashboard <?= h($provinceName) ?></h1><p class="muted">Agregasi kabupaten/kota · Periode <?= h($reportDate) ?></p></div><form class="filter" method="get"><label>Provinsi<select name="province"><?php foreach ($provinces as $province): ?><option value="<?= h($province['code']) ?>"<?= $province['code'] === $provinceCode ? ' selected' : '' ?>><?= h($province['name']) ?></option><?php endforeach; ?></select></label><label>Periode<input name="date" type="date" value="<?= h($reportDate) ?>"></label><button type="submit">Terapkan</button></form></div>
        <section class="dashboard" aria-label="Indikator utama">
            <article class="total"><div class="status"><div class="info"><h3>Total Objek</h3><h2><?= number_format((int) $summary['total_records'], 0, ',', '.') ?></h2></div><div class="progress"><svg viewBox="0 0 100 100" aria-hidden="true"><circle class="track" cx="50" cy="50" r="42"></circle><circle class="value" cx="50" cy="50" r="42" style="--progress:100"></circle></svg><div class="percentage"><p>100%</p></div></div></div><small><?= (int) $summary['reporting_regions'] ?> wilayah melapor</small></article>
            <article class="validasi-1"><div class="status"><div class="info"><h3>Validasi Tahap 1</h3><h2><?= number_format((int) $summary['stage_1'], 0, ',', '.') ?></h2></div><div class="progress"><svg viewBox="0 0 100 100" aria-hidden="true"><circle class="track" cx="50" cy="50" r="42"></circle><circle class="value" cx="50" cy="50" r="42" style="--progress:<?= dashboard_percent($summary['stage_1'], $summary['total_records']) ?>"></circle></svg><div class="percentage"><p><?= dashboard_percent($summary['stage_1'], $summary['total_records']) ?>%</p></div></div></div><small>Dihitung dari total provinsi</small></article>
            <article class="validasi-2"><div class="status"><div class="info"><h3>Validasi Tahap 2</h3><h2><?= number_format((int) $summary['stage_2'], 0, ',', '.') ?></h2></div><div class="progress"><svg viewBox="0 0 100 100" aria-hidden="true"><circle class="track" cx="50" cy="50" r="42"></circle><circle class="value" cx="50" cy="50" r="42" style="--progress:<?= dashboard_percent($summary['stage_2'], $summary['total_records']) ?>"></circle></svg><div class="percentage"><p><?= dashboard_percent($summary['stage_2'], $summary['total_records']) ?>%</p></div></div></div><small>Tidak menjumlahkan persentase</small></article>
            <article class="luas"><div class="status"><div class="info"><h3>Luas Terdata</h3><h2><?= number_format((float) $summary['area_hectare'], 1, ',', '.') ?></h2></div><div class="icon-stat"><span class="material-icons-sharp">landscape</span></div></div><small>Hektare</small></article>
        </section>
        <section class="work-table"><div class="section-title"><div><h2>Data kabupaten dan kota</h2><p class="muted">Snapshot Pemda menggantikan snapshot KKP pada wilayah dan periode yang sama.</p></div><span class="status-label"><i></i>Data terverifikasi</span></div><div class="table-wrap"><table><thead><tr><th>Wilayah</th><th>Total</th><th>Tahap 1</th><th>Tahap 2</th><th>Luas (ha)</th><th>Sumber</th></tr></thead><tbody><?php foreach ($rows as $row): ?><tr><td><?= h($row['name']) ?></td><td><?= number_format((int) $row['total_records'], 0, ',', '.') ?></td><td><?= number_format((int) $row['validated_stage_1'], 0, ',', '.') ?></td><td><?= number_format((int) $row['validated_stage_2'], 0, ',', '.') ?></td><td><?= number_format((float) $row['area_hectare'], 1, ',', '.') ?></td><td><span class="badge <?= $row['source'] === 'kkp' ? 'api' : 'pemda' ?>"><?= h(strtoupper($row['source'])) ?></span></td></tr><?php endforeach; ?></tbody></table></div></section>
    </main>
    <section class="right-section" aria-label="Profil dan status"><div class="nav"><button class="theme-toggle" id="theme-btn" aria-label="Ubah tema"><span class="material-icons-sharp active">light_mode</span><span class="material-icons-sharp">dark_mode</span></button><div class="profile"><div class="info"><p>Halo, <b><?= h($_SESSION['user']['name']) ?></b></p><small><?= h(ucfirst($_SESSION['user']['role'])) ?></small></div><div class="profile-photo"><img src="assets/logo.png" alt="Profil pengguna"></div></div></div><div class="user-profile"><img src="assets/logo.png" alt="Logo ATR/BPN"><h2><?= h($_SESSION['user']['name']) ?></h2><p>Kementerian ATR/BPN</p></div><div class="reminders"><div class="section-title"><h2>Status integrasi</h2><span class="material-icons-sharp">notifications_none</span></div><div class="notification"><div class="icon"><span class="material-icons-sharp">storage</span></div><div><h3>Database dashboard</h3><small>SQLite · data contoh aktif</small></div></div><div class="notification"><div class="icon api-icon"><span class="material-icons-sharp">api</span></div><div><h3>KKP API</h3><small><?= KKP_MODE === 'live' ? 'Mode live' : 'Mode mock — siap dikonfigurasi' ?></small></div></div></div></section>
</div>
<script src="assets/app.js"></script>
</body>
</html>
