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
    if (!$editFilm) {
        flash('Odabrani film ne postoji.', 'error');
        header('Location: admin_films.php#films-table');
        exit;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    check_csrf();
    $action = $_POST['action'] ?? '';

    if ($action === 'delete') {
        $filmId = (int) ($_POST['id'] ?? 0);
        $stmt = db()->prepare('DELETE FROM films WHERE id = ?');
        $stmt->execute([$filmId]);
        flash($stmt->rowCount() ? 'Film je obrisan.' : 'Odabrani film ne postoji.', $stmt->rowCount() ? 'notice' : 'error');
        header('Location: admin_films.php#films-table');
        exit;
    }

    $data = [
        'title' => trim((string) ($_POST['title'] ?? '')),
        'genre' => trim((string) ($_POST['genre'] ?? '')),
        'year' => (int) ($_POST['year'] ?? 0),
        'duration' => (int) ($_POST['duration'] ?? 0),
        'rating' => (float) ($_POST['rating'] ?? 0),
        'director' => trim((string) ($_POST['director'] ?? '')),
        'country' => trim((string) ($_POST['country'] ?? '')),
    ];

    if ($action !== 'save') {
        $errors[] = 'Nepoznata akcija.';
    }
    if ($data['title'] === '' || $data['genre'] === '' || $data['director'] === '' || $data['country'] === '') {
        $errors[] = 'Sva tekstualna polja su obavezna.';
    }
    if (text_length($data['title']) > 160 || text_length($data['director']) > 160) {
        $errors[] = 'Naslov i redatelj mogu imati najviše 160 znakova.';
    }
    if (text_length($data['genre']) > 120 || text_length($data['country']) > 120) {
        $errors[] = 'Žanr i zemlja mogu imati najviše 120 znakova.';
    }
    if ($data['year'] < 1888 || $data['year'] > 2035) {
        $errors[] = 'Godina mora biti u ispravnom rasponu.';
    }
    if ($data['duration'] < 1 || $data['duration'] > 600) {
        $errors[] = 'Trajanje mora biti između 1 i 600 minuta.';
    }
    if ($data['rating'] < 0 || $data['rating'] > 10) {
        $errors[] = 'Ocjena mora biti između 0 i 10.';
    }

    if (!$errors && $action === 'save') {
        try {
            if (!empty($_POST['id'])) {
                $stmt = db()->prepare('UPDATE films SET title=?, genre=?, year=?, duration=?, rating=?, director=?, country=? WHERE id=?');
                $stmt->execute([$data['title'], $data['genre'], $data['year'], $data['duration'], $data['rating'], $data['director'], $data['country'], (int) $_POST['id']]);
                flash($stmt->rowCount() ? 'Film je ažuriran.' : 'Nema promjena za spremanje.');
            } else {
                $stmt = db()->prepare('INSERT INTO films (title, genre, year, duration, rating, director, country) VALUES (?, ?, ?, ?, ?, ?, ?)');
                $stmt->execute([$data['title'], $data['genre'], $data['year'], $data['duration'], $data['rating'], $data['director'], $data['country']]);
                flash('Film je dodan.');
            }
            header('Location: admin_films.php#films-table');
            exit;
        } catch (PDOException) {
            $errors[] = 'Film s istim naslovom i godinom već postoji.';
        }
    }
    $editFilm = $data + ['id' => (int) ($_POST['id'] ?? 0)];
}

$films = db()->query('SELECT * FROM films ORDER BY created_at DESC, title ASC')->fetchAll();
$title = 'Admin upravljanje filmovima';
require __DIR__ . '/includes/header.php';
?>
<?php foreach ($errors as $error): ?><p class="error"><?= h($error) ?></p><?php endforeach; ?>

<section class="form-panel" id="film-form">
    <h2><?= !empty($editFilm['id']) ? 'Uredi film' : 'Dodaj film' ?></h2>
    <form method="post" class="form-grid">
        <input type="hidden" name="csrf" value="<?= h(csrf_token()) ?>">
        <input type="hidden" name="action" value="save">
        <input type="hidden" name="id" value="<?= h($editFilm['id'] ?? '') ?>">
        <label>Naslov <input name="title" maxlength="160" required value="<?= h($editFilm['title'] ?? '') ?>"></label>
        <label>Žanr <input name="genre" maxlength="120" required value="<?= h($editFilm['genre'] ?? '') ?>"></label>
        <label>Godina <input type="number" name="year" min="1888" max="2035" required value="<?= h($editFilm['year'] ?? '') ?>"></label>
        <label>Trajanje <input type="number" name="duration" min="1" max="600" required value="<?= h($editFilm['duration'] ?? '') ?>"></label>
        <label>Ocjena <input type="number" name="rating" min="0" max="10" step="0.1" required value="<?= h($editFilm['rating'] ?? '') ?>"></label>
        <label>Redatelj <input name="director" maxlength="160" required value="<?= h($editFilm['director'] ?? '') ?>"></label>
        <label>Zemlja <input name="country" maxlength="120" required value="<?= h($editFilm['country'] ?? '') ?>"></label>
        <button type="submit">Spremi</button>
    </form>
</section>

<section class="tablica-clanak" id="films-table">
    <h2>Filmovi u bazi</h2>
    <div class="table-wrapper">
        <table>
            <thead><tr><th>Naslov</th><th>Godina</th><th>Žanr</th><th>Ocjena</th><th>Akcije</th></tr></thead>
            <tbody>
            <?php foreach ($films as $film): ?>
                <tr>
                    <td><?= h($film['title']) ?></td>
                    <td><?= h($film['year']) ?></td>
                    <td><?= h($film['genre']) ?></td>
                    <td><?= h($film['rating']) ?></td>
                    <td class="admin-actions">
                        <a class="akcija-gumb" href="admin_films.php?edit=<?= h($film['id']) ?>#film-form">Uredi</a>
                        <form method="post" class="inline-form">
                            <input type="hidden" name="csrf" value="<?= h(csrf_token()) ?>">
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="id" value="<?= h($film['id']) ?>">
                            <button type="submit">Obriši</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</section>
<?php require __DIR__ . '/includes/footer.php'; ?>
