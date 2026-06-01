<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/functions.php';
require_login();

$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'upload') {
    check_csrf();
    $titleInput = trim((string) ($_POST['title'] ?? ''));
    $file = $_FILES['photo'] ?? null;
    $allowed = ['image/jpeg' => 'jpg', 'image/png' => 'png'];

    if (text_length($titleInput) > 160) {
        $errors[] = 'Naziv slike može imati najviše 160 znakova.';
    }
    if (!$file || $file['error'] !== UPLOAD_ERR_OK) {
        $errors[] = 'Slika nije ispravno učitana.';
    } elseif (($file['size'] ?? 0) > 5 * 1024 * 1024) {
        $errors[] = 'Slika ne smije biti veća od 5MB.';
    } else {
        $info = finfo_open(FILEINFO_MIME_TYPE);
        $mime = finfo_file($info, $file['tmp_name']);
        finfo_close($info);
        if (!isset($allowed[$mime])) {
            $errors[] = 'Dozvoljene su samo JPEG i PNG slike.';
        }
    }

    if (!$errors) {
        $uploadDir = __DIR__ . '/uploads';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0775, true);
        }
        $extension = $allowed[$mime];
        $filename = bin2hex(random_bytes(12)) . '.' . $extension;
        $target = $uploadDir . '/' . $filename;
        if (move_uploaded_file($file['tmp_name'], $target)) {
            try {
                $stmt = db()->prepare('INSERT INTO photos (title, path, source) VALUES (?, ?, "upload")');
                $stmt->execute([$titleInput ?: pathinfo((string) $file['name'], PATHINFO_FILENAME), 'uploads/' . $filename]);
                $photoId = (int) db()->lastInsertId();
                flash('Slika je dodana u galeriju.');
                header('Location: gallery.php#photo-' . $photoId);
                exit;
            } catch (PDOException) {
                unlink($target);
                $errors[] = 'Spremanje podataka slike nije uspjelo.';
            }
        }
        if (!$errors) {
            $errors[] = 'Spremanje slike nije uspjelo.';
        }
    }
}

$photos = db()->query(
    'SELECT p.*,
            ROUND(AVG(r.rating), 2) AS avg_rating,
            COUNT(r.id) AS rating_count
     FROM photos p
     LEFT JOIN photo_ratings r ON r.photo_id = p.id
     GROUP BY p.id
     ORDER BY p.created_at DESC, p.title ASC'
)->fetchAll();

$userRatingsStmt = db()->prepare('SELECT photo_id, rating, comment FROM photo_ratings WHERE user_id = ?');
$userRatingsStmt->execute([(int) current_user()['id']]);
$userRatings = [];
foreach ($userRatingsStmt->fetchAll() as $row) {
    $userRatings[(int) $row['photo_id']] = $row;
}

$comments = [];
$commentsStmt = db()->query(
    'SELECT r.photo_id, r.comment, u.username
     FROM photo_ratings r
     JOIN users u ON u.id = r.user_id
     WHERE r.comment IS NOT NULL AND r.comment <> ""
     ORDER BY r.rated_at DESC'
);
foreach ($commentsStmt->fetchAll() as $row) {
    $comments[(int) $row['photo_id']][] = $row;
}

$title = 'Ocjenjivanje fotografija';
require __DIR__ . '/includes/header.php';
?>

<section class="uvod" aria-labelledby="naslov-uvod">
    <h2 id="naslov-uvod">Galerija fotografija</h2>
    <p>
        Ovdje možete pregledavati i ocjenjivati slike iz galerije te dodati vlastite JPEG ili PNG fotografije.
    </p>
</section>

<?php foreach ($errors as $error): ?><p class="error"><?= h($error) ?></p><?php endforeach; ?>

<section class="form-panel">
    <h2>Dodaj novu sliku</h2>
    <form method="post" enctype="multipart/form-data" class="form-grid">
        <input type="hidden" name="csrf" value="<?= h(csrf_token()) ?>">
        <input type="hidden" name="action" value="upload">
        <label>Naziv slike <input name="title" maxlength="160" placeholder="Naziv"></label>
        <label>JPEG/PNG do 5MB <input type="file" name="photo" accept="image/jpeg,image/png" required></label>
        <button type="submit">Učitaj sliku</button>
    </form>
</section>

<section class="galerija">
    <h2>Galerija s ocjenama</h2>
    <?php foreach ($photos as $photo): ?>
        <?php
            $mine = $userRatings[(int) $photo['id']] ?? null;
            $rawPath = (string) $photo['path'];
            $safePath = str_replace(["\0", '\\'], ['', '/'], $rawPath);
            if (str_contains($safePath, '..')) {
                continue;
            }
        ?>
        <figure class="galerija_slika" id="photo-<?= h($photo['id']) ?>">
            <a href="#slika<?= h($photo['id']) ?>">
                <img src="<?= h($safePath) ?>" alt="<?= h($photo['title']) ?>" loading="lazy">
            </a>
            <figcaption><?= h($photo['title']) ?></figcaption>
            <p class="rating-summary">
                Prosjek: <?= $photo['avg_rating'] !== null ? h($photo['avg_rating']) : 'nema' ?>
                (<?= h($photo['rating_count']) ?>)
            </p>
            <form method="post" action="rate_photo.php">
                <input type="hidden" name="csrf" value="<?= h(csrf_token()) ?>">
                <input type="hidden" name="photo_id" value="<?= h($photo['id']) ?>">
                <div class="stars" aria-label="Ocjena slike">
                    <?php for ($i = 1; $i <= 5; $i++): ?>
                        <button class="<?= $mine && (int) $mine['rating'] === $i ? 'active' : '' ?>" type="submit" name="rating" value="<?= $i ?>"><?= $i ?></button>
                    <?php endfor; ?>
                </div>
                <label class="stacked">Komentar
                    <input name="comment" maxlength="255" value="<?= h($mine['comment'] ?? '') ?>">
                </label>
                <?php if ($mine): ?>
                    <button class="save-comment" type="submit" name="rating" value="<?= h($mine['rating']) ?>">Spremi komentar</button>
                <?php else: ?>
                    <small>Ocijenite sliku kako biste spremili komentar.</small>
                <?php endif; ?>
            </form>
            <?php if (!empty($comments[(int) $photo['id']])): ?>
                <section class="photo-comments" aria-label="Komentari">
                    <h3>Komentari</h3>
                    <?php foreach ($comments[(int) $photo['id']] as $comment): ?>
                        <p><strong><?= h($comment['username']) ?>:</strong> <?= h($comment['comment']) ?></p>
                    <?php endforeach; ?>
                </section>
            <?php endif; ?>
        </figure>
        <div id="slika<?= h($photo['id']) ?>" class="lightbox">
            <a href="#" class="zatvori">x</a>
            <img src="<?= h($safePath) ?>" alt="<?= h($photo['title']) ?>">
        </div>
    <?php endforeach; ?>
</section>
<?php require __DIR__ . '/includes/footer.php'; ?>
