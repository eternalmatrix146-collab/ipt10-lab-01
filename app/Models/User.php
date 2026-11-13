<?php
declare(strict_types=1);

namespace App\Models;

class User extends Database
{
    public function findById(int $id): ?array
    {
        $stmt = $this->connect()->prepare('SELECT id, username, email, avatar, created_at FROM users WHERE id = ?');
        $stmt->execute([$id]);
        $user = $stmt->fetch();
        if ($user) {
            $user['avatar'] = self::resolveAvatar($user['username'], $user['avatar'] ?? null);
        }
        return $user ?: null;
    }

    public function findByUsername(string $username): ?array
    {
        $stmt = $this->connect()->prepare('SELECT id, username, email, password_hash, avatar, created_at FROM users WHERE username = ?');
        $stmt->execute([$username]);
        $user = $stmt->fetch();
        if ($user) {
            $user['avatar'] = self::resolveAvatar($user['username'], $user['avatar'] ?? null);
        }
        return $user ?: null;
    }

    public function findByEmail(string $email): ?array
    {
        $stmt = $this->connect()->prepare('SELECT id, username, email, password_hash, avatar, created_at FROM users WHERE email = ?');
        $stmt->execute([$email]);
        $user = $stmt->fetch();
        if ($user) {
            $user['avatar'] = self::resolveAvatar($user['username'], $user['avatar'] ?? null);
        }
        return $user ?: null;
    }

    public function create(string $username, string $email, string $password): ?int
    {
        $hash = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $this->connect()->prepare(
            'INSERT INTO users (username, email, password_hash) VALUES (?, ?, ?)'
        );
        $stmt->execute([$username, $email, $hash]);
        return (int)$this->connect()->lastInsertId();
    }

    public function verifyPassword(array $user, string $password): bool
    {
        $hash = $user['password_hash'] ?? null;
        if ($hash === null || $hash === '') {
            return false;
        }
        return password_verify($password, $hash);
    }

    public static function resolveAvatar(string $username, ?string $avatar): string
    {
        if ($avatar !== null && $avatar !== '') {
            return $avatar;
        }
        // Default avatar: SVG Data URI with the user's initial
        $initial = strtoupper(substr($username, 0, 1));
        $colors = ['#3b82f6', '#10b981', '#f59e0b', '#ef4444', '#8b5cf6', '#ec4899', '#06b6d4'];
        $color = $colors[crc32($username) % count($colors)];
        
        $svg = sprintf(
            '<svg xmlns="http://www.w3.org/2000/svg" width="36" height="36" viewBox="0 0 36 36"><circle cx="18" cy="18" r="18" fill="%s"/><text x="18" y="24" text-anchor="middle" font-family="sans-serif" font-size="16" font-weight="bold" fill="#ffffff">%s</text></svg>',
            $color,
            htmlspecialchars($initial, ENT_QUOTES, 'UTF-8')
        );
        return 'data:image/svg+xml;utf8,' . rawurlencode($svg);
    }
}
