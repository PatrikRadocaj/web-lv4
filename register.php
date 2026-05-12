<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/functions.php';

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    check_csrf();
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if (strlen($username) < 3 || strlen($password) < 6) {
        $error = 'Korisnicko ime mora imati barem 3 znaka, a lozinka barem 6 znakova.';
    } else {
        try {
            $stmt = db()->prepare('INSERT INTO users (username, password_hash, role) VALUES (?, ?, "user")');
            $stmt->execute([$username, password_hash($password, PASSWORD_DEFAULT)]);
            flash('Registracija je uspjesna. Sada se mozete prijaviti.');
            header('Location: login.php');
            exit;
        } catch (PDOException) {
            $error = 'Korisnicko ime je vec zauzeto.';
        }
    }
}

$title = 'Registracija';
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
            <input type="password" name="password" required autocomplete="new-password" minlength="6">
        </label>
        <button type="submit">Registriraj se</button>
    </form>
</section>
<?php require __DIR__ . '/includes/footer.php'; ?>
