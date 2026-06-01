<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/functions.php';

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    check_csrf();
    $username = trim((string) ($_POST['username'] ?? ''));
    $password = (string) ($_POST['password'] ?? '');

    if (strlen($username) < 3 || strlen($password) < 6) {
        $error = 'Korisničko ime mora imati barem 3 znaka, a lozinka barem 6 znakova.';
    } elseif (text_length($username) > 80) {
        $error = 'Korisničko ime može imati najviše 80 znakova.';
    } else {
        try {
            $stmt = db()->prepare('INSERT INTO users (username, password_hash, role) VALUES (?, ?, "user")');
            $stmt->execute([$username, password_hash($password, PASSWORD_DEFAULT)]);
            flash('Registracija je uspješna. Sada se možete prijaviti.');
            header('Location: login.php');
            exit;
        } catch (PDOException) {
            $error = 'Korisničko ime je već zauzeto.';
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
        <label>Korisničko ime
            <input name="username" minlength="3" maxlength="80" required autocomplete="username">
        </label>
        <label>Lozinka
            <input type="password" name="password" required autocomplete="new-password" minlength="6">
        </label>
        <button type="submit">Registriraj se</button>
    </form>
</section>
<?php require __DIR__ . '/includes/footer.php'; ?>
