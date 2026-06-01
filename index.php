<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/functions.php';
require_login();

$user = current_user();
$queryString = http_build_query($_GET);
$indexUrl = 'index.php' . ($queryString !== '' ? '?' . $queryString : '');
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    if (in_array($action, ['remove', 'clear', 'checkout', 'add_to_cart'], true)) {
        check_csrf();
    }

    if ($action === 'remove') {
        $filmId = (int) ($_POST['film_id'] ?? 0);
        if (cart_has_film($filmId)) {
            remove_film_from_cart($filmId);
            flash('Film je uklonjen iz košarice.');
        } else {
            flash('Film nije pronađen u košarici.', 'error');
        }
        header('Location: ' . $indexUrl . '#cart');
        exit;
    }

    if ($action === 'clear') {
        $_SESSION['film_cart'] = [];
        flash('Košarica je ispražnjena.');
        header('Location: ' . $indexUrl . '#cart');
        exit;
    }

    if ($action === 'checkout') {
        $filmsInCart = fetch_cart_films();
        if (!$filmsInCart) {
            flash('Košarica je prazna.', 'error');
            header('Location: ' . $indexUrl . '#cart');
            exit;
        }

        $pdo = db();
        $pdo->beginTransaction();
        try {
            $insert = $pdo->prepare('INSERT IGNORE INTO desired_films (user_id, film_id) VALUES (?, ?)');
            foreach ($filmsInCart as $f) {
                $insert->execute([(int) $user['id'], (int) $f['id']]);
            }
            $pdo->commit();
            $_SESSION['film_cart'] = [];
            flash('Posudba je završena. Filmovi su dodani u osobnu videoteku.');
            header('Location: my_videoteka.php#videoteka');
            exit;
        } catch (Throwable) {
            $pdo->rollBack();
            flash('Posudbu nije moguće završiti. Pokušajte ponovno.', 'error');
            header('Location: ' . $indexUrl . '#cart');
            exit;
        }
    }

    if ($action === 'add_to_cart') {
        $filmId = (int) ($_POST['film_id'] ?? 0);
        $stmt = db()->prepare('SELECT * FROM films WHERE id = ?');
        $stmt->execute([$filmId]);
        $film = $stmt->fetch();

        if ($film) {
            $warning = '';
            if (user_has_film((int) $user['id'], $filmId)) {
                flash('Film je već u osobnoj videoteci.');
                header('Location: ' . $indexUrl . '#film-' . $filmId);
                exit;
            }

            if (cart_has_film($filmId)) {
                flash('Film je već u košarici.');
                header('Location: ' . $indexUrl . '#film-' . $filmId);
                exit;
            }

            add_film_to_cart($filmId);
            if ((float) $film['rating'] < 5.0) {
                $warning = 'Film ima nisku ocjenu, ali je dodan u košaricu. Provjerite ga prije završetka posudbe.';
            }
            flash($warning ?: 'Film je dodan u košaricu.', $warning ? 'warning' : 'notice');
            header('Location: ' . $indexUrl . '#film-' . $filmId);
            exit;
        }

        flash('Odabrani film ne postoji.', 'error');
        header('Location: ' . $indexUrl);
        exit;
    }
}

$filters = normalize_film_filters($_GET);
$films = fetch_films($filters);
$genres = fetch_genres();

$cartFilms = fetch_cart_films();

$title = 'LV4 Virtualna videoteka';
require __DIR__ . '/includes/header.php';
?>
<section class="uvod" aria-labelledby="naslov-uvod">
    <h2 id="naslov-uvod">O stranici</h2>
    <p>
        Ova stranica prikazuje odabrane filmove iz baze podataka. Korisnici mogu filtrirati, sortirati i dodavati
        filmove u košaricu. Filmovi se spremaju u osobnu videoteku tek nakon završetka posudbe.
    </p>
</section>

<section class="glavni-sadrzaj" aria-labelledby="naslov-tablica">
    <article class="tablica-clanak">
        <section class="form-panel">
            <h2>Filtriranje i sortiranje filmova</h2>
            <form method="get" class="form-grid">
                <label>Žanr
                    <select name="genre">
                        <option value="">Svi žanrovi</option>
                        <?php foreach ($genres as $genre): ?>
                            <option value="<?= h($genre) ?>" <?= $filters['genre'] === $genre ? 'selected' : '' ?>><?= h($genre) ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <label>Zemlja
                    <input name="country" value="<?= h($filters['country']) ?>" placeholder="npr. USA">
                </label>
                <label>Godina od
                    <input type="number" name="year_from" min="1888" max="2035" value="<?= h($filters['year_from']) ?>">
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

        <div class="films-and-cart">
        <section class="tablica-clanak">
            <h2 id="naslov-tablica">Filmovi iz baze</h2>
            <p>Pronađeno filmova: <?= count($films) ?></p>
            <div class="table-wrapper">
                <table>
                    <thead>
                        <tr>
                            <th>Naslov</th><th>Žanr</th><th>Godina</th><th>Trajanje</th><th>Zemlja</th><th>Ocjena</th><th>Akcija</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($films as $film): ?>
                            <tr id="film-<?= h($film['id']) ?>">
                                <td><?= h($film['title']) ?></td>
                                <td><?= h($film['genre']) ?></td>
                                <td><?= h($film['year']) ?></td>
                                <td><?= h($film['duration']) ?> min</td>
                                <td><?= h($film['country']) ?></td>
                                <td><?= h($film['rating']) ?></td>
                                <td>
                                    <?php if (user_has_film((int) $user['id'], (int) $film['id'])): ?>
                                        Već dodano
                                    <?php elseif (cart_has_film((int) $film['id'])): ?>
                                        U košarici
                                    <?php else: ?>
                                        <form method="post" class="inline-form">
                                            <input type="hidden" name="csrf" value="<?= h(csrf_token()) ?>">
                                            <input type="hidden" name="action" value="add_to_cart">
                                            <input type="hidden" name="film_id" value="<?= h($film['id']) ?>">
                                            <button type="submit">Dodaj u košaricu</button>
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

        
        </div>
    </article>

    <aside class="sidebar" aria-labelledby="naslov-aside">
        <h2 id="naslov-aside">Filmski kutak</h2>
        <p>
            Filmovi su jedan od najpopularnijih oblika zabave, ali i važan dio moderne kulture.
        </p>
        <p>
            Na ovoj stranici možeš pregledavati filmove, mijenjati filtre i dodavati naslove u svoju kolekciju.
        </p>
        <p>
            Posebno je zanimljivo promatrati koji se žanrovi najčešće pojavljuju među odabranim naslovima.
        </p>

        <section class="cart-panel" id="cart">
            <h2>Košarica za posudbu filmova</h2>
            <p>Filmovi će biti dodani u osobnu videoteku tek nakon završetka posudbe.</p>

            <?php if ($cartFilms): ?>
                <div class="table-wrapper cart-table-wrapper">
                    <table>
                        <thead>
                            <tr><th>Naslov</th><th>Ocjena</th><th>Akcija</th></tr>
                        </thead>
                        <tbody>
                        <?php foreach ($cartFilms as $film): ?>
                            <tr id="cart-film-<?= h($film['id']) ?>">
                                <td><?= h($film['title']) ?></td>
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
