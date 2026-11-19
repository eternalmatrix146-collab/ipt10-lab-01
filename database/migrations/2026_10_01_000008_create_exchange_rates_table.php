<?php

use Phantom\Database\Connection;
use Phantom\Database\Migration;

return new class extends Migration {
    public function up(Connection $db): void
    {
        // Last-known rates from the Frankfurter API, used as a cache and as a fallback when it is unreachable.
        $db->unprepared(<<<SQL
            CREATE TABLE exchange_rates (
                id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                base CHAR(3) NOT NULL,
                currency CHAR(3) NOT NULL,
                rate DECIMAL(16, 8) NOT NULL,
                rate_date DATE NOT NULL,
                fetched_at DATETIME NOT NULL,
                created_at DATETIME NOT NULL,
                updated_at DATETIME NOT NULL,
                UNIQUE KEY uq_rate_pair (base, currency)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        SQL);
    }

    public function down(Connection $db): void
    {
        $db->unprepared('DROP TABLE IF EXISTS exchange_rates');
    }
};
