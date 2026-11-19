<?php

declare(strict_types=1);

namespace Tests;

use Phantom\Database\Connection;
use Phantom\Database\DB;
use Phantom\Http\Client\HttpClient;
use PDO;

/**
 * Shared helpers for the suite. Database tests run against an in-memory
 * SQLite database so they never touch the real MySQL data.
 */
abstract class TestCase extends \Phantom\Testing\TestCase
{
    protected ?Connection $db = null;

    public function tearDown(): void
    {
        DB::setConnection(null); // next real query reconnects to the configured database
        HttpClient::fake(null);
    }

    protected function useSqlite(): Connection
    {
        $this->db = new Connection(new PDO('sqlite::memory:'));
        $this->db->unprepared(<<<SQL
            CREATE TABLE categories (id INTEGER PRIMARY KEY AUTOINCREMENT, name TEXT, slug TEXT UNIQUE, description TEXT, motif TEXT DEFAULT 'star', created_at TEXT, updated_at TEXT);
            CREATE TABLE products (id INTEGER PRIMARY KEY AUTOINCREMENT, category_id INTEGER, name TEXT, slug TEXT UNIQUE, tagline TEXT, description TEXT,
                price REAL, stock INTEGER DEFAULT 0, is_featured INTEGER DEFAULT 0, created_at TEXT, updated_at TEXT);
            CREATE TABLE users (id INTEGER PRIMARY KEY AUTOINCREMENT, name TEXT, email TEXT UNIQUE, password TEXT, role TEXT DEFAULT 'customer', codename TEXT, created_at TEXT, updated_at TEXT);
            CREATE TABLE exchange_rates (id INTEGER PRIMARY KEY AUTOINCREMENT, base TEXT, currency TEXT, rate REAL, rate_date TEXT, fetched_at TEXT, created_at TEXT, updated_at TEXT);
        SQL);
        DB::setConnection($this->db);
        return $this->db;
    }

    protected function seedCatalog(): void
    {
        $now = date('Y-m-d H:i:s');
        $cat = $this->db->table('categories')->insert(['name' => 'Masks', 'slug' => 'masks', 'motif' => 'mask', 'created_at' => $now, 'updated_at' => $now]);
        $cat2 = $this->db->table('categories')->insert(['name' => 'Coffee', 'slug' => 'coffee', 'motif' => 'cup', 'created_at' => $now, 'updated_at' => $now]);
        foreach ([
            [$cat, 'Crimson Mask', 'crimson-mask', 899, 10, 1],
            [$cat, 'Silver Fox Mask', 'silver-fox-mask', 1150, 0, 0],
            [$cat2, 'Midnight Roast', 'midnight-roast', 680, 50, 1],
        ] as [$c, $name, $slug, $price, $stock, $featured]) {
            $this->db->table('products')->insert([
                'category_id' => $c, 'name' => $name, 'slug' => $slug, 'tagline' => "{$name} tagline",
                'description' => "About {$name}", 'price' => $price, 'stock' => $stock, 'is_featured' => $featured,
                'created_at' => $now, 'updated_at' => $now,
            ]);
        }
    }
}
