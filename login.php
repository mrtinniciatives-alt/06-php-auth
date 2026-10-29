<?php
declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';
require_guest();

$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_verify()) {
        $error = 'Invalid email or password';
    } else {
        $email = strtolower(trim((string) ($_POST['email'] ?? '')));
        $password = (string) ($_POST['password'] ?? '');
        $pdo = db($config);

        $stmt = $pdo->prepare('SELECT id, name, email, password_hash, role FROM users WHERE email = ? LIMIT 1');
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        $valid = is_array($user) && password_verify($password, (string) $user['password_hash']);
        $ipAddress = $_SERVER['REMOTE_ADDR'] ?? null;
        $attempt = $pdo->prepare(
            'INSERT INTO login_attempts (user_id, email, ip_address, successful, attempted_at) VALUES (?, ?, ?, ?, NOW())'
        );
        $attempt->execute([$valid ? (int) $user['id'] : null, $email, is_string($ipAddress) ? substr($ipAddress, 0, 45) : null, $valid ? 1 : 0]);

        if (!$valid) {
            $error = 'Invalid email or password';
        } else {
            if (password_needs_rehash((string) $user['password_hash'], PASSWORD_DEFAULT)) {
                $newHash = password_hash($password, PASSWORD_DEFAULT);
                $rehash = $pdo->prepare('UPDATE users SET password_hash = ?, updated_at = NOW() WHERE id = ?');
                $rehash->execute([$newHash, (int) $user['id']]);
            }

            $pdo->prepare('UPDATE users SET last_login_at = NOW() WHERE id = ?')->execute([(int) $user['id']]);

            session_regenerate_id(true);
            $_SESSION['user_id'] = (int) $user['id'];
            $_SESSION['role'] = (string) $user['role'];
            $_SESSION['last_activity'] = time();
            unset($_SESSION['csrf_token']);

            redirect(pop_intended_url());
        }
    }
}
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Log in</title>
    <link rel="stylesheet" href="assets/auth.css">
</head>
<body>
<main class="auth-shell">
    <section class="auth-card" aria-labelledby="login-title">
        <h1 id="login-title">Log in</h1>
        <?php $warning = flash('warning'); if ($warning !== null): ?>
            <div class="alert alert-error" role="status"><?= e($warning) ?></div>
        <?php endif; ?>
        <?php $success = flash('success'); if ($success !== null): ?>
            <div class="alert alert-success" role="status"><?= e($success) ?></div>
        <?php endif; ?>
        <?php if ($error !== null): ?>
            <div class="alert alert-error" role="alert"><?= e($error) ?></div>
        <?php endif; ?>
        <form method="post" action="login.php" autocomplete="off" novalidate>
            <?= csrf_field() ?>
            <div class="field">
                <label for="email">Email</label>
                <input id="email" name="email" type="email" maxlength="255" value="<?= e(old('email')) ?>" autocomplete="email" required>
            </div>
            <div class="field">
                <label for="password">Password</label>
                <input id="password" name="password" type="password" autocomplete="current-password" required>
            </div>
            <button class="auth-button" type="submit">Log in</button>
        </form>
        <p class="auth-note">No account? <a href="register.php">Create one</a></p>
    </section>
</main>
</body>
</html>
