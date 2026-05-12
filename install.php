<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/db.php';

$pdo = db(false);
$pdo->exec('CREATE DATABASE IF NOT EXISTS ' . DB_NAME . ' CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
$pdo = db();
$sql = file_get_contents(__DIR__ . '/database.sql');
$pdo->exec($sql);

$users = [
    ['admin', 'admin123', 'admin'],
    ['korisnik', 'korisnik123', 'user'],
];

$stmt = $pdo->prepare('INSERT INTO users (username, password_hash, role) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE role = VALUES(role)');
foreach ($users as [$username, $password, $role]) {
    $stmt->execute([$username, password_hash($password, PASSWORD_DEFAULT), $role]);
}

echo '<p>Instalacija je gotova.</p>';
echo '<p>Admin: admin / admin123<br>Korisnik: korisnik / korisnik123</p>';
echo '<p><a href="login.php">Idi na prijavu</a></p>';
