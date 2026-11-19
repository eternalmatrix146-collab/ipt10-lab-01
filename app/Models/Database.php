<?php
declare(strict_types=1);

namespace App\Models;

use PDO;
use PDOException;

class Database
{
    private static ?PDO $pdo = null;

    public function connect(): PDO
    {
        if (self::$pdo !== null) {
            return self::$pdo;
        }

        $config = require __DIR__ . '/../../config/database.php';

        $dsn = sprintf(
            'mysql:host=%s;port=%d;dbname=%s;charset=%s',
            $config['host'],
            $config['port'],
            $config['dbname'],
            $config['charset']
        );

        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];

        try {
            self::$pdo = new PDO($dsn, $config['username'], $config['password'], $options);
        } catch (PDOException $e) {
            http_response_code(500);
            echo 'Database connection failed: ' . $e->getMessage();
            exit;
        }

        return self::$pdo;
    }

    public static function setConnection(?PDO $customPdo): void
    {
        self::$pdo = $customPdo;
    }
}
