<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/functions.php';

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    check_csrf();
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    $stmt = db()->prepare('SELECT * FROM users WHERE username = ?');
    $stmt->execute([$username]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password_hash'])) {
        session_regenerate_id(true);
        $_SESSION['user'] = [
            'id' => (int) $user['id'],
            'username' => $user['username'],
            'role' => $user['role'],
        ];
        header('Location: index.php');
        exit;
    }
    $error = 'Neispravno korisnicko ime ili lozinka.';
}

$title = 'Prijava korisnika';
require __DIR__ . '/includes/header.php';
?>
<?php if ($error): ?><p class="error"><?= h($error) ?></p><?php endif; ?>
<section class="form-panel">
    <form method="post" class="stacked">
        <input type="hidden" name="csrf" value="<?= h(csrf_token()) ?>">
        <label>Korisnicko ime
            <input name="username" required autocomplete="username">
        </label>
        <label>Lozinka
            <input type="password" name="password" required autocomplete="current-password">
        </label>
        <button type="submit">Prijavi se</button>
    </form>
    <p>Demo admin: <strong>admin / admin123</strong>. Demo korisnik: <strong>korisnik / korisnik123</strong>.</p>
</section>
<?php require __DIR__ . '/includes/footer.php'; ?>
