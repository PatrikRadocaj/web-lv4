<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/functions.php';
require_admin();

$errors = [];
$editFilm = null;

if (isset($_GET['edit'])) {
    $stmt = db()->prepare('SELECT * FROM films WHERE id = ?');
    $stmt->execute([(int) $_GET['edit']]);
    $editFilm = $stmt->fetch() ?: null;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    check_csrf();
    $action = $_POST['action'] ?? '';

    if ($action === 'delete') {
        $stmt = db()->prepare('DELETE FROM films WHERE id = ?');
        $stmt->execute([(int) ($_POST['id'] ?? 0)]);
        flash('Film je obrisan.');
        header('Location: admin_films.php');
        exit;
    }

    $data = [
        'title' => trim($_POST['title'] ?? ''),
        'genre' => trim($_POST['genre'] ?? ''),
        'year' => (int) ($_POST['year'] ?? 0),
        'duration' => (int) ($_POST['duration'] ?? 0),
        'rating' => (float) ($_POST['rating'] ?? 0),
        'director' => trim($_POST['director'] ?? ''),
        'country' => trim($_POST['country'] ?? ''),
    ];

    if ($data['title'] === '' || $data['genre'] === '' || $data['director'] === '' || $data['country'] === '') {
        $errors[] = 'Sva tekstualna polja su obavezna.';
    }
    if ($data['year'] < 1888 || $data['year'] > 2035) {
        $errors[] = 'Godina mora biti u ispravnom rasponu.';
    }
    if ($data['duration'] < 1 || $data['duration'] > 600) {
        $errors[] = 'Trajanje mora biti izmedju 1 i 600 minuta.';
    }
    if ($data['rating'] < 0 || $data['rating'] > 10) {
        $errors[] = 'Ocjena mora biti izmedju 0 i 10.';
    }

    if (!$errors && $action === 'save') {
        if (!empty($_POST['id'])) {
            $stmt = db()->prepare('UPDATE films SET title=?, genre=?, year=?, duration=?, rating=?, director=?, country=? WHERE id=?');
            $stmt->execute([$data['title'], $data['genre'], $data['year'], $data['duration'], $data['rating'], $data['director'], $data['country'], (int) $_POST['id']]);
            flash('Film je azuriran.');
        } else {
            $stmt = db()->prepare('INSERT INTO films (title, genre, year, duration, rating, director, country) VALUES (?, ?, ?, ?, ?, ?, ?)');
            $stmt->execute([$data['title'], $data['genre'], $data['year'], $data['duration'], $data['rating'], $data['director'], $data['country']]);
            flash('Film je dodan.');
        }
        header('Location: admin_films.php');
        exit;
    }
}

$films = db()->query('SELECT * FROM films ORDER BY created_at DESC, title ASC')->fetchAll();
$title = 'Admin upravljanje filmovima';
require __DIR__ . '/includes/header.php';
?>
<?php foreach ($errors as $error): ?><p class="error"><?= h($error) ?></p><?php endforeach; ?>

<section class="form-panel">
    <h2><?= $editFilm ? 'Uredi film' : 'Dodaj film' ?></h2>
    <form method="post" class="form-grid">
        <input type="hidden" name="csrf" value="<?= h(csrf_token()) ?>">
        <input type="hidden" name="action" value="save">
        <input type="hidden" name="id" value="<?= h($editFilm['id'] ?? '') ?>">
        <label>Naslov <input name="title" required value="<?= h($editFilm['title'] ?? '') ?>"></label>
        <label>Zanr <input name="genre" required value="<?= h($editFilm['genre'] ?? '') ?>"></label>
        <label>Godina <input type="number" name="year" min="1888" max="2035" required value="<?= h($editFilm['year'] ?? '') ?>"></label>
        <label>Trajanje <input type="number" name="duration" min="1" max="600" required value="<?= h($editFilm['duration'] ?? '') ?>"></label>
        <label>Ocjena <input type="number" name="rating" min="0" max="10" step="0.1" required value="<?= h($editFilm['rating'] ?? '') ?>"></label>
        <label>Reziser <input name="director" required value="<?= h($editFilm['director'] ?? '') ?>"></label>
        <label>Zemlja <input name="country" required value="<?= h($editFilm['country'] ?? '') ?>"></label>
        <button type="submit">Spremi</button>
    </form>
</section>

<section class="tablica-clanak">
    <h2>Filmovi u bazi</h2>
    <div class="table-wrapper">
        <table>
            <thead><tr><th>Naslov</th><th>Godina</th><th>Zanr</th><th>Ocjena</th><th>Akcije</th></tr></thead>
            <tbody>
            <?php foreach ($films as $film): ?>
                <tr>
                    <td><?= h($film['title']) ?></td>
                    <td><?= h($film['year']) ?></td>
                    <td><?= h($film['genre']) ?></td>
                    <td><?= h($film['rating']) ?></td>
                    <td class="admin-actions">
                        <a class="akcija-gumb" href="admin_films.php?edit=<?= h($film['id']) ?>">Uredi</a>
                        <form method="post" class="inline-form">
                            <input type="hidden" name="csrf" value="<?= h(csrf_token()) ?>">
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="id" value="<?= h($film['id']) ?>">
                            <button type="submit">Obrisi</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</section>
<?php require __DIR__ . '/includes/footer.php'; ?>
