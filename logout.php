<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/functions.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: login.php');
    exit;
}

check_csrf();
$_SESSION = [];
session_destroy();
header('Location: login.php');
exit;
