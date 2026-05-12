<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/functions.php';

$genres = [];
foreach (db()->query('SELECT genre FROM films')->fetchAll(PDO::FETCH_COLUMN) as $genreString) {
    foreach (explode(',', $genreString) as $genre) {
        $genre = trim($genre);
        if ($genre !== '') {
            $genres[] = $genre;
        }
    }
}

$genreCounts = array_count_values($genres);
arsort($genreCounts);
$topGenres = array_slice($genreCounts, 0, 7, true);

$title = 'Grafikon filmova';
require __DIR__ . '/includes/header.php';
?>

<section class="uvod">
    <h2>Opis grafikona</h2>
    <p>
        Ovaj grafikon prikazuje koliko se puta pojedini žanr pojavljuje među filmovima u bazi podataka.
    </p>
</section>

<section class="grafikon">
    <h2>Broj filmova po žanru</h2>

    <?php if (empty($topGenres)): ?>
        <p>Trenutno nema podataka za prikaz grafikona.</p>
    <?php else: ?>
        <div class="chart">
            <?php foreach ($topGenres as $genre => $count): ?>
                <div class="bar" style="--value: <?= h($count) ?>">
                    <span><?= h($genre) ?></span>
                    <div class="value"><?= h($count) ?></div>
                </div>
            <?php endforeach; ?>
        </div>

        <div class="legend">
            <?php foreach (array_keys($topGenres) as $index => $genre): ?>
                <div><span class="c<?= $index + 1 ?>"></span><?= h($genre) ?> (<?= h($topGenres[$genre]) ?>)</div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>