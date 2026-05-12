<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/functions.php';
require_login();
check_csrf();

$photoId = (int) ($_POST['photo_id'] ?? 0);
$rating = (int) ($_POST['rating'] ?? 0);
$comment = trim($_POST['comment'] ?? '');

if ($photoId <= 0 || $rating < 1 || $rating > 5) {
    flash('Ocjena mora biti izmedju 1 i 5.');
    header('Location: gallery.php');
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
