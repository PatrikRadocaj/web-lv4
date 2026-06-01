<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/functions.php';
require_login();

$user = current_user();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    check_csrf();
    $action = $_POST['action'] ?? '';

    if ($action === 'remove') {
        $filmId = (int) ($_POST['film_id'] ?? 0);
        if (cart_has_film($filmId)) {
            remove_film_from_cart($filmId);
            flash('Film je uklonjen iz košarice.');
        } else {
            flash('Film nije pronađen u košarici.', 'error');
        }
        header('Location: cart.php#cart');
        exit;
    }

    if ($action === 'clear') {
        $_SESSION['film_cart'] = [];
        flash('Košarica je ispražnjena.');
        header('Location: cart.php#cart');
        exit;
    }

    if ($action === 'checkout') {
        $films = fetch_cart_films();
        if (!$films) {
            flash('Košarica je prazna.', 'error');
            header('Location: cart.php#cart');
            exit;
        }

        $pdo = db();
        $pdo->beginTransaction();
        try {
            $insert = $pdo->prepare('INSERT IGNORE INTO desired_films (user_id, film_id) VALUES (?, ?)');
            foreach ($films as $film) {
                $insert->execute([(int) $user['id'], (int) $film['id']]);
            }
            $pdo->commit();
            $_SESSION['film_cart'] = [];
            flash('Posudba je završena. Filmovi su dodani u osobnu videoteku.');
            header('Location: my_videoteka.php#videoteka');
            exit;
        } catch (Throwable) {
            $pdo->rollBack();
            flash('Posudbu nije moguće završiti. Pokušajte ponovno.', 'error');
            header('Location: cart.php#cart');
            exit;
        }
    }

    flash('Nepoznata akcija.', 'error');
    header('Location: cart.php#cart');
    exit;
}

$films = fetch_cart_films();
$title = 'Košarica za posudbu';
require __DIR__ . '/includes/header.php';
?>
<section class="cart-panel" id="cart">
    <h2>Košarica za posudbu filmova</h2>
    <p>Filmovi će biti dodani u osobnu videoteku tek nakon završetka posudbe.</p>

    <?php if ($films): ?>
        <div class="table-wrapper">
            <table>
                <thead>
                    <tr><th>Naslov</th><th>Godina</th><th>Žanr</th><th>Ocjena</th><th>Akcija</th></tr>
                </thead>
                <tbody>
                <?php foreach ($films as $film): ?>
                    <tr id="cart-film-<?= h($film['id']) ?>">
                        <td><?= h($film['title']) ?></td>
                        <td><?= h($film['year']) ?></td>
                        <td><?= h($film['genre']) ?></td>
                        <td>
                            <?= h($film['rating']) ?>
                            <?php if ((float) $film['rating'] < 5.0): ?>
                                <strong class="low-rating">Niska ocjena</strong>
                            <?php endif; ?>
                        </td>
                        <td>
                            <form method="post" class="inline-form">
                                <input type="hidden" name="csrf" value="<?= h(csrf_token()) ?>">
                                <input type="hidden" name="action" value="remove">
                                <input type="hidden" name="film_id" value="<?= h($film['id']) ?>">
                                <button type="submit">Ukloni</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <div class="cart-actions">
            <form method="post">
                <input type="hidden" name="csrf" value="<?= h(csrf_token()) ?>">
                <input type="hidden" name="action" value="clear">
                <button type="submit" class="secondary">Isprazni košaricu</button>
            </form>
            <form method="post">
                <input type="hidden" name="csrf" value="<?= h(csrf_token()) ?>">
                <input type="hidden" name="action" value="checkout">
                <button type="submit">Završi posudbu</button>
            </form>
        </div>
    <?php else: ?>
        <p>Košarica je prazna. <a href="index.php">Odaberite filmove za posudbu.</a></p>
    <?php endif; ?>
</section>
<?php require __DIR__ . '/includes/footer.php'; ?>
