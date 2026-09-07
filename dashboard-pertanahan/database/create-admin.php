<?php
declare(strict_types=1);
if (PHP_SAPI !== 'cli') { http_response_code(403); exit; }
require_once __DIR__ . '/Connection.php';
$pdo=DatabaseConnection::create();
$name=getenv('ADMIN_NAME'); $email=getenv('ADMIN_EMAIL'); $password=getenv('ADMIN_PASSWORD');
if (!$name || strlen($name)>150 || !filter_var($email,FILTER_VALIDATE_EMAIL) || strlen($email)>180 || !$password || strlen($password)<12) {
    throw new RuntimeException('Isi ADMIN_NAME, ADMIN_EMAIL, dan ADMIN_PASSWORD (minimal 12 karakter) melalui environment sementara.');
}
$query=$pdo->prepare('INSERT INTO users (role_id,name,email,password_hash) SELECT id,?,?,? FROM roles WHERE code=?');
try { $query->execute([$name,$email,password_hash($password,PASSWORD_DEFAULT),'admin']); }
catch (PDOException $exception) { throw new RuntimeException('Administrator tidak dapat dibuat; periksa email unik dan schema database.'); }
if ($query->rowCount()!==1) { throw new RuntimeException('Jalankan migration referensi terlebih dahulu.'); }
echo "Administrator dibuat; tidak ada kredensial ditampilkan.\n";
