<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/functions.php';
require_login();

$user = current_user();
$warning = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'add') {
    check_csrf();
    $filmId = (int) ($_POST['film_id'] ?? 0);
    $stmt = db()->prepare('SELECT * FROM films WHERE id = ?');
    $stmt->execute([$filmId]);
    $film = $stmt->fetch();

    if ($film) {
        if ((float) $film['rating'] < 5.0) {
            $warning = 'Ovaj film ima nisku ocjenu - jeste li sigurni da ga zelite dodati? Film je ipak dodan u osobnu videoteku.';
        }
        $insert = db()->prepare('INSERT IGNORE INTO desired_films (user_id, film_id) VALUES (?, ?)');
        $insert->execute([$user['id'], $filmId]);
        if (!$warning) {
            flash('Film je dodan u osobnu videoteku.');
            header('Location: index.php?' . http_build_query($_GET));
            exit;
        }
    }
}

$filters = [
    'genre' => trim($_GET['genre'] ?? ''),
    'country' => trim($_GET['country'] ?? ''),
    'year_from' => trim($_GET['year_from'] ?? ''),
    'rating_min' => trim($_GET['rating_min'] ?? ''),
    'sort' => trim($_GET['sort'] ?? 'rating'),
    'direction' => trim($_GET['direction'] ?? 'desc'),
];
$films = fetch_films($filters);
$genres = fetch_genres();

$title = 'LV4 Virtualna videoteka';
require __DIR__ . '/includes/header.php';
?>
<?php if ($warning): ?><p class="warning"><?= h($warning) ?></p><?php endif; ?>

<section class="uvod" aria-labelledby="naslov-uvod">
    <h2 id="naslov-uvod">O stranici</h2>
    <p>
        Ova stranica prikazuje odabrane filmove iz baze podataka. Korisnici mogu filtrirati, sortirati i dodavati
        filmove u osobnu videoteka, a zatim pregledati vlastitu kolekciju.
    </p>
</section>

<section class="glavni-sadrzaj" aria-labelledby="naslov-tablica">
    <article class="tablica-clanak">
        <section class="form-panel">
            <h2>Filtriranje i sortiranje filmova</h2>
            <form method="get" class="form-grid">
                <label>Zanr
                    <select name="genre">
                        <option value="">Svi zanrovi</option>
                        <?php foreach ($genres as $genre): ?>
                            <option value="<?= h($genre) ?>" <?= $filters['genre'] === $genre ? 'selected' : '' ?>><?= h($genre) ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <label>Zemlja
                    <input name="country" value="<?= h($filters['country']) ?>" placeholder="npr. USA">
                </label>
                <label>Godina od
                    <input type="number" name="year_from" min="1900" max="2035" value="<?= h($filters['year_from']) ?>">
                </label>
                <label>Minimalna ocjena
                    <input type="number" name="rating_min" min="0" max="10" step="0.1" value="<?= h($filters['rating_min']) ?>">
                </label>
                <label>Sortiraj po
                    <select name="sort">
                        <?php foreach (['rating' => 'Ocjena', 'title' => 'Naslov', 'year' => 'Godina', 'duration' => 'Trajanje', 'country' => 'Zemlja'] as $key => $label): ?>
                            <option value="<?= $key ?>" <?= $filters['sort'] === $key ? 'selected' : '' ?>><?= $label ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <label>Smjer
                    <select name="direction">
                        <option value="desc" <?= $filters['direction'] === 'desc' ? 'selected' : '' ?>>Silazno</option>
                        <option value="asc" <?= $filters['direction'] === 'asc' ? 'selected' : '' ?>>Uzlazno</option>
                    </select>
                </label>
                <button type="submit">Primijeni</button>
            </form>
        </section>

        <section class="tablica-clanak">
            <h2 id="naslov-tablica">Filmovi iz baze</h2>
            <p>Pronadjeno filmova: <?= count($films) ?></p>
            <div class="table-wrapper">
                <table>
                    <thead>
                        <tr>
                            <th>Naslov</th><th>Zanr</th><th>Godina</th><th>Trajanje</th><th>Zemlja</th><th>Ocjena</th><th>Akcija</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($films as $film): ?>
                            <tr>
                                <td><?= h($film['title']) ?></td>
                                <td><?= h($film['genre']) ?></td>
                                <td><?= h($film['year']) ?></td>
                                <td><?= h($film['duration']) ?> min</td>
                                <td><?= h($film['country']) ?></td>
                                <td><?= h($film['rating']) ?></td>
                                <td>
                                    <?php if (user_has_film((int) $user['id'], (int) $film['id'])): ?>
                                        Vec dodano
                                    <?php else: ?>
                                        <form method="post" class="inline-form">
                                            <input type="hidden" name="csrf" value="<?= h(csrf_token()) ?>">
                                            <input type="hidden" name="action" value="add">
                                            <input type="hidden" name="film_id" value="<?= h($film['id']) ?>">
                                            <button type="submit">Dodaj</button>
                                        </form>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if (!$films): ?>
                            <tr><td colspan="7">Nema rezultata za odabrane filtere.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </section>
    </article>

    <aside class="sidebar" aria-labelledby="naslov-aside">
        <h2 id="naslov-aside">Filmski kutak</h2>
        <picture>
            <source media="(max-width: 768px)" srcset="public/images/photo1.jpg">
            <img src="public/images/photo1.jpg" alt="Kino projekcija">
        </picture>
        <p>
            Filmovi su jedan od najpopularnijih oblika zabave, ali i važan dio moderne kulture.
        </p>
        <p>
            Na ovoj stranici možeš pregledavati filmove, mijenjati filtre i dodavati naslove u svoju kolekciju.
        </p>
        <p>
            Posebno je zanimljivo promatrati koji se žanrovi najčešće pojavljuju među odabranim naslovima.
        </p>
        <a class="akcija-gumb" href="grafikon.php">Pogledaj grafički prikaz</a>
    </aside>
</section>

<section aria-labelledby="naslov-info">
    <h2 id="naslov-info">O filmskoj bazi podataka</h2>

    <article>
        <h3>Što prikazuje tablica?</h3>
        <p>
            Tablica prikazuje odabrane filmove iz baze podataka. Za svaki film navedeni su osnovni podaci poput naslova,
            godine izlaska, žanra, trajanja, zemlje i ocjene.
        </p>
    </article>

    <article>
        <h3>Zašto je ova tema odabrana?</h3>
        <p>
            Tema filmova prikladna je jer kombinira tekstualne, tablične i vizualne podatke. Filmovi su pregledni i lako se mogu 
            usporediti, što je korisno za daljnju analizu.
        </p>
    </article>

    <article>
        <h3>Što se može analizirati?</h3>
        <p>
            Na temelju prikazanih podataka može se analizirati zastupljenost pojedinih žanrova, usporediti trajanje filmova te 
            pratiti ocjene filmova u kolekciji.
        </p>
    </article>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>
