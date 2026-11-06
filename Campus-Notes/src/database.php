<?php
/**
 * Database connection (issue 8: credential configuration).
 *
 * - Credentials come only from environment variables. There is no fallback to
 *   root / empty password: if they are missing the app fails closed.
 * - Real (non-emulated) prepared statements are used everywhere (issue 1).
 */
declare(strict_types=1);

function db(): PDO
{
    static $pdo = null;
    if ($pdo instanceof PDO) {
        return $pdo;
    }

    $user = getenv('LAB_DB_USER');
    $pass = getenv('LAB_DB_PASSWORD');
    if (!is_string($user) || $user === '' || !is_string($pass) || $pass === '') {
        throw new RuntimeException('Database credentials are not configured.');
    }
    $host = getenv('LAB_DB_HOST') ?: 'localhost';
    $name = getenv('LAB_DB_NAME') ?: 'notes_lab';

    $pdo = new PDO(
        "mysql:host={$host};dbname={$name};charset=utf8mb4",
        $user,
        $pass,
        [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
            // rowCount() reports matched rows, so "updated with same text" still counts as found.
            (defined('Pdo\Mysql::ATTR_FOUND_ROWS') ? constant('Pdo\Mysql::ATTR_FOUND_ROWS') : PDO::MYSQL_ATTR_FOUND_ROWS) => true,
        ]
    );
    return $pdo;
}
