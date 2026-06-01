<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/functions.php';
require_login();
check_csrf();

$photoId = (int) ($_POST['photo_id'] ?? 0);
$rating = (int) ($_POST['rating'] ?? 0);
$comment = trim((string) ($_POST['comment'] ?? ''));

if ($photoId <= 0 || $rating < 1 || $rating > 5) {
    flash('Ocjena mora biti između 1 i 5.', 'error');
    header('Location: gallery.php');
    exit;
}

if (!photo_exists($photoId)) {
    flash('Odabrana slika ne postoji.', 'error');
    header('Location: gallery.php');
    exit;
}

if (text_length($comment) > 255) {
    flash('Komentar može imati najviše 255 znakova.', 'error');
    header('Location: gallery.php#photo-' . $photoId);
    exit;
}

$stmt = db()->prepare(
    'INSERT INTO photo_ratings (user_id, photo_id, rating, comment)
     VALUES (?, ?, ?, ?)
     ON DUPLICATE KEY UPDATE rating = VALUES(rating), comment = VALUES(comment), rated_at = CURRENT_TIMESTAMP'
);
$stmt->execute([current_user()['id'], $photoId, $rating, $comment ?: null]);
flash('Ocjena slike je spremljena.');
header('Location: gallery.php#photo-' . $photoId);
exit;
