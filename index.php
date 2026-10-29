<?php
declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';
redirect(isset($_SESSION['user_id']) ? 'dashboard.php' : 'login.php');
