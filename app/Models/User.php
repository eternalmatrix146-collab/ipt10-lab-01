<?php

declare(strict_types=1);

namespace App\Models;

use Phantom\Database\Model;

/**
 * @property int $id
 * @property string $name
 * @property string $email
 * @property string $password
 * @property string $role
 * @property string|null $codename
 */
class User extends Model
{
    protected static string $table = 'users';

    protected static array $fillable = ['name', 'email', 'password', 'role', 'codename'];

    protected static array $hidden = ['password'];

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    public function displayName(): string
    {
        return $this->codename ?: explode(' ', (string) $this->name)[0];
    }

    /** @return Order[] */
    public function orders(): array
    {
        return $this->hasMany(Order::class, 'user_id')->latest()->get();
    }
}
