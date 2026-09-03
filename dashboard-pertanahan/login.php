<?php
require_once __DIR__ . '/db.php';

if (!empty($_SESSION['user'])) {
    header('Location: dashboard.php');
    exit;
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf()) {
        $error = 'Sesi formulir tidak valid. Muat ulang halaman dan coba lagi.';
    } else {
        $stmt = db()->prepare('SELECT id, name, email, password_hash, role FROM users WHERE email = ?');
        $stmt->execute(array(trim($_POST['email'] ?? '')));
        $user = $stmt->fetch();
        if ($user && password_verify($_POST['password'] ?? '', $user['password_hash'])) {
            unset($user['password_hash']);
            session_regenerate_id(true);
            $_SESSION['user'] = $user;
            header('Location: dashboard.php');
            exit;
        }
        $error = 'Email atau kata sandi tidak sesuai.';
    }
}
?>
<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Masuk | <?= h(APP_NAME) ?></title>
    <link rel="stylesheet" href="assets/style.css">
</head>
<body class="auth-page">
    <main class="auth-card">
        <p class="eyebrow">ATR/BPN · MVP</p>
        <h1>Dashboard Pertanahan</h1>
        <p>Masuk sesuai peran untuk melihat atau memperbarui data pertanahan.</p>
        <?php if ($error): ?><p class="alert error" role="alert"><?= h($error) ?></p><?php endif; ?>
        <form method="post" class="form-stack">
            <input type="hidden" name="csrf_token" value="<?= h(csrf_token()) ?>">
            <label>Email<input type="email" name="email" required autocomplete="email" value="joshua@demo.local"></label>
            <label>Kata sandi<input type="password" name="password" required autocomplete="current-password" value="Demo123!"></label>
            <button type="submit">Masuk ke dashboard</button>
        </form>
        <p class="hint">Akun demo: joshua@demo.local, admin@demo.local, atau pemda@demo.local. Kata sandi: Demo123!</p>
    </main>
</body>
</html>
