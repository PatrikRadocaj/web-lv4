<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/functions.php';
require_login();

$user = current_user();
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'remove') {
    check_csrf();
    $stmt = db()->prepare('DELETE FROM desired_films WHERE user_id = ? AND film_id = ?');
    $stmt->execute([$user['id'], (int) ($_POST['film_id'] ?? 0)]);
    flash('Film je uklonjen iz osobne videoteke.');
    header('Location: my_videoteka.php');
    exit;
}

$stmt = db()->prepare(
    'SELECT f.* FROM desired_films d JOIN films f ON f.id = d.film_id WHERE d.user_id = ? ORDER BY d.created_at DESC'
);
$stmt->execute([$user['id']]);
$films = $stmt->fetchAll();

$title = 'Moja videoteka';
require __DIR__ . '/includes/header.php';
?>
<section class="tablica-clanak">
    <h2>Trajno spremljeni filmovi</h2>
    <p>Ukupno odabranih filmova: <?= count($films) ?></p>
    <div class="table-wrapper">
        <table>
            <thead><tr><th>Naslov</th><th>Godina</th><th>Zanr</th><th>Ocjena</th><th>Ukloni</th></tr></thead>
            <tbody>
            <?php foreach ($films as $film): ?>
                <tr>
                    <td><?= h($film['title']) ?></td>
                    <td><?= h($film['year']) ?></td>
                    <td><?= h($film['genre']) ?></td>
                    <td><?= h($film['rating']) ?></td>
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
            <?php if (!$films): ?>
                <tr><td colspan="5">Osobna videoteka je prazna.</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</section>
<?php require __DIR__ . '/includes/footer.php'; ?>
