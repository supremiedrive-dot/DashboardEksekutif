<?php
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/services/KkpClient.php';
require_role(array('admin'));

$pdo = db();
$provinces = $pdo->query('SELECT code, name FROM regions WHERE type = "provinsi" ORDER BY name')->fetchAll();
$message = '';
$error = '';
$provinceCode = $_POST['province'] ?? $provinces[0]['code'];
$reportDate = $_POST['date'] ?? date('Y-m-d');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf()) {
        $error = 'Sesi formulir tidak valid. Muat ulang halaman dan coba lagi.';
    } elseif (!preg_match('/^\\d{2}$/', $provinceCode) || !preg_match('/^\\d{4}-\\d{2}-\\d{2}$/', $reportDate)) {
        $error = 'Provinsi atau periode tidak valid.';
    } else {
        try {
            $count = (new KkpClient($pdo))->syncProvince($provinceCode, $reportDate);
            $message = $count . ' snapshot KKP berhasil disinkronkan.';
        } catch (Throwable $exception) {
            $error = $exception->getMessage();
        }
    }
}
?>
<!doctype html>
<html lang="id"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>Sinkronisasi KKP | <?= h(APP_NAME) ?></title><link rel="stylesheet" href="assets/style.css"></head>
<body><header class="topbar"><div><p class="eyebrow">ATR/BPN · ADMIN</p><strong><?= h(APP_NAME) ?></strong></div><nav aria-label="Navigasi utama"><a href="dashboard.php">Dashboard</a><a href="input-data.php">Input Pemda</a><a class="active" href="sync-kkp.php">Sinkronisasi KKP</a><a href="logout.php">Keluar</a></nav></header>
<main class="page-shell narrow"><section class="page-heading"><div><p class="eyebrow">INTEGRASI DATA</p><h1>Sinkronisasi KKP</h1><p>Mode saat ini: <strong><?= h(KKP_MODE) ?></strong>. Pada mode mock, data contoh digunakan; pada mode live, permintaan dikirim ke API KKP resmi.</p></div></section>
<?php if ($message): ?><p class="alert success" role="status"><?= h($message) ?></p><?php endif; ?><?php if ($error): ?><p class="alert error" role="alert"><?= h($error) ?></p><?php endif; ?>
<form method="post" class="content-panel form-grid"><input type="hidden" name="csrf_token" value="<?= h(csrf_token()) ?>"><label>Provinsi<select name="province"><?php foreach ($provinces as $province): ?><option value="<?= h($province['code']) ?>"<?= $province['code'] === $provinceCode ? ' selected' : '' ?>><?= h($province['name']) ?></option><?php endforeach; ?></select></label><label>Periode snapshot<input type="date" name="date" value="<?= h($reportDate) ?>" required></label><div class="full form-actions"><button type="submit">Sinkronkan data KKP</button><a href="docs.php">Lihat kontrak data</a></div></form>
</main></body></html>
