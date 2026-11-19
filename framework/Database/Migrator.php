<?php

declare(strict_types=1);

namespace Phantom\Database;

/**
 * Runs migration files in filename order and records them in a
 * `migrations` table with a batch number, so `rollback` can undo the
 * last batch.
 */
class Migrator
{
    /** @var callable(string): void */
    private $output;

    public function __construct(private Connection $db, private string $path, ?callable $output = null)
    {
        $this->output = $output ?? static function (string $line): void {
        };
    }

    public function ensureTable(): void
    {
        $id = $this->db->driver() === 'sqlite'
            ? 'id INTEGER PRIMARY KEY AUTOINCREMENT'
            : 'id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY';
        $this->db->unprepared(
            "CREATE TABLE IF NOT EXISTS migrations ({$id}, migration VARCHAR(255) NOT NULL, batch INT NOT NULL, ran_at DATETIME NOT NULL)"
        );
    }

    /** @return string[] names of migrations that ran */
    public function migrate(): array
    {
        $this->ensureTable();
        $ran = array_column($this->db->select('SELECT migration FROM migrations'), 'migration');
        $batch = (int) ($this->db->selectOne('SELECT MAX(batch) AS b FROM migrations')['b'] ?? 0) + 1;

        $executed = [];
        foreach ($this->files() as $name => $file) {
            if (in_array($name, $ran, true)) {
                continue;
            }
            ($this->output)("Migrating: {$name}");
            $this->load($file)->up($this->db);
            $this->db->table('migrations')->insert([
                'migration' => $name,
                'batch' => $batch,
                'ran_at' => date('Y-m-d H:i:s'),
            ]);
            $executed[] = $name;
        }
        return $executed;
    }

    /** @return string[] names of migrations rolled back */
    public function rollback(): array
    {
        $this->ensureTable();
        $batch = (int) ($this->db->selectOne('SELECT MAX(batch) AS b FROM migrations')['b'] ?? 0);
        if ($batch === 0) {
            return [];
        }

        $files = $this->files();
        $rows = $this->db->select('SELECT migration FROM migrations WHERE batch = ? ORDER BY id DESC', [$batch]);
        $rolledBack = [];
        foreach ($rows as $row) {
            $name = $row['migration'];
            if (isset($files[$name])) {
                ($this->output)("Rolling back: {$name}");
                $this->load($files[$name])->down($this->db);
            }
            $this->db->table('migrations')->where('migration', $name)->delete();
            $rolledBack[] = $name;
        }
        return $rolledBack;
    }

    /** Drops every table, then migrates from scratch. */
    public function fresh(): array
    {
        if ($this->db->driver() === 'mysql') {
            $this->db->unprepared('SET FOREIGN_KEY_CHECKS = 0');
            foreach ($this->db->select('SHOW TABLES') as $row) {
                $table = (string) reset($row);
                if (preg_match('/^[A-Za-z0-9_]+$/', $table)) {
                    $this->db->unprepared("DROP TABLE IF EXISTS `{$table}`");
                }
            }
            $this->db->unprepared('SET FOREIGN_KEY_CHECKS = 1');
        } else {
            foreach ($this->db->select("SELECT name FROM sqlite_master WHERE type='table' AND name NOT LIKE 'sqlite_%'") as $row) {
                $this->db->unprepared("DROP TABLE IF EXISTS \"{$row['name']}\"");
            }
        }
        ($this->output)('Dropped all tables.');
        return $this->migrate();
    }

    /** @return array<string, array{name: string, ran: bool, batch: int|null}> */
    public function status(): array
    {
        $this->ensureTable();
        $ran = [];
        foreach ($this->db->select('SELECT migration, batch FROM migrations') as $row) {
            $ran[$row['migration']] = (int) $row['batch'];
        }
        $status = [];
        foreach (array_keys($this->files()) as $name) {
            $status[$name] = ['name' => $name, 'ran' => isset($ran[$name]), 'batch' => $ran[$name] ?? null];
        }
        return $status;
    }

    /** @return array<string, string> migration name => file path, sorted */
    private function files(): array
    {
        $files = [];
        foreach (glob(rtrim($this->path, '/\\') . '/*.php') ?: [] as $file) {
            $files[basename($file, '.php')] = $file;
        }
        ksort($files);
        return $files;
    }

    private function load(string $file): Migration
    {
        $migration = require $file;
        if (!$migration instanceof Migration) {
            throw new \RuntimeException("{$file} must return an instance of " . Migration::class);
        }
        return $migration;
    }
}
