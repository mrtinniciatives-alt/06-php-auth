<?php
declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';

if (!isset($_SESSION['user_id'])) {
    redirect('register.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'logout') {
    if (csrf_verify()) {
        $_SESSION = [];
        if (isset($_COOKIE[session_name()])) {
            setcookie(session_name(), '', ['expires' => time() - 3600, 'path' => '/', 'httponly' => true, 'samesite' => 'Lax']);
        }
        session_destroy();
        redirect('register.php');
    }
}
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Dashboard</title>
    <link rel="stylesheet" href="assets/auth.css">
</head>
<body>
<main class="auth-shell">
    <section class="auth-card">
        <h1>Dashboard</h1>
        <p>Welcome, <?= e($_SESSION['user_name'] ?? 'User') ?>.</p>
        <form method="post" action="dashboard.php">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="logout">
            <button class="auth-button" type="submit">Log out</button>
        </form>
    </section>
</main>
</body>
</html>
