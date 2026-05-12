<?php
declare(strict_types=1);

const DB_HOST = 'localhost';
const DB_NAME = 'lv4_videoteka';
const DB_USER = 'root';
const DB_PASS = '';

function db(bool $withDatabase = true): PDO
{
    $database = $withDatabase ? ';dbname=' . DB_NAME : '';
    $dsn = 'mysql:host=' . DB_HOST . $database . ';charset=utf8mb4';

    return new PDO($dsn, DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);
}
