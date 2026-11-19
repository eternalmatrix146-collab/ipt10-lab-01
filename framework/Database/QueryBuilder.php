<?php

declare(strict_types=1);

namespace Phantom\Database;

use InvalidArgumentException;

/**
 * Fluent SQL builder. Values are always bound as parameters; identifiers
 * (tables/columns) are checked against a strict pattern so user input can
 * never be spliced into the SQL text.
 *
 *   DB::table('products')->where('stock', '>', 0)->orderBy('price')->limit(10)->get();
 */
class QueryBuilder
{
    private const OPERATORS = ['=', '!=', '<>', '<', '>', '<=', '>=', 'LIKE', 'NOT LIKE'];

    /** @var string[] */
    private array $columns = ['*'];

    /** @var array<int, array{boolean: string, sql: string}> */
    private array $wheres = [];

    /** @var array<int, mixed> */
    private array $bindings = [];

    /** @var string[] */
    private array $joins = [];

    /** @var string[] */
    private array $orders = [];

    /** @var string[] */
    private array $groups = [];

    private ?int $limit = null;

    private ?int $offset = null;

    /** @var class-string<Model>|null hydrate rows into this model */
    private ?string $model = null;

    public function __construct(private Connection $connection, private string $table)
    {
        $this->assertIdentifier($table);
    }

    /** @param class-string<Model> $model */
    public function setModel(string $model): self
    {
        $this->model = $model;
        return $this;
    }

    public function select(string ...$columns): self
    {
        foreach ($columns as $column) {
            $this->assertIdentifier($column, allowAlias: true);
        }
        $this->columns = $columns ?: ['*'];
        return $this;
    }

    /** Trusted developer-written expressions only, e.g. "SUM(total) AS revenue". */
    public function selectRaw(string $expression): self
    {
        $this->columns = $this->columns === ['*'] ? [$expression] : [...$this->columns, $expression];
        return $this;
    }

    public function where(string $column, mixed $operator, mixed $value = null, string $boolean = 'AND'): self
    {
        if (func_num_args() === 2) {
            [$value, $operator] = [$operator, '='];
        }
        $operator = strtoupper((string) $operator);
        if (!in_array($operator, self::OPERATORS, true)) {
            throw new InvalidArgumentException("Invalid operator [{$operator}].");
        }
        $this->assertIdentifier($column);

        if ($value === null && in_array($operator, ['=', '!=', '<>'], true)) {
            $this->wheres[] = ['boolean' => $boolean, 'sql' => $column . ($operator === '=' ? ' IS NULL' : ' IS NOT NULL')];
            return $this;
        }

        $this->wheres[] = ['boolean' => $boolean, 'sql' => "{$column} {$operator} ?"];
        $this->bindings[] = $value;
        return $this;
    }

    public function orWhere(string $column, mixed $operator, mixed $value = null): self
    {
        if (func_num_args() === 2) {
            return $this->where($column, '=', $operator, 'OR');
        }
        return $this->where($column, $operator, $value, 'OR');
    }

    /** @param array<int, mixed> $values */
    public function whereIn(string $column, array $values): self
    {
        $this->assertIdentifier($column);
        if ($values === []) {
            $this->wheres[] = ['boolean' => 'AND', 'sql' => '1 = 0'];
            return $this;
        }
        $placeholders = implode(', ', array_fill(0, count($values), '?'));
        $this->wheres[] = ['boolean' => 'AND', 'sql' => "{$column} IN ({$placeholders})"];
        array_push($this->bindings, ...array_values($values));
        return $this;
    }

    public function whereNull(string $column): self
    {
        return $this->where($column, '=', null);
    }

    /**
     * Groups conditions in parentheses:
     *   ->whereGroup(fn ($q) => $q->where('name', 'LIKE', $t)->orWhere('description', 'LIKE', $t))
     */
    public function whereGroup(callable $callback, string $boolean = 'AND'): self
    {
        $nested = new self($this->connection, $this->table);
        $callback($nested);
        if ($nested->wheres) {
            $this->wheres[] = ['boolean' => $boolean, 'sql' => '(' . $nested->compileWheres() . ')'];
            array_push($this->bindings, ...$nested->bindings);
        }
        return $this;
    }

    /**
     * Trusted developer-written condition with bound values.
     *
     * @param array<int, mixed> $bindings
     */
    public function whereRaw(string $sql, array $bindings = [], string $boolean = 'AND'): self
    {
        $this->wheres[] = ['boolean' => $boolean, 'sql' => $sql];
        array_push($this->bindings, ...$bindings);
        return $this;
    }

    public function join(string $table, string $first, string $operator, string $second, string $type = 'INNER'): self
    {
        foreach ([$table, $first, $second] as $identifier) {
            $this->assertIdentifier($identifier, allowAlias: true);
        }
        if (!in_array($operator, self::OPERATORS, true)) {
            throw new InvalidArgumentException("Invalid join operator [{$operator}].");
        }
        $this->joins[] = "{$type} JOIN {$table} ON {$first} {$operator} {$second}";
        return $this;
    }

    public function leftJoin(string $table, string $first, string $operator, string $second): self
    {
        return $this->join($table, $first, $operator, $second, 'LEFT');
    }

    public function orderBy(string $column, string $direction = 'ASC'): self
    {
        $this->assertIdentifier($column);
        $direction = strtoupper($direction) === 'DESC' ? 'DESC' : 'ASC';
        $this->orders[] = "{$column} {$direction}";
        return $this;
    }

    public function latest(string $column = 'created_at'): self
    {
        return $this->orderBy($column, 'DESC');
    }

    public function groupBy(string ...$columns): self
    {
        foreach ($columns as $column) {
            $this->assertIdentifier($column);
        }
        $this->groups = array_merge($this->groups, $columns);
        return $this;
    }

