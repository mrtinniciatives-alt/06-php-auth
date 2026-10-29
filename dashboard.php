<?php
declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';
require_login();
$user = current_user();

if ($user === null) {
    redirect('login.php');
}

$lastLogin = $user['last_login_at'] === null
    ? 'First login'
    : date('F j, Y g:i A', strtotime((string) $user['last_login_at']));
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
        <p>Welcome, <?= e($user['name']) ?>.</p>
        <dl class="account-details">
            <dt>Email</dt><dd><?= e($user['email']) ?></dd>
            <dt>Role</dt><dd><?= e($user['role']) ?></dd>
            <dt>Last login</dt><dd><?= e($lastLogin) ?></dd>
        </dl>
        <form method="post" action="logout.php">
            <?= csrf_field() ?>
            <button class="auth-button" type="submit">Log out</button>
        </form>
    </section>
</main>
</body>
</html>
