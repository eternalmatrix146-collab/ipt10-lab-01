<?php

declare(strict_types=1);

namespace Phantom\Database;

use JsonSerializable;

/**
 * Lightweight Active Record base class.
 *
 *   class Product extends Model {
 *       protected static string $table = 'products';
 *       protected static array $fillable = ['name', 'price'];
 *   }
 *
 *   $p = Product::create(['name' => 'Mask', 'price' => 499]);
 *   $p->price = 450; $p->save();
 *   Product::where('stock', '>', 0)->orderBy('name')->get();
 */
abstract class Model implements JsonSerializable
{
    protected static string $table = '';

    protected static string $primaryKey = 'id';

    /** @var string[] columns that create()/fill() may set from user input */
    protected static array $fillable = [];

    /** @var string[] columns stripped from toArray()/JSON */
    protected static array $hidden = [];

    protected static bool $timestamps = true;

    /** @var array<string, mixed> */
    protected array $attributes = [];

    /** @var array<string, mixed> */
    protected array $original = [];

    public bool $exists = false;

    /** @param array<string, mixed> $attributes */
    public function __construct(array $attributes = [])
    {
        $this->fill($attributes);
    }

    public static function table(): string
    {
        return static::$table;
    }

    public static function query(): QueryBuilder
    {
        return DB::connection()->table(static::$table)->setModel(static::class);
    }

    /** Proxy so callers can write Product::where(...)->get(). */
    public static function where(string $column, mixed $operator, mixed $value = null): QueryBuilder
    {
        return func_num_args() === 2
            ? static::query()->where($column, $operator)
            : static::query()->where($column, $operator, $value);
    }

    /** @return static[] */
    public static function all(): array
    {
        return static::query()->get();
    }

    public static function find(int|string $id): ?static
    {
        return static::query()->where(static::$primaryKey, $id)->first();
    }

    public static function findOrFail(int|string $id): static
    {
        $model = static::find($id);
        if ($model === null) {
            throw new \Phantom\Exceptions\HttpException(404, class_basename(static::class) . ' not found');
        }
        return $model;
    }

    public static function findBy(string $column, mixed $value): ?static
    {
        return static::query()->where($column, $value)->first();
    }

    /** @param array<string, mixed> $attributes */
    public static function create(array $attributes): static
    {
        $model = new static($attributes);
        $model->save();
        return $model;
    }

    /** @param array<string, mixed> $row */
    public static function hydrate(array $row): static
    {
        $model = new static();
        $model->attributes = $row;
        $model->original = $row;
        $model->exists = true;
        return $model;
    }

    /**
     * Mass-assign only whitelisted columns (protects against over-posting).
     *
     * @param array<string, mixed> $attributes
     */
    public function fill(array $attributes): static
    {
        foreach ($attributes as $key => $value) {
            if (in_array($key, static::$fillable, true)) {
                $this->attributes[$key] = $value;
            }
        }
        return $this;
    }

    public function save(): bool
    {
        $now = date('Y-m-d H:i:s');

        if ($this->exists) {
            $dirty = array_diff_assoc(
                array_map(static fn ($v) => is_scalar($v) || $v === null ? $v : json_encode($v), $this->attributes),
                array_map(static fn ($v) => is_scalar($v) || $v === null ? $v : json_encode($v), $this->original)
            );
            unset($dirty[static::$primaryKey]);
            if ($dirty === []) {
                return true;
            }
            if (static::$timestamps) {
                $dirty['updated_at'] = $this->attributes['updated_at'] = $now;
            }
            DB::table(static::$table)->where(static::$primaryKey, $this->getKey())->update($dirty);
        } else {
            if (static::$timestamps) {
                $this->attributes['created_at'] ??= $now;
                $this->attributes['updated_at'] ??= $now;
            }
            $id = DB::table(static::$table)->insert($this->attributes);
            $this->attributes[static::$primaryKey] ??= $id;
            $this->exists = true;
        }

        $this->original = $this->attributes;
        return true;
    }

    /** @param array<string, mixed> $attributes */
    public function update(array $attributes): bool
    {
        $this->fill($attributes);
        return $this->save();
    }

    public function delete(): bool
    {
        if (!$this->exists) {
            return false;
        }
        DB::table(static::$table)->where(static::$primaryKey, $this->getKey())->delete();
        $this->exists = false;
        return true;
    }

    public function getKey(): mixed
    {
        return $this->attributes[static::$primaryKey] ?? null;
    }

    /**
     * Relationship helper: $order->hasMany(OrderItem::class, 'order_id')
     *
     * @param class-string<Model> $related
     */
    public function hasMany(string $related, string $foreignKey): QueryBuilder
    {
        return $related::query()->where($foreignKey, $this->getKey());
    }

    /**
     * Relationship helper: $product->belongsTo(Category::class, 'category_id')
     *
     * @param class-string<Model> $related
     */
    public function belongsTo(string $related, string $foreignKey): ?Model
    {
        $id = $this->attributes[$foreignKey] ?? null;
        return $id === null ? null : $related::find($id);
    }

    public function __get(string $key): mixed
    {
        return $this->attributes[$key] ?? null;
    }

    public function __set(string $key, mixed $value): void
    {
        $this->attributes[$key] = $value;
    }

    public function __isset(string $key): bool
    {
        return isset($this->attributes[$key]);
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return array_diff_key($this->attributes, array_flip(static::$hidden));
    }

    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
