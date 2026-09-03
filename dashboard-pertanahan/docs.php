<?php
require_once __DIR__ . '/config.php';
require_login();
?>
<!doctype html>
<html lang="id">
<head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>Dokumentasi | <?= h(APP_NAME) ?></title><link rel="stylesheet" href="assets/style.css"></head>
<body>
<header class="topbar"><div><p class="eyebrow">ATR/BPN · MVP</p><strong><?= h(APP_NAME) ?></strong></div><nav aria-label="Navigasi utama"><a href="dashboard.php">Dashboard</a><?php if (in_array($_SESSION['user']['role'], array('admin', 'pemda'), true)): ?><a href="input-data.php">Input Pemda</a><?php endif; ?><a class="active" href="docs.php">Dokumentasi</a><a href="logout.php">Keluar</a></nav></header>
<main class="page-shell narrow">
    <section class="page-heading"><div><p class="eyebrow">Penjelasan teknis</p><h1>Alur data dashboard</h1><p>Materi ringkas untuk demo bersama Pusdatin dan pimpinan.</p></div></section>
    <section class="content-panel">
        <h2>1. Sumber data</h2><p>Data berasal dari Pemda melalui input terstruktur dan dari KKP melalui adaptor API baca. Setiap snapshot menyimpan wilayah, periode, indikator, sumber, serta waktu pembaruan.</p>
        <h2>2. Validasi</h2><p>Aplikasi memeriksa format tanggal, kode kabupaten/kota, nilai negatif, dan urutan proses validasi. Data tahap 2 tidak boleh melampaui tahap 1; tahap 1 tidak boleh melampaui total objek.</p>
        <h2>3. Agregasi provinsi</h2><p>Dashboard menjumlahkan data absolut seluruh kabupaten/kota pada periode yang dipilih. Persentase validasi dihitung ulang dari total provinsi agar tidak terjadi penjumlahan persentase yang keliru.</p>
        <h2>4. Hak akses</h2><p>Eksekutif melihat dashboard. Admin dan Pemda dapat memasukkan data. Untuk produksi, akun demo harus diganti dengan pengelolaan identitas institusional dan kredensial API disimpan pada konfigurasi lingkungan.</p>
    </section>
</main>
</body>
</html>
