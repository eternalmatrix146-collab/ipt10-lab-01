<?php

use Phantom\Database\Connection;
use Phantom\Database\Migration;

return new class extends Migration {
    public function up(Connection $db): void
    {
        $db->unprepared(<<<SQL
            CREATE TABLE orders (
                id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                user_id INT UNSIGNED NOT NULL,
                reference VARCHAR(20) NOT NULL UNIQUE,
                status ENUM('pending', 'processing', 'shipped', 'delivered', 'cancelled') NOT NULL DEFAULT 'pending',
                subtotal DECIMAL(10, 2) NOT NULL,
                shipping_fee DECIMAL(10, 2) NOT NULL DEFAULT 0,
                total DECIMAL(10, 2) NOT NULL,
                display_currency CHAR(3) NOT NULL DEFAULT 'PHP',
                display_rate DECIMAL(16, 8) NOT NULL DEFAULT 1,
                payment_method ENUM('cod', 'gcash', 'card') NOT NULL DEFAULT 'cod',
                shipping_name VARCHAR(120) NOT NULL,
                shipping_phone VARCHAR(30) NOT NULL,
                shipping_address VARCHAR(255) NOT NULL,
                shipping_city VARCHAR(100) NOT NULL,
                notes VARCHAR(500) NULL,
                created_at DATETIME NOT NULL,
                updated_at DATETIME NOT NULL,
                INDEX idx_orders_user (user_id),
                INDEX idx_orders_status (status),
                CONSTRAINT fk_orders_user FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE RESTRICT
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

            CREATE TABLE order_items (
                id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                order_id INT UNSIGNED NOT NULL,
                product_id INT UNSIGNED NULL,
                product_name VARCHAR(150) NOT NULL,
                unit_price DECIMAL(10, 2) NOT NULL,
                quantity INT UNSIGNED NOT NULL,
                line_total DECIMAL(10, 2) NOT NULL,
                created_at DATETIME NOT NULL,
                updated_at DATETIME NOT NULL,
                INDEX idx_items_order (order_id),
                CONSTRAINT fk_items_order FOREIGN KEY (order_id) REFERENCES orders (id) ON DELETE CASCADE,
                CONSTRAINT fk_items_product FOREIGN KEY (product_id) REFERENCES products (id) ON DELETE SET NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        SQL);
    }

    public function down(Connection $db): void
    {
        $db->unprepared('DROP TABLE IF EXISTS order_items; DROP TABLE IF EXISTS orders;');
    }
};
