<?php
declare(strict_types=1);

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/auth.php';

function fetch_films(array $filters): array
{
    $sql = 'SELECT * FROM films WHERE 1=1';
    $params = [];

    if (($filters['genre'] ?? '') !== '') {
        $sql .= ' AND genre LIKE :genre';
        $params['genre'] = '%' . $filters['genre'] . '%';
    }
    if (($filters['country'] ?? '') !== '') {
        $sql .= ' AND country LIKE :country';
        $params['country'] = '%' . $filters['country'] . '%';
    }
    if (($filters['year_from'] ?? '') !== '') {
        $sql .= ' AND year >= :year_from';
        $params['year_from'] = (int) $filters['year_from'];
    }
    if (($filters['rating_min'] ?? '') !== '') {
        $sql .= ' AND rating >= :rating_min';
        $params['rating_min'] = (float) $filters['rating_min'];
    }

    $allowedSorts = ['title', 'year', 'genre', 'country', 'rating', 'duration'];
    $sort = in_array($filters['sort'] ?? '', $allowedSorts, true) ? $filters['sort'] : 'rating';
    $direction = ($filters['direction'] ?? 'desc') === 'asc' ? 'ASC' : 'DESC';
    $sql .= " ORDER BY {$sort} {$direction}, title ASC";

    $stmt = db()->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

function normalize_film_filters(array $input): array
{
    $allowedSorts = ['rating', 'title', 'year', 'duration', 'country', 'genre'];
    $sort = in_array(($input['sort'] ?? ''), $allowedSorts, true) ? (string) $input['sort'] : 'rating';
    $direction = (($input['direction'] ?? 'desc') === 'asc') ? 'asc' : 'desc';

    $yearFrom = trim((string) ($input['year_from'] ?? ''));
    if ($yearFrom !== '' && (!ctype_digit($yearFrom) || (int) $yearFrom < 1888 || (int) $yearFrom > 2035)) {
        $yearFrom = '';
    }

    $ratingMin = trim((string) ($input['rating_min'] ?? ''));
    if ($ratingMin !== '' && (!is_numeric($ratingMin) || (float) $ratingMin < 0 || (float) $ratingMin > 10)) {
        $ratingMin = '';
    }

    return [
        'genre' => trim((string) ($input['genre'] ?? '')),
        'country' => trim((string) ($input['country'] ?? '')),
        'year_from' => $yearFrom,
        'rating_min' => $ratingMin,
        'sort' => $sort,
        'direction' => $direction,
    ];
}

function fetch_genres(): array
{
    return db()->query('SELECT DISTINCT genre FROM films ORDER BY genre')->fetchAll(PDO::FETCH_COLUMN);
}

function user_has_film(int $userId, int $filmId): bool
{
    $stmt = db()->prepare('SELECT COUNT(*) FROM desired_films WHERE user_id = ? AND film_id = ?');
    $stmt->execute([$userId, $filmId]);
    return (int) $stmt->fetchColumn() > 0;
}

function flash(?string $message = null, string $type = 'notice'): ?array
{
    if ($message !== null) {
        $_SESSION['flash'] = [
            'message' => $message,
            'type' => in_array($type, ['notice', 'warning', 'error'], true) ? $type : 'notice',
        ];
        return null;
    }
    $stored = $_SESSION['flash'] ?? null;
    unset($_SESSION['flash']);
    if (is_string($stored)) {
        return ['message' => $stored, 'type' => 'notice'];
    }
    return is_array($stored) ? $stored : null;
}

function photo_exists(int $photoId): bool
{
    $stmt = db()->prepare('SELECT COUNT(*) FROM photos WHERE id = ?');
    $stmt->execute([$photoId]);
    return (int) $stmt->fetchColumn() > 0;
}

function cart_film_ids(): array
{
    $ids = $_SESSION['film_cart'] ?? [];
    return is_array($ids) ? array_values(array_unique(array_map('intval', $ids))) : [];
}

function cart_has_film(int $filmId): bool
{
    return in_array($filmId, cart_film_ids(), true);
}

function add_film_to_cart(int $filmId): void
{
    $ids = cart_film_ids();
    if (!in_array($filmId, $ids, true)) {
        $ids[] = $filmId;
    }
    $_SESSION['film_cart'] = $ids;
}

function remove_film_from_cart(int $filmId): void
{
    $_SESSION['film_cart'] = array_values(array_filter(
        cart_film_ids(),
        static fn (int $id): bool => $id !== $filmId
    ));
}

function fetch_cart_films(): array
{
    $ids = cart_film_ids();
    if (!$ids) {
        return [];
    }

    $placeholders = implode(',', array_fill(0, count($ids), '?'));
    $stmt = db()->prepare("SELECT * FROM films WHERE id IN ({$placeholders}) ORDER BY title ASC");
    $stmt->execute($ids);
    return $stmt->fetchAll();
}

function text_length(string $value): int
{
    return function_exists('mb_strlen') ? mb_strlen($value) : strlen($value);
}
