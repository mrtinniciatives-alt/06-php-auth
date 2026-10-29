<?php
declare(strict_types=1);

require_once __DIR__ . '/../bootstrap.php';
require_role('admin');

$message = null;
$error = null;
$pdo = db($config);
$currentUser = current_user();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_verify()) {
        $error = 'Your session has expired. Please try again.';
    } else {
        $targetId = filter_input(INPUT_POST, 'user_id', FILTER_VALIDATE_INT);
        $newRole = (string) ($_POST['role'] ?? '');

        if ($targetId === false || $targetId === null || !in_array($newRole, ['user', 'admin'], true)) {
            $error = 'Invalid role update.';
        } elseif ((int) $targetId === (int) $currentUser['id'] && $newRole === 'user') {
            $error = 'You cannot demote yourself.';
        } else {
            $update = $pdo->prepare('UPDATE users SET role = ? WHERE id = ?');
            $update->execute([$newRole, (int) $targetId]);
            if ($update->rowCount() === 1) {
                $message = 'User role updated successfully.';
            } else {
                $error = 'User role was not changed.';
            }
        }
    }
}

$users = $pdo->query('SELECT id, name, email, role, created_at, last_login_at FROM users ORDER BY id ASC')->fetchAll();
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
    <title>User administration</title>
    <link rel="stylesheet" href="../assets/auth.css">
</head>
<body>
<nav class="navbar"><a href="../dashboard.php">Dashboard</a><a href="users.php" aria-current="page">Admin</a></nav>
<main class="page-shell">
    <section class="data-card">
        <h1>User administration</h1>
        <?php if ($message !== null): ?><div class="alert alert-success" role="status"><?= e($message) ?></div><?php endif; ?>
        <?php if ($error !== null): ?><div class="alert alert-error" role="alert"><?= e($error) ?></div><?php endif; ?>
        <div class="table-wrap">
            <table>
                <thead><tr><th>Name</th><th>Email</th><th>Role</th><th>Created</th><th>Last login</th><th>Action</th></tr></thead>
                <tbody>
                <?php foreach ($users as $row): ?>
                    <tr>
                        <td><?= e($row['name']) ?></td>
                        <td><?= e($row['email']) ?></td>
                        <td><?= e($row['role']) ?></td>
                        <td><?= e($row['created_at']) ?></td>
                        <td><?= e($row['last_login_at'] ?? 'Never') ?></td>
                        <td>
                            <form method="post" class="role-form">
                                <?= csrf_field() ?>
                                <input type="hidden" name="user_id" value="<?= e($row['id']) ?>">
                                <?php if ((int) $row['id'] === (int) $currentUser['id']): ?>
                                    <span class="self-label">Current user</span>
                                <?php else: ?>
                                    <input type="hidden" name="role" value="<?= $row['role'] === 'admin' ? 'user' : 'admin' ?>">
                                    <button class="small-button" type="submit"><?= $row['role'] === 'admin' ? 'Demote' : 'Promote' ?></button>
                                <?php endif; ?>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <form method="post" action="../logout.php"><?= csrf_field() ?><button class="small-button logout-button" type="submit">Log out</button></form>
    </section>
</main>
</body>
</html>
