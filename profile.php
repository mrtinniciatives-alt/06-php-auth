<?php
declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';
require_login();
$user = current_user();
if ($user === null) {
    redirect('login.php');
}

$profileErrors = [];
$passwordErrors = [];
$profileSuccess = null;
$passwordSuccess = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_verify()) {
        $profileErrors['csrf'] = 'Your session has expired. Please try again.';
        $passwordErrors['csrf'] = 'Your session has expired. Please try again.';
    } elseif (($action = (string) ($_POST['action'] ?? '')) === 'profile') {
        $name = trim((string) ($_POST['name'] ?? ''));
        $email = strtolower(trim((string) ($_POST['email'] ?? '')));

        if ($name === '') {
            $profileErrors['name'] = 'Name is required.';
        } elseif (mb_strlen($name) > 100) {
            $profileErrors['name'] = 'Name must be 100 characters or fewer.';
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL) || mb_strlen($email) > 255) {
            $profileErrors['email'] = 'Enter a valid email address of 255 characters or fewer.';
        } else {
            $pdo = db($config);
            $check = $pdo->prepare('SELECT id FROM users WHERE email = ? AND id <> ? LIMIT 1');
            $check->execute([$email, (int) $user['id']]);
            if ($check->fetchColumn() !== false) {
                $profileErrors['email'] = 'An account with this email already exists.';
            }
        }

        if ($profileErrors === []) {
            $update = db($config)->prepare('UPDATE users SET name = ?, email = ?, updated_at = NOW() WHERE id = ?');
            $update->execute([$name, $email, (int) $user['id']]);
            $user['name'] = $name;
            $user['email'] = $email;
            $profileSuccess = 'Profile updated successfully.';
            session_regenerate_id(true);
        }
    } elseif ($action === 'password') {
        $currentPassword = (string) ($_POST['current_password'] ?? '');
        $newPassword = (string) ($_POST['new_password'] ?? '');
        $confirmPassword = (string) ($_POST['confirm_password'] ?? '');

        $pdo = db($config);
        $lookup = $pdo->prepare('SELECT password_hash FROM users WHERE id = ? LIMIT 1');
        $lookup->execute([(int) $user['id']]);
        $row = $lookup->fetch();

        if (!is_array($row) || !password_verify($currentPassword, (string) $row['password_hash'])) {
            $passwordErrors['current_password'] = 'Current password is incorrect.';
        }
        if (strlen($newPassword) < 10) {
            $passwordErrors['new_password'] = 'Password must be at least 10 characters.';
        } elseif (!preg_match('/[A-Za-z]/', $newPassword) || !preg_match('/[0-9]/', $newPassword)) {
            $passwordErrors['new_password'] = 'Password must contain at least one letter and one number.';
        }
        if (!hash_equals($newPassword, $confirmPassword)) {
            $passwordErrors['confirm_password'] = 'Passwords do not match.';
        }

        if ($passwordErrors === []) {
            $newHash = password_hash($newPassword, PASSWORD_DEFAULT);
            if ($newHash === false) {
                $passwordErrors['new_password'] = 'Unable to create a secure password hash.';
            } else {
                $update = $pdo->prepare('UPDATE users SET password_hash = ?, updated_at = NOW() WHERE id = ?');
                $update->execute([$newHash, (int) $user['id']]);
                $passwordSuccess = 'Password changed successfully.';
                session_regenerate_id(true);
            }
        }
    }
}
?>
<!doctype html>
<html lang="en">
<head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>Profile</title><link rel="stylesheet" href="assets/auth.css"></head>
<body>
<nav class="navbar"><a href="dashboard.php">Dashboard</a><?php if ((string) $user['role'] === 'admin'): ?><a href="admin/users.php">Admin</a><?php endif; ?><a href="profile.php" aria-current="page">Profile</a></nav>
<main class="auth-shell">
    <section class="auth-card">
        <h1>Profile</h1>

        <h2>Account details</h2>
        <?php if ($profileSuccess !== null): ?><div class="alert alert-success" role="status"><?= e($profileSuccess) ?></div><?php endif; ?>
        <?php if (isset($profileErrors['csrf'])): ?><div class="alert alert-error" role="alert"><?= e($profileErrors['csrf']) ?></div><?php endif; ?>
        <form method="post" action="profile.php">
            <?= csrf_field() ?><input type="hidden" name="action" value="profile">
            <div class="field"><label for="name">Name</label><input id="name" name="name" type="text" maxlength="100" value="<?= e($profileErrors ? (string) ($_POST['name'] ?? $user['name']) : $user['name']) ?>" required><?php if (isset($profileErrors['name'])): ?><p class="field-error"><?= e($profileErrors['name']) ?></p><?php endif; ?></div>
            <div class="field"><label for="email">Email</label><input id="email" name="email" type="email" maxlength="255" value="<?= e($profileErrors ? (string) ($_POST['email'] ?? $user['email']) : $user['email']) ?>" required><?php if (isset($profileErrors['email'])): ?><p class="field-error"><?= e($profileErrors['email']) ?></p><?php endif; ?></div>
            <button class="auth-button" type="submit">Update profile</button>
        </form>

        <h2 class="section-heading">Change password</h2>
        <?php if ($passwordSuccess !== null): ?><div class="alert alert-success" role="status"><?= e($passwordSuccess) ?></div><?php endif; ?>
        <form method="post" action="profile.php">
            <?= csrf_field() ?><input type="hidden" name="action" value="password">
            <div class="field"><label for="current_password">Current password</label><input id="current_password" name="current_password" type="password" autocomplete="current-password" required><?php if (isset($passwordErrors['current_password'])): ?><p class="field-error"><?= e($passwordErrors['current_password']) ?></p><?php endif; ?></div>
            <div class="field"><label for="new_password">New password</label><input id="new_password" name="new_password" type="password" minlength="10" autocomplete="new-password" required><?php if (isset($passwordErrors['new_password'])): ?><p class="field-error"><?= e($passwordErrors['new_password']) ?></p><?php endif; ?></div>
            <div class="field"><label for="confirm_password">Confirm new password</label><input id="confirm_password" name="confirm_password" type="password" minlength="10" autocomplete="new-password" required><?php if (isset($passwordErrors['confirm_password'])): ?><p class="field-error"><?= e($passwordErrors['confirm_password']) ?></p><?php endif; ?></div>
            <button class="auth-button" type="submit">Change password</button>
        </form>
    </section>
</main>
</body>
</html>
