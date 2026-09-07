<?php
declare(strict_types=1);
require_once __DIR__ . '/../config/environment.php';
$file=tempnam(sys_get_temp_dir(),'dp_env_');
try {
    file_put_contents($file,"\xEF\xBB\xBFTEST_ENV_VALUE=from_file\nTEST_ENV_EMPTY=\nTEST_ENV_QUOTED=\"a # literal\"\n");
    putenv('TEST_ENV_VALUE=from_server');
    load_environment($file);
    if (getenv('TEST_ENV_VALUE')!=='from_server' || getenv('TEST_ENV_EMPTY')!=='' || getenv('TEST_ENV_QUOTED')!=='a # literal') {
        throw new RuntimeException('Environment precedence or literal parsing failed');
    }
    file_put_contents($file,"NOT A VALID LINE\n");
    $rejected=false;
    try { load_environment($file); } catch(RuntimeException $exception) { $rejected=true; }
    if(!$rejected) { throw new RuntimeException('Malformed configuration was accepted'); }
    echo "PASS: 4 environment checks\n";
} finally {
    unlink($file);
    foreach(['TEST_ENV_VALUE','TEST_ENV_EMPTY','TEST_ENV_QUOTED'] as $key) { putenv($key); }
}
