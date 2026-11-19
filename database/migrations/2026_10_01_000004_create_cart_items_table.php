<?php

use Phantom\Database\Connection;
use Phantom\Database\Migration;

return new class extends Migration {
    public function up(Connection $db): void
    {
        // A cart belongs to a logged-in user OR an anonymous session token.
        $db->unprepared(<<<SQL
            CREATE TABLE cart_items (
                id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                user_id INT UNSIGNED NULL,
                session_token VARCHAR(64) NULL,
                product_id INT UNSIGNED NOT NULL,
                quantity INT UNSIGNED NOT NULL DEFAULT 1,
                created_at DATETIME NOT NULL,
                updated_at DATETIME NOT NULL,
                INDEX idx_cart_user (user_id),
                INDEX idx_cart_session (session_token),
                CONSTRAINT fk_cart_user FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE,
                CONSTRAINT fk_cart_product FOREIGN KEY (product_id) REFERENCES products (id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        SQL);
    }

    public function down(Connection $db): void
    {
        $db->unprepared('DROP TABLE IF EXISTS cart_items');
    }
};
