<?php

declare(strict_types=1);

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/services/ExcelWorkbookImporter.php';

require_role(['admin']);

$importer = new ExcelWorkbookImporter(db());
$statusMessage = '';
$statusType = '';
$result = null;
$sourcePath = __DIR__ . '/data/source/Input Data Jawa Barat.xlsx';
$reportDate = is_string($_POST['report_date'] ?? null) ? $_POST['report_date'] : '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $mode = $_POST['mode'] ?? 'preview';
    $targetPath = $sourcePath;

    if (!verify_csrf() || !in_array($mode, ['preview','import'], true) || !valid_report_date($reportDate)) {
        $statusType = 'error';
        $statusMessage = 'Sesi, mode impor, atau tanggal laporan tidak valid.';
    }
    if ($statusType !== 'error' && !empty($_FILES['excel_file']['name'])) {
        $upload = $_FILES['excel_file'];
        if ($upload['error'] !== UPLOAD_ERR_OK) {
            $statusType = 'error';
            $statusMessage = 'Upload file gagal. Silakan pilih file Excel yang valid.';
        } else {
            $ext = strtolower(pathinfo($upload['name'], PATHINFO_EXTENSION));
            if ($ext !== 'xlsx') {
                $statusType = 'error';
                $statusMessage = 'Format file tidak valid. Hanya file .xlsx yang diizinkan.';
            } elseif ($upload['size'] > 10485760) {
                $statusType = 'error';
                $statusMessage = 'Ukuran file melebihi batas 10 MB.';
            } else {
                $targetPath = __DIR__ . '/data/source/' . bin2hex(random_bytes(16)) . '.xlsx';
                if (!move_uploaded_file($upload['tmp_name'], $targetPath)) {
                    $statusType = 'error';
                    $statusMessage = 'File tidak dapat disimpan ke folder import.';
                }
            }
        }
    }

    if ($statusType !== 'error') {
        $result = $importer->importFile($targetPath, (int) ($_SESSION['user']['id'] ?? 0), $mode === 'preview', $reportDate);
        if ($result['status'] === 'preview_ok' || $result['status'] === 'imported') {
            $statusType = 'success';
            $statusMessage = $mode === 'preview' ? 'Preview impor berhasil dibuat.' : 'Impor data Excel selesai.';
        } else {
            $statusType = 'error';
            $statusMessage = 'Proses impor dibatalkan. Lihat detail di bawah ini.';
        }
    }
}

if (!isset($result)) {
    $result = $importer->previewFile($sourcePath, valid_report_date($reportDate) ? $reportDate : null);
}

define('DEFAULT_IMPORT_PATH', $sourcePath);
?>
<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Import Excel | Dashboard Pertanahan</title>
    <link rel="stylesheet" href="assets/style.css">
</head>
<body>
<header class="topbar">
    <div><p class="eyebrow">ATR/BPN · ADMIN</p><strong><?= h(APP_NAME) ?></strong></div>
    <nav aria-label="Navigasi utama">
        <a href="dashboard.php">Dashboard</a>
        <a href="input-data.php">Input Pemda</a>
        <a class="active" href="import-excel.php">Import Excel</a>
        <a href="logout.php">Keluar</a>
    </nav>
</header>
<main class="page-shell narrow">
    <section class="page-heading">
        <div>
            <p class="eyebrow">Impor data master</p>
            <h1>Import workbook Excel</h1>
            <p>Fitur ini memvalidasi header bertingkat, data wilayah, checksum file, sekaligus mencegah duplikasi saat impor berulang.</p>
        </div>
    </section>

    <?php if ($statusMessage !== ''): ?>
        <p class="alert <?= $statusType === 'success' ? 'success' : 'error' ?>" role="alert"><?= h($statusMessage) ?></p>
    <?php endif; ?>

    <section class="content-panel">
        <form method="post" enctype="multipart/form-data" class="form-grid">
            <input type="hidden" name="csrf_token" value="<?= h(csrf_token()) ?>">
            <label>Periode laporan<input type="date" name="report_date" value="<?= h($reportDate) ?>" required></label>
            <label class="full">File Excel (.xlsx)
                <input type="file" name="excel_file" accept=".xlsx,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet">
            </label>
            <div class="full form-actions">
                <button type="submit" name="mode" value="preview">Preview</button>
                <button type="submit" name="mode" value="import">Import data</button>
            </div>
        </form>
    </section>

    <?php if ($result): ?>
        <section class="content-panel" style="margin-top:1.5rem;">
            <h2>Ringkasan impor</h2>
            <p><strong>File:</strong> <?= h($result['file'] ?? '') ?></p>
            <p><strong>Checksum SHA256:</strong> <?= h($result['checksum'] ?? '') ?></p>
            <p><strong>Sheet:</strong> <?= h($result['sheet_name'] ?? '') ?></p>
            <p><strong>Baris valid:</strong> <?= (int) ($result['valid_rows'] ?? 0) ?> | <strong>Baris gagal:</strong> <?= (int) ($result['invalid_rows'] ?? 0) ?></p>
            <p><strong>Data mulai di baris:</strong> <?= (int) ($result['data_start_row'] ?? 0) ?></p>

            <?php if (!empty($result['errors'])): ?>
                <h3>Catatan error</h3>
                <ul>
                    <?php foreach ($result['errors'] as $error): ?>
                        <li><?= h((($error['row_number'] ?? 0) > 0 ? 'Baris ' . $error['row_number'] . ': ' : '') . $error['message']) ?></li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>

            <?php if (!empty($result['preview'])): ?>
                <h3>Preview</h3>
                <table>
                    <thead>
                        <tr>
                            <th>Baris</th>
                            <th>Wilayah</th>
                            <th>Periode</th>
                            <th>Nilai utama</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach (array_slice($result['preview'], 0, 5) as $item): ?>
                            <tr>
                                <td><?= (int) ($item['row_number'] ?? 0) ?></td>
                                <td><?= h($item['region'] ?? '') ?></td>
                                <td><?= h($item['period'] ?? '') ?></td>
                                <td><?= h(json_encode($item['values'] ?? [], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </section>
    <?php endif; ?>
</main>
</body>
</html>
