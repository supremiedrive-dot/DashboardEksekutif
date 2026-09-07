<?php
declare(strict_types=1);
require_once __DIR__ . '/../database/Connection.php';
$pdo=DatabaseConnection::create();
if (getenv('APP_ENV')!=='testing' || substr(getenv('DB_NAME'),-5)!=='_test') { throw new RuntimeException('Dedicated test database required.'); }
$base=getenv('TEST_BASE_URL') ?: 'http://127.0.0.1:8094';
if (!preg_match('~^http://127\.0\.0\.1:\d+$~D',$base)) { throw new RuntimeException('HTTP test must target localhost.'); }
$password=bin2hex(random_bytes(20));
$user=$pdo->prepare('INSERT INTO users (role_id,name,email,password_hash) SELECT id,?,?,? FROM roles WHERE code=? ON DUPLICATE KEY UPDATE password_hash=VALUES(password_hash)');
foreach (['admin','eksekutif'] as $role) { $user->execute(['HTTP test '.$role,'http-'.$role.'@example.invalid',password_hash($password,PASSWORD_DEFAULT),$role]); }
$cookie=tempnam(sys_get_temp_dir(),'dp_http_');
$checks=0;
function checkHttp(bool $ok,string $message): void { global $checks; if(!$ok) { throw new RuntimeException($message); } $checks++; }
function requestPage(string $path, ?array $body=null, bool $follow=true): array
{
    global $base,$cookie;
    $curl=curl_init($base.$path);
    curl_setopt_array($curl,[CURLOPT_RETURNTRANSFER=>true,CURLOPT_COOKIEFILE=>$cookie,CURLOPT_COOKIEJAR=>$cookie,CURLOPT_FOLLOWLOCATION=>$follow,CURLOPT_TIMEOUT=>20]);
    if($body!==null) { curl_setopt($curl,CURLOPT_POSTFIELDS,http_build_query($body)); }
    $html=curl_exec($curl); $status=curl_getinfo($curl,CURLINFO_HTTP_CODE); curl_close($curl);
    return ['status'=>$status,'html'=>$html];
}
function token(array $page): string
{
    preg_match('/name="csrf_token" value="([^"]+)"/',$page['html'],$match);
    if(!isset($match[1])) { throw new RuntimeException('CSRF field missing'); }
    return $match[1];
}
try {
    checkHttp(requestPage('/dashboard.php',null,false)['status']===302,'Authentication required');
    foreach (['/.env','/.git/config','/data/dashboard.sqlite','/database/schema.sql','/tmp/mysql-baseline/db.php'] as $path) {
        checkHttp(requestPage($path)['status']===403,'Protected path: '.$path);
    }
    checkHttp(requestPage('/assets/dashboard.css')['status']===200,'CSS accessible');
    $login=requestPage('/login.php');
    checkHttp(strpos($login['html'],'value="Demo')===false,'No demo password in UI');
    $login=requestPage('/login.php',['email'=>'http-admin@example.invalid','password'=>$password,'csrf_token'=>token($login)]);
    checkHttp($login['status']===200 && strpos($login['html'],'RINGKASAN EKSEKUTIF')!==false,'Admin login');
    $empty=requestPage('/dashboard.php?province=32&date=2097-04-02');
    checkHttp($empty['status']===200 && strpos($empty['html'],'value="2097-04-02"')!==false && strpos($empty['html'],'Tidak ada data untuk periode ini.')!==false,'Explicit empty period');
    checkHttp(requestPage('/dashboard.php?province=32&date=2097-02-29')['status']===422,'Invalid date 422');
    checkHttp(requestPage('/dashboard.php?date[]=2097-01-01')['status']===422,'Array input 422');
    $form=requestPage('/input-data.php');
    $body=['csrf_token'=>token($form),'region_code'=>'3273','report_date'=>'2097-04-01','total_records'=>'20','validated_stage_1'=>'10','validated_stage_2'=>'5','area_hectare'=>'4.5'];
    checkHttp(strpos(requestPage('/input-data.php',$body)['html'],'Data Pemda tersimpan')!==false,'Pemda writes MySQL');
    checkHttp(strpos(requestPage('/input-data.php',$body)['html'],'Data Pemda tersimpan')!==false,'Pemda repeat upsert');
    $bad=$body; $bad['report_date']='2097-02-29';
    checkHttp(strpos(requestPage('/input-data.php',$bad)['html'],'periode laporan tidak valid')!==false,'Pemda invalid calendar');
    $pdo->prepare('INSERT INTO metric_snapshots (region_code,report_date,total_records,validated_stage_1,validated_stage_2,area_hectare,source) VALUES (?,?,?,?,?,?,?) ON DUPLICATE KEY UPDATE total_records=VALUES(total_records)')->execute(['3273','2097-04-01',10,8,5,2.5,'kkp']);
    $api=requestPage('/api/kkp.php?province=32&date=2097-04-01');
    checkHttp($api['status']===200 && count(json_decode($api['html'],true)['data'])===1,'KKP API');
    checkHttp(requestPage('/api/kkp.php?province=32&date=2097-02-29')['status']===422,'KKP API date validation');
    $sync=requestPage('/sync-kkp.php');
    checkHttp(strpos(requestPage('/sync-kkp.php',['csrf_token'=>token($sync),'province'=>'32','date'=>'2097-04-01'])['html'],'1 snapshot KKP berhasil')!==false,'KKP mock transactional upsert');
    $import=requestPage('/import-excel.php');
    checkHttp($import['status']===200,'Importer page');
    $before=(int)$pdo->query('SELECT COUNT(*) FROM import_batches')->fetchColumn();
    requestPage('/import-excel.php',['mode'=>'import','report_date'=>'2097-05-01']);
    checkHttp((int)$pdo->query('SELECT COUNT(*) FROM import_batches')->fetchColumn()===$before,'Import CSRF prevents writes');
    $preview=requestPage('/import-excel.php',['csrf_token'=>token($import),'mode'=>'preview','report_date'=>'2097-05-01']);
    checkHttp(strpos($preview['html'],'Preview impor berhasil')!==false,'HTTP workbook preview');
    $imported=requestPage('/import-excel.php',['csrf_token'=>token($preview),'mode'=>'import','report_date'=>'2097-05-01']);
    checkHttp(strpos($imported['html'],'Impor data Excel selesai')!==false,'HTTP workbook import');
    requestPage('/logout.php');
    $login=requestPage('/login.php');
    requestPage('/login.php',['email'=>'http-eksekutif@example.invalid','password'=>$password,'csrf_token'=>token($login)]);
    foreach (['/input-data.php','/sync-kkp.php','/import-excel.php'] as $path) { checkHttp(requestPage($path)['status']===403,'Executive role restriction: '.$path); }
    echo 'PASS: '.$checks." HTTP checks\n";
} finally { unlink($cookie); }
