<?php

declare(strict_types=1);

namespace Phantom\Database;

use Closure;
use RuntimeException;

/**
 * Static access point to the shared connection. The connection is opened
 * lazily, so pages that never touch the database never pay for it.
 */
final class DB
{
    private static ?Connection $connection = null;

    private static ?Closure $resolver = null;

    public static function setResolver(Closure $resolver): void
    {
        self::$resolver = $resolver;
        self::$connection = null;
    }

    public static function setConnection(?Connection $connection): void
    {
        self::$connection = $connection;
    }

    public static function connection(): Connection
    {
        if (self::$connection === null) {
            if (self::$resolver === null) {
                throw new RuntimeException('No database connection configured.');
            }
            self::$connection = (self::$resolver)();
        }
        return self::$connection;
    }

    public static function table(string $table): QueryBuilder
    {
        return self::connection()->table($table);
    }

    /**
     * @param array<int|string, mixed> $bindings
     * @return array<int, array<string, mixed>>
     */
    public static function select(string $sql, array $bindings = []): array
    {
        return self::connection()->select($sql, $bindings);
    }

    /**
     * @template T
     * @param callable(Connection): T $callback
     * @return T
     */
    public static function transaction(callable $callback): mixed
    {
        return self::connection()->transaction($callback);
    }
}
