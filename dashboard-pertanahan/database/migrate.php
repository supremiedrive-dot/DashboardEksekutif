<?php
declare(strict_types=1);
if (PHP_SAPI !== 'cli') { http_response_code(403); exit; }
require_once __DIR__ . '/Connection.php';
$pdo = DatabaseConnection::create();
// Repeatable baseline only; future ALTERs belong in versioned migrations.
$pdo->exec(file_get_contents(__DIR__ . '/schema.sql'));
$pdo->beginTransaction();
try {
    $pdo->exec(file_get_contents(__DIR__ . '/seeders/reference_seed.sql'));
    $pdo->exec(file_get_contents(__DIR__ . '/seeders/regions_jawa_barat.sql'));
    $pdo->commit();
} catch (Throwable $exception) {
    $pdo->rollBack();
    throw $exception;
}
echo "Schema dan referensi MySQL siap. Tidak ada akun atau metrik demo dibuat.\n";
