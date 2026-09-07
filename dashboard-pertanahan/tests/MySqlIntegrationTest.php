<?php
declare(strict_types=1);
require_once __DIR__ . '/../database/Connection.php';
require_once __DIR__ . '/../services/DashboardDataService.php';
require_once __DIR__ . '/../services/KkpClient.php';
$pdo=DatabaseConnection::create();
if (getenv('APP_ENV')!=='testing' || substr(getenv('DB_NAME'),-5)!=='_test') { throw new RuntimeException('Dedicated test database required.'); }
$checks=0;
function checkDb(bool $ok,string $message): void { global $checks; if(!$ok) { throw new RuntimeException($message); } $checks++; }
checkDb($pdo->getAttribute(PDO::ATTR_DRIVER_NAME)==='mysql' && !$pdo->getAttribute(PDO::ATTR_EMULATE_PREPARES),'Native MySQL prepared statements');
checkDb($pdo->query('SELECT @@character_set_connection')->fetchColumn()==='utf8mb4','Connection charset');
$pdo->exec("SET SESSION sql_mode = CONCAT(@@sql_mode, ',ANSI_QUOTES')");
$pdo->beginTransaction();
try {
    $insert=$pdo->prepare('INSERT INTO metric_snapshots (region_code,report_date,total_records,validated_stage_1,validated_stage_2,area_hectare,source) VALUES (?,?,?,?,?,?,?) ON DUPLICATE KEY UPDATE total_records=VALUES(total_records)');
    $insert->execute(['3273','2098-01-01',10,8,5,2.5,'kkp']);
    $insert->execute(['3273','2098-01-01',20,15,10,5,'pemda']);
    $service=new DashboardDataService($pdo);
    $result=$service->getDashboard('32','2098-01-01');
    checkDb((int)$result['summary']['total_records']===20 && count($result['rows'])===1,'Pemda priority and aggregation');
    $empty=$service->getDashboard('32','2098-01-02');
    checkDb($empty['report_date']==='2098-01-02' && !$empty['rows'] && (int)$empty['summary']['total_records']===0,'Empty period');
    checkDb($service->resolveReportDate('32',null)==='2098-01-01','Latest period');
    checkDb(count((new KkpClient($pdo))->fetchSnapshots('32','2098-01-01'))===1,'KKP mock query');
    $insert->execute(['3273','2098-01-01',25,15,10,5,'pemda']);
    checkDb((int)$service->getDashboard('32','2098-01-01')['summary']['total_records']===25,'MySQL upsert');
    try { $insert->execute(['missing','2098-01-01',1,0,0,1,'kkp']); checkDb(false,'Foreign key must reject unknown region'); }
    catch (PDOException $exception) { $checks++; }
} finally { $pdo->rollBack(); }
$originalPort=getenv('DB_PORT'); putenv('DB_PORT=1');
try { DatabaseConnection::create(); checkDb(false,'Connection must fail without fallback'); }
catch (RuntimeException $exception) { checkDb(strpos($exception->getMessage(),'Koneksi MySQL gagal')!==false && $exception->getPrevious()===null,'Safe MySQL failure'); }
finally { putenv('DB_PORT='.$originalPort); }
$driver=getenv('DB_DRIVER'); putenv('DB_DRIVER=sqlite');
try { DatabaseConnection::create(); checkDb(false,'SQLite driver must be rejected'); }
catch (RuntimeException $exception) { $checks++; }
finally { putenv('DB_DRIVER='.$driver); }
echo 'PASS: '.$checks." MySQL integration checks\n";
