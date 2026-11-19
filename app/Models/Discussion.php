<?php
declare(strict_types=1);

namespace App\Models;

class Discussion extends Database
{
    public function findById(int $id): ?array
    {
        $stmt = $this->connect()->prepare(
            'SELECT d.id, d.title, d.body, d.created_at, u.username AS author, u.id AS author_id, u.avatar
             FROM discussions d
             JOIN users u ON u.id = d.user_id
             WHERE d.id = ?'
        );
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        if ($row) {
            $row['avatar'] = User::resolveAvatar($row['author'], $row['avatar'] ?? null);
            $row['formatted_created_at'] = gmdate('Y-m-d H:i:s', strtotime($row['created_at'])) . ' UTC';
        }
        return $row ?: null;
    }

    public function all(): array
    {
        $stmt = $this->connect()->query(
            'SELECT d.id, d.title, d.body, d.created_at, u.username AS author, u.id AS author_id
             FROM discussions d
             JOIN users u ON u.id = d.user_id
             ORDER BY d.id ASC'
        );
        return $stmt->fetchAll() ?: [];
    }
}
