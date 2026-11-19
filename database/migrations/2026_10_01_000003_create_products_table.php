<?php

use Phantom\Database\Connection;
use Phantom\Database\Migration;

return new class extends Migration {
    public function up(Connection $db): void
    {
        $db->unprepared(<<<SQL
            CREATE TABLE products (
                id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                category_id INT UNSIGNED NOT NULL,
                name VARCHAR(150) NOT NULL,
                slug VARCHAR(170) NOT NULL UNIQUE,
                tagline VARCHAR(160) NULL,
                description TEXT NULL,
                price DECIMAL(10, 2) NOT NULL,
                stock INT UNSIGNED NOT NULL DEFAULT 0,
                is_featured TINYINT(1) NOT NULL DEFAULT 0,
                created_at DATETIME NOT NULL,
                updated_at DATETIME NOT NULL,
                INDEX idx_products_category (category_id),
                INDEX idx_products_price (price),
                FULLTEXT INDEX ft_products_search (name, tagline, description),
                CONSTRAINT fk_products_category FOREIGN KEY (category_id)
                    REFERENCES categories (id) ON DELETE RESTRICT ON UPDATE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        SQL);
    }

    public function down(Connection $db): void
    {
        $db->unprepared('DROP TABLE IF EXISTS products');
    }
};
