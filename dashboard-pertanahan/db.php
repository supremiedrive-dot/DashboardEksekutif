<?php
declare(strict_types=1);

require_once __DIR__ . '/config.php';

function db(): PDO
{
    static $pdo = null;
    if ($pdo instanceof PDO) {
        return $pdo;
    }

    $pdo = new PDO('sqlite:' . DB_PATH);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    initialize_database($pdo);
    return $pdo;
}

function initialize_database(PDO $pdo): void
{
    $pdo->exec('CREATE TABLE IF NOT EXISTS users (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        name TEXT NOT NULL,
        email TEXT NOT NULL UNIQUE,
        password_hash TEXT NOT NULL,
        role TEXT NOT NULL CHECK(role IN ("eksekutif", "admin", "pemda"))
    )');
    $pdo->exec('CREATE TABLE IF NOT EXISTS regions (
        code TEXT PRIMARY KEY,
        name TEXT NOT NULL,
        type TEXT NOT NULL CHECK(type IN ("provinsi", "kabupaten_kota")),
        province_code TEXT NULL REFERENCES regions(code)
    )');
    $pdo->exec('CREATE TABLE IF NOT EXISTS metric_snapshots (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        region_code TEXT NOT NULL REFERENCES regions(code),
        report_date TEXT NOT NULL,
        total_records INTEGER NOT NULL CHECK(total_records >= 0),
        validated_stage_1 INTEGER NOT NULL CHECK(validated_stage_1 >= 0),
        validated_stage_2 INTEGER NOT NULL CHECK(validated_stage_2 >= 0),
        area_hectare REAL NOT NULL CHECK(area_hectare >= 0),
        source TEXT NOT NULL CHECK(source IN ("pemda", "kkp")),
        submitted_by INTEGER NULL REFERENCES users(id),
        created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
        UNIQUE(region_code, report_date, source)
    )');

    if ((int) $pdo->query('SELECT COUNT(*) FROM users')->fetchColumn() === 0) {
        $insertUser = $pdo->prepare('INSERT INTO users (name, email, password_hash, role) VALUES (?, ?, ?, ?)');
        $password = password_hash('Demo123!', PASSWORD_DEFAULT);
        $insertUser->execute(array('Joshua Paskah', 'joshua@demo.local', $password, 'eksekutif'));
        $insertUser->execute(array('Admin Dashboard', 'admin@demo.local', $password, 'admin'));
        $insertUser->execute(array('Operator Pemda', 'pemda@demo.local', $password, 'pemda'));

        $regions = array(
            array('32', 'Jawa Barat', 'provinsi', null),
            array('35', 'Jawa Timur', 'provinsi', null),
            array('31', 'DKI Jakarta', 'provinsi', null),
            array('3273', 'Kota Bandung', 'kabupaten_kota', '32'),
            array('3275', 'Kota Bekasi', 'kabupaten_kota', '32'),
            array('3216', 'Kabupaten Bekasi', 'kabupaten_kota', '32'),
            array('3271', 'Kota Bogor', 'kabupaten_kota', '32'),
            array('3578', 'Kota Surabaya', 'kabupaten_kota', '35'),
            array('3573', 'Kota Malang', 'kabupaten_kota', '35'),
            array('3515', 'Kabupaten Sidoarjo', 'kabupaten_kota', '35'),
            array('3171', 'Kota Jakarta Selatan', 'kabupaten_kota', '31'),
            array('3173', 'Kota Jakarta Barat', 'kabupaten_kota', '31'),
            array('3174', 'Kota Jakarta Utara', 'kabupaten_kota', '31')
        );
        $insertRegion = $pdo->prepare('INSERT INTO regions (code, name, type, province_code) VALUES (?, ?, ?, ?)');
        foreach ($regions as $region) {
            $insertRegion->execute($region);
        }

        $records = array(
            array('3273', '2026-09-01', 18000, 12000, 9000, 8100.5, 'kkp'),
            array('3275', '2026-09-01', 15000, 9800, 7300, 6400.2, 'kkp'),
            array('3216', '2026-09-01', 16000, 11000, 8500, 9000.0, 'pemda'),
            array('3271', '2026-09-01', 11578, 7000, 4900, 4600.4, 'pemda'),
            array('3578', '2026-09-01', 17000, 13000, 11000, 7000.0, 'kkp'),
            array('3573', '2026-09-01', 9000, 6200, 4800, 4100.0, 'pemda'),
            array('3515', '2026-09-01', 14000, 9500, 7100, 8200.0, 'kkp'),
            array('3171', '2026-09-01', 12500, 10000, 8000, 4500.0, 'kkp'),
            array('3173', '2026-09-01', 10000, 7000, 5400, 3900.0, 'pemda'),
            array('3174', '2026-09-01', 9500, 6200, 4500, 3600.0, 'kkp')
        );
        $insertMetric = $pdo->prepare('INSERT INTO metric_snapshots (region_code, report_date, total_records, validated_stage_1, validated_stage_2, area_hectare, source) VALUES (?, ?, ?, ?, ?, ?, ?)');
        foreach ($records as $record) {
            $insertMetric->execute($record);
        }
    }
}

