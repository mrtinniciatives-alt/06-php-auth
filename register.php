<?php
declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_verify()) {
        $errors['csrf'] = 'Your session has expired. Please try again.';
    } else {
        $name = trim((string) ($_POST['name'] ?? ''));
        $email = trim((string) ($_POST['email'] ?? ''));
        $password = (string) ($_POST['password'] ?? '');
        $confirmPassword = (string) ($_POST['confirm_password'] ?? '');

        if ($name === '') {
            $errors['name'] = 'Name is required.';
        } elseif (mb_strlen($name) > 100) {
            $errors['name'] = 'Name must be 100 characters or fewer.';
        }

        if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = 'Enter a valid email address.';
        } elseif (mb_strlen($email) > 255) {
            $errors['email'] = 'Email must be 255 characters or fewer.';
        } else {
            $pdo = db($config);
            $stmt = $pdo->prepare('SELECT 1 FROM users WHERE email = ? LIMIT 1');
            $stmt->execute([$email]);
            if ($stmt->fetchColumn() !== false) {
                $errors['email'] = 'An account with this email already exists.';
            }
        }

        if (strlen($password) < 10) {
            $errors['password'] = 'Password must be at least 10 characters.';
        } elseif (!preg_match('/[A-Za-z]/', $password) || !preg_match('/[0-9]/', $password)) {
            $errors['password'] = 'Password must contain at least one letter and one number.';
        }

        if (!hash_equals($password, $confirmPassword)) {
            $errors['confirm_password'] = 'Passwords do not match.';
        }

        if ($errors === []) {
            $passwordHash = password_hash($password, PASSWORD_DEFAULT);
            if ($passwordHash === false) {
                $errors['password'] = 'Unable to create a secure password hash.';
            } else {
                try {
                    $pdo ??= db($config);
                    $stmt = $pdo->prepare(
                        'INSERT INTO users (name, email, password_hash, created_at) VALUES (?, ?, ?, NOW())'
                    );
                    $stmt->execute([$name, $email, $passwordHash]);
                    $userId = (int) $pdo->lastInsertId();

                    session_regenerate_id(true);
                    $_SESSION['user_id'] = $userId;
                    $_SESSION['user_name'] = $name;
                    $_SESSION['user_email'] = $email;
                    unset($_SESSION['csrf_token']);
                    redirect('dashboard.php');
                } catch (PDOException $exception) {
                    if ((string) $exception->getCode() === '23000') {
                        $errors['email'] = 'An account with this email already exists.';
                    } else {
                        throw $exception;
                    }
                }
            }
        }
    }
}
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Create an account</title>
    <link rel="stylesheet" href="assets/auth.css">
</head>
<body>
<main class="auth-shell">
    <section class="auth-card" aria-labelledby="register-title">
        <h1 id="register-title">Create account</h1>
        <?php if (isset($errors['csrf'])): ?>
            <div class="alert alert-error" role="alert"><?= e($errors['csrf']) ?></div>
        <?php endif; ?>
        <form method="post" action="register.php" autocomplete="off" novalidate>
            <?= csrf_field() ?>

            <div class="field">
                <label for="name">Name</label>
                <input id="name" name="name" type="text" maxlength="100" value="<?= e(old('name')) ?>" autocomplete="name" required>
                <?php if (isset($errors['name'])): ?><p class="field-error"><?= e($errors['name']) ?></p><?php endif; ?>
            </div>

            <div class="field">
                <label for="email">Email</label>
                <input id="email" name="email" type="email" maxlength="255" value="<?= e(old('email')) ?>" autocomplete="email" required>
                <?php if (isset($errors['email'])): ?><p class="field-error"><?= e($errors['email']) ?></p><?php endif; ?>
            </div>

            <div class="field">
                <label for="password">Password</label>
                <input id="password" name="password" type="password" minlength="10" autocomplete="new-password" required>
                <?php if (isset($errors['password'])): ?><p class="field-error"><?= e($errors['password']) ?></p><?php endif; ?>
            </div>

            <div class="field">
                <label for="confirm_password">Confirm password</label>
                <input id="confirm_password" name="confirm_password" type="password" minlength="10" autocomplete="new-password" required>
                <?php if (isset($errors['confirm_password'])): ?><p class="field-error"><?= e($errors['confirm_password']) ?></p><?php endif; ?>
            </div>

            <button class="auth-button" type="submit">Register</button>
        </form>
        <p class="auth-note">Passwords: 10+ characters, including a letter and number.</p>
    </section>
</main>
</body>
</html>
