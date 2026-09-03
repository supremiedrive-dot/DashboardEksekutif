<?php
declare(strict_types=1);

define('APP_NAME', 'Dashboard Pertanahan');
define('DB_PATH', __DIR__ . '/data/dashboard.sqlite');
define('SESSION_PATH', __DIR__ . '/data/sessions');
define('KKP_MODE', getenv('KKP_MODE') ?: 'mock');
define('KKP_API_BASE_URL', rtrim(getenv('KKP_API_BASE_URL') ?: '', '/'));
define('KKP_METRICS_ENDPOINT', getenv('KKP_METRICS_ENDPOINT') ?: '/v1/pertanahan/metrics');
define('KKP_API_BEARER_TOKEN', getenv('KKP_API_BEARER_TOKEN') ?: '');

if (!is_dir(SESSION_PATH)) {
    mkdir(SESSION_PATH, 0700, true);
}
session_save_path(SESSION_PATH);
session_start();

function h($value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function csrf_token(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }

    return $_SESSION['csrf_token'];
}

function verify_csrf(): bool
{
    return isset($_POST['csrf_token'], $_SESSION['csrf_token'])
        && hash_equals($_SESSION['csrf_token'], $_POST['csrf_token']);
}

function require_login(): void
{
    if (empty($_SESSION['user'])) {
        header('Location: login.php');
        exit;
    }
}

function require_role(array $roles): void
{
    require_login();
    if (!in_array($_SESSION['user']['role'], $roles, true)) {
        http_response_code(403);
        exit('Anda tidak memiliki hak akses untuk halaman ini.');
    }
}

function kkp_live_configured(): bool
{
    return KKP_MODE === 'live' && KKP_API_BASE_URL !== '' && KKP_API_BEARER_TOKEN !== '';
}
