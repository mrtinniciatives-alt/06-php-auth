<?php
declare(strict_types=1);

function current_user(): ?array
{
    if (!isset($_SESSION['user_id'])) {
        return null;
    }

    global $config;
    $stmt = db($config)->prepare('SELECT id, name, email, role, created_at, last_login_at FROM users WHERE id = ? LIMIT 1');
    $stmt->execute([(int) $_SESSION['user_id']]);
    $user = $stmt->fetch();

    if (!is_array($user)) {
        $_SESSION = [];
        return null;
    }

    return $user;
}

function require_login(): void
{
    if (!isset($_SESSION['user_id'])) {
        $requestUri = (string) ($_SERVER['REQUEST_URI'] ?? 'dashboard.php');
        if (is_safe_relative_url($requestUri)) {
            $_SESSION['intended_url'] = $requestUri;
        }
        redirect('login.php');
    }
}

function require_guest(): void
{
    if (isset($_SESSION['user_id'])) {
        redirect('dashboard.php');
    }
}

function require_role(string $role): void
{
    require_login();
    $user = current_user();
    if ($user === null || !hash_equals($role, (string) $user['role'])) {
        http_response_code(403);
        echo '<!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>Access denied</title><link rel="stylesheet" href="../assets/auth.css"></head><body><main class="auth-shell"><section class="auth-card"><h1>Access denied</h1><p>You do not have permission to view this page.</p><p><a href="../dashboard.php">Return to dashboard</a></p></section></main></body></html>';
        exit;
    }
}

function is_safe_relative_url(string $url): bool
{
    if ($url === '' || strlen($url) > 2048 || str_contains($url, '\\')) {
        return false;
    }
    if (preg_match('/[\x00-\x20\x7f]/', $url) === 1 || str_starts_with($url, '//')) {
        return false;
    }

    $parts = parse_url($url);
    if ($parts === false || isset($parts['scheme']) || isset($parts['host'])) {
        return false;
    }

    return str_starts_with($url, '/') || str_starts_with($url, './') || !str_contains($url, ':');
}

function pop_intended_url(): string
{
    $url = $_SESSION['intended_url'] ?? null;
    unset($_SESSION['intended_url']);
    return is_string($url) && is_safe_relative_url($url) ? $url : 'dashboard.php';
}
