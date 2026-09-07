<?php
declare(strict_types=1);
if (PHP_SAPI !== 'cli') { http_response_code(403); exit; }
require_once __DIR__ . '/Connection.php';
load_environment(__DIR__ . '/../.env');
if (!in_array(getenv('APP_ENV'), ['development', 'testing'], true) || !in_array('--demo', $argv, true)) {
    throw new RuntimeException('Hanya untuk development/testing dengan opsi --demo.');
}
$pdo = DatabaseConnection::create();
foreach (['users', 'metric_snapshots'] as $table) {
    // Table names are a fixed internal allowlist, never request data.
    if ((int) $pdo->query('SELECT COUNT(*) FROM ' . $table)->fetchColumn() !== 0) {
        throw new RuntimeException('Target harus kosong; impor demo tidak menimpa data.');
    }
}
$path = __DIR__ . '/../data/dashboard.sqlite';
if (!is_file($path)) { throw new RuntimeException('Arsip SQLite tidak ditemukan.'); }
$before = hash_file('sha256', $path);
$source = new PDO('sqlite:' . $path, null, null, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]);
$source->exec('PRAGMA query_only = ON');
$pdo->beginTransaction();
try {
    $province = $pdo->prepare('INSERT INTO provinces (code,name) VALUES (?,?) ON DUPLICATE KEY UPDATE name=VALUES(name)');
    $region = $pdo->prepare('INSERT INTO regions (code,name,province_id,region_type) SELECT ?,?,id,\'kabupaten_kota\' FROM provinces WHERE code=? ON DUPLICATE KEY UPDATE code=VALUES(code)');
    foreach ($source->query('SELECT * FROM regions')->fetchAll() as $row) {
        if ($row['type'] === 'provinsi') { $province->execute([$row['code'], $row['name']]); }
    }
    foreach ($source->query('SELECT * FROM regions')->fetchAll() as $row) {
        if ($row['type'] !== 'provinsi') { $region->execute([$row['code'], $row['name'], $row['province_code']]); }
    }
    $user = $pdo->prepare('INSERT INTO users (id,role_id,name,email,password_hash) SELECT ?,id,?,?,? FROM roles WHERE code=?');
    foreach ($source->query('SELECT * FROM users')->fetchAll() as $row) {
        $user->execute([$row['id'], $row['name'], $row['email'], $row['password_hash'], $row['role']]);
    }
    $metric = $pdo->prepare('INSERT INTO metric_snapshots (region_code,report_date,total_records,validated_stage_1,validated_stage_2,area_hectare,source,submitted_by) VALUES (?,?,?,?,?,?,?,?)');
    foreach ($source->query('SELECT * FROM metric_snapshots')->fetchAll() as $row) {
        $metric->execute([$row['region_code'],$row['report_date'],$row['total_records'],$row['validated_stage_1'],$row['validated_stage_2'],$row['area_hectare'],$row['source'],$row['submitted_by']]);
    }
    $pdo->commit();
} catch (Throwable $exception) { $pdo->rollBack(); throw $exception; }
if (hash_file('sha256', $path) !== $before) { throw new RuntimeException('Hash arsip SQLite berubah.'); }
echo "Data demo disalin ke MySQL development. Arsip SQLite tetap utuh.\n";
