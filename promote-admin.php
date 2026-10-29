<?php
declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit("Forbidden: this script can only run from the command line.\n");
}

$email = isset($argv[1]) ? strtolower(trim($argv[1])) : '';
if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    fwrite(STDERR, "Usage: php promote-admin.php user@example.com\n");
    exit(1);
}

$config = require __DIR__ . '/config/db.php';
require_once __DIR__ . '/src/db.php';

try {
    $pdo = db($config);
    $update = $pdo->prepare("UPDATE users SET role = 'admin', updated_at = NOW() WHERE email = ?");
    $update->execute([$email]);

    if ($update->rowCount() !== 1) {
        fwrite(STDERR, "No user found for {$email}, or that user is already an admin.\n");
        exit(1);
    }

    echo "User {$email} is now an admin.\n";
} catch (Throwable $exception) {
    fwrite(STDERR, 'Error: ' . $exception->getMessage() . "\n");
    exit(1);
}
