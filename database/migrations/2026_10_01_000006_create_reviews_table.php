<?php

use Phantom\Database\Connection;
use Phantom\Database\Migration;

return new class extends Migration {
    public function up(Connection $db): void
    {
        $db->unprepared(<<<SQL
            CREATE TABLE reviews (
                id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                product_id INT UNSIGNED NOT NULL,
                user_id INT UNSIGNED NOT NULL,
                rating TINYINT UNSIGNED NOT NULL,
                comment VARCHAR(1000) NOT NULL,
                created_at DATETIME NOT NULL,
                updated_at DATETIME NOT NULL,
                UNIQUE KEY uq_review_once (product_id, user_id),
                CONSTRAINT fk_reviews_product FOREIGN KEY (product_id) REFERENCES products (id) ON DELETE CASCADE,
                CONSTRAINT fk_reviews_user FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        SQL);
    }

    public function down(Connection $db): void
    {
        $db->unprepared('DROP TABLE IF EXISTS reviews');
    }
};
