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

function flash(?string $message = null): ?string
{
    if ($message !== null) {
        $_SESSION['flash'] = $message;
        return null;
    }
    $stored = $_SESSION['flash'] ?? null;
    unset($_SESSION['flash']);
    return $stored;
}