    public function limit(int $limit): self
    {
        $this->limit = max(0, $limit);
        return $this;
    }

    public function offset(int $offset): self
    {
        $this->offset = max(0, $offset);
        return $this;
    }

    public function toSql(): string
    {
        $sql = 'SELECT ' . implode(', ', $this->columns) . ' FROM ' . $this->table;
        if ($this->joins) {
            $sql .= ' ' . implode(' ', $this->joins);
        }
        if ($this->wheres) {
            $sql .= ' WHERE ' . $this->compileWheres();
        }
        if ($this->groups) {
            $sql .= ' GROUP BY ' . implode(', ', $this->groups);
        }
        if ($this->orders) {
            $sql .= ' ORDER BY ' . implode(', ', $this->orders);
        }
        if ($this->limit !== null) {
            $sql .= ' LIMIT ' . $this->limit;
            if ($this->offset !== null) {
                $sql .= ' OFFSET ' . $this->offset;
            }
        }
        return $sql;
    }

    /** @return array<int, mixed> */
    public function getBindings(): array
    {
        return $this->bindings;
    }

    /** @return array<int, mixed> rows as arrays, or models when setModel() was used */
    public function get(): array
    {
        $rows = $this->connection->select($this->toSql(), $this->bindings);
        if ($this->model === null) {
            return $rows;
        }
        $model = $this->model;
        return array_map(static fn (array $row) => $model::hydrate($row), $rows);
    }

    public function first(): mixed
    {
        $clone = clone $this;
        $rows = $clone->limit(1)->get();
        return $rows[0] ?? null;
    }

    public function value(string $column): mixed
    {
        $row = (clone $this)->select($column)->setModelless()->first();
        return $row === null ? null : reset($row);
    }

    public function exists(): bool
    {
        return $this->count() > 0;
    }

    public function count(): int
    {
        return (int) $this->aggregate('COUNT(*)');
    }

    public function sum(string $column): float
    {
        $this->assertIdentifier($column);
        return (float) $this->aggregate("SUM({$column})");
    }

    private function aggregate(string $expression): mixed
    {
        $clone = clone $this;
        $clone->columns = ["{$expression} AS aggregate"];
        $clone->orders = [];
        $clone->limit = null;
        $clone->offset = null;
        $row = $this->connection->selectOne($clone->toSql(), $clone->bindings);
        return $row['aggregate'] ?? 0;
    }

    /**
     * @return array{data: array<int, mixed>, total: int, page: int, per_page: int, pages: int}
     */
    public function paginate(int $perPage = 12, int $page = 1): array
    {
        $total = $this->count();
        $pages = max(1, (int) ceil($total / max(1, $perPage)));
        $page = min(max(1, $page), $pages);
        $data = (clone $this)->limit($perPage)->offset(($page - 1) * $perPage)->get();

        return ['data' => $data, 'total' => $total, 'page' => $page, 'per_page' => $perPage, 'pages' => $pages];
    }

    /** @param array<string, mixed> $values */
    public function insert(array $values): int
    {
        foreach (array_keys($values) as $column) {
            $this->assertIdentifier($column);
        }
        $columns = implode(', ', array_keys($values));
        $placeholders = implode(', ', array_fill(0, count($values), '?'));
        $this->connection->execute(
            "INSERT INTO {$this->table} ({$columns}) VALUES ({$placeholders})",
            array_values($values)
        );
        return $this->connection->lastInsertId();
    }

    /** @param array<string, mixed> $values */
    public function update(array $values): int
    {
        $sets = [];
        foreach (array_keys($values) as $column) {
            $this->assertIdentifier($column);
            $sets[] = "{$column} = ?";
        }
        $sql = "UPDATE {$this->table} SET " . implode(', ', $sets);
        if ($this->wheres) {
            $sql .= ' WHERE ' . $this->compileWheres();
        }
        return $this->connection->execute($sql, [...array_values($values), ...$this->bindings]);
    }

    public function increment(string $column, int $amount = 1): int
    {
        $this->assertIdentifier($column);
        $sql = "UPDATE {$this->table} SET {$column} = {$column} + ?";
        if ($this->wheres) {
            $sql .= ' WHERE ' . $this->compileWheres();
        }
        return $this->connection->execute($sql, [$amount, ...$this->bindings]);
    }

    public function decrement(string $column, int $amount = 1): int
    {
        return $this->increment($column, -$amount);
    }

    public function delete(): int
    {
        $sql = "DELETE FROM {$this->table}";
        if ($this->wheres) {
            $sql .= ' WHERE ' . $this->compileWheres();
        }
        return $this->connection->execute($sql, $this->bindings);
    }

    private function setModelless(): self
    {
        $this->model = null;
        return $this;
    }

    private function compileWheres(): string
    {
        $sql = '';
        foreach ($this->wheres as $i => $where) {
            $sql .= ($i === 0 ? '' : " {$where['boolean']} ") . $where['sql'];
        }
        return $sql;
    }

    private function assertIdentifier(string $identifier, bool $allowAlias = false): void
    {
        $pattern = $allowAlias
            ? '/^[A-Za-z_][A-Za-z0-9_]*(\.[A-Za-z_*][A-Za-z0-9_]*)?(\s+(AS\s+)?[A-Za-z_][A-Za-z0-9_]*)?$|^\*$/i'
            : '/^[A-Za-z_][A-Za-z0-9_]*(\.[A-Za-z_][A-Za-z0-9_]*)?$/';
        if (!preg_match($pattern, $identifier)) {
            throw new InvalidArgumentException("Invalid SQL identifier [{$identifier}].");
        }
    }
}
