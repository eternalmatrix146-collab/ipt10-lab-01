<?php
declare(strict_types=1);

namespace App\Models;

class Comment extends Database
{
    public const MAX_DISPLAY_DEPTH = 8;
    public const MAX_TRAVERSAL_DEPTH = 50;
    public const MAX_BODY_LENGTH = 2000;
    public const MIN_BODY_LENGTH = 1;

    /**
     * Returns a flat array of comments ordered in depth-first traversal order.
     */
    public function threadedForDiscussion(int $discussionId): array
    {
        $rows = $this->fetchRawComments($discussionId);
        [$roots, $children, $byId] = $this->buildAdjacency($rows);

        $flattened = [];
        $this->traverseFlat($roots, $children, $byId, $flattened, 0, []);

        return $flattened;
    }

    /**
     * Returns a hierarchical recursive tree of root comments with nested 'replies'.
     */
    public function treeForDiscussion(int $discussionId): array
    {
        $rows = $this->fetchRawComments($discussionId);
        [$roots, $children, $byId] = $this->buildAdjacency($rows);

        return $this->buildRecursiveTree($roots, $children, $byId, 0, []);
    }

    private function fetchRawComments(int $discussionId): array
    {
        $stmt = $this->connect()->prepare(
            'SELECT c.id, c.discussion_id, c.parent_id, c.author_id, c.body, c.created_at, u.username AS author, u.avatar
             FROM comments c
             JOIN users u ON u.id = c.author_id
             WHERE c.discussion_id = ?
             ORDER BY c.created_at ASC, c.id ASC'
        );
        $stmt->execute([$discussionId]);
        return $stmt->fetchAll() ?: [];
    }

    private function buildAdjacency(array $rows): array
    {
        $byId = [];
        $children = [];
        foreach ($rows as $row) {
            $id = (int)$row['id'];
            $byId[$id] = $row;
            $children[$id] = [];
        }

        $roots = [];
        foreach ($rows as $row) {
            $id = (int)$row['id'];
            $parentId = $row['parent_id'] === null ? null : (int)$row['parent_id'];
            if ($parentId === null || !isset($byId[$parentId])) {
                $roots[] = $id;
            } else {
                $children[$parentId][] = $id;
            }
        }

        return [$roots, $children, $byId];
    }

    private function traverseFlat(array $ids, array $children, array $byId, array &$flattened, int $depth, array $ancestors): void
    {
        foreach ($ids as $id) {
            if (in_array($id, $ancestors, true)) {
                continue; // cycle prevention
            }
            $row = $byId[$id];
            $hasChildren = !empty($children[$id]);
            $flattened[] = [
                'id' => (int)$row['id'],
                'discussion_id' => (int)$row['discussion_id'],
                'parent_id' => $row['parent_id'] === null ? null : (int)$row['parent_id'],
                'author_id' => (int)$row['author_id'],
                'author' => $row['author'],
                'avatar' => User::resolveAvatar($row['author'], $row['avatar'] ?? null),
                'body' => $row['body'],
                'created_at' => $row['created_at'],
                'formatted_created_at' => gmdate('Y-m-d H:i:s', strtotime($row['created_at'])) . ' UTC',
                'depth' => $depth,
                'indent' => min($depth, self::MAX_DISPLAY_DEPTH),
                'has_children' => $hasChildren,
                'child_count' => count($children[$id]),
            ];

            $nextAncestors = $ancestors;
            $nextAncestors[] = $id;
            if (count($nextAncestors) > self::MAX_TRAVERSAL_DEPTH) {
                continue;
            }
            $this->traverseFlat($children[$id], $children, $byId, $flattened, $depth + 1, $nextAncestors);
        }
    }

    private function buildRecursiveTree(array $ids, array $children, array $byId, int $depth, array $ancestors): array
    {
        $nodes = [];
        foreach ($ids as $id) {
            if (in_array($id, $ancestors, true)) {
                continue;
            }
            $row = $byId[$id];
            $nextAncestors = $ancestors;
            $nextAncestors[] = $id;

            $childNodes = [];
            if (count($nextAncestors) <= self::MAX_TRAVERSAL_DEPTH) {
                $childNodes = $this->buildRecursiveTree($children[$id], $children, $byId, $depth + 1, $nextAncestors);
            }

            $hasChildren = !empty($childNodes);
            $nodes[] = [
                'id' => (int)$row['id'],
                'discussion_id' => (int)$row['discussion_id'],
                'parent_id' => $row['parent_id'] === null ? null : (int)$row['parent_id'],
                'author_id' => (int)$row['author_id'],
                'author' => $row['author'],
                'avatar' => User::resolveAvatar($row['author'], $row['avatar'] ?? null),
                'body' => $row['body'],
                'created_at' => $row['created_at'],
                'formatted_created_at' => gmdate('Y-m-d H:i:s', strtotime($row['created_at'])) . ' UTC',
                'depth' => $depth,
                'indent' => min($depth, self::MAX_DISPLAY_DEPTH),
                'has_children' => $hasChildren,
                'child_count' => count($childNodes),
                'replies' => $childNodes,
            ];
        }
        return $nodes;
    }

    public function create(int $discussionId, int $authorId, string $body, ?int $parentId): int
    {
        $body = trim($body);
        if (strlen($body) < self::MIN_BODY_LENGTH || strlen($body) > self::MAX_BODY_LENGTH) {
            throw new \InvalidArgumentException('Comment body must be between ' . self::MIN_BODY_LENGTH . ' and ' . self::MAX_BODY_LENGTH . ' characters.');
        }

        $stmt = $this->connect()->prepare('SELECT id FROM discussions WHERE id = ?');
        $stmt->execute([$discussionId]);
        if (!$stmt->fetch()) {
            throw new \InvalidArgumentException('Discussion not found.');
        }

        $resolvedDepth = 0;
        if ($parentId !== null) {
            $resolvedDepth = $this->validateParent($discussionId, $parentId);
        }

        $newDepth = $resolvedDepth;
        if ($newDepth > self::MAX_TRAVERSAL_DEPTH) {
            throw new \InvalidArgumentException('Maximum reply depth exceeded.');
        }

        $stmt = $this->connect()->prepare(
            'INSERT INTO comments (discussion_id, parent_id, author_id, body) VALUES (?, ?, ?, ?)'
        );
        $stmt->execute([$discussionId, $parentId, $authorId, $body]);

        return (int)$this->connect()->lastInsertId();
    }

    public function validateParent(int $discussionId, int $parentId): int
    {
        $depth = 0;
        $current = $parentId;
        $seen = [];

        while ($current !== null) {
            if (in_array($current, $seen, true)) {
                throw new \InvalidArgumentException('Reply cycle detected.');
            }
            $seen[] = $current;

            $stmt = $this->connect()->prepare('SELECT id, parent_id, discussion_id FROM comments WHERE id = ?');
            $stmt->execute([$current]);
            $row = $stmt->fetch();
            if (!$row) {
                throw new \InvalidArgumentException('Parent comment not found.');
            }

            if ((int)$row['discussion_id'] !== $discussionId) {
                throw new \InvalidArgumentException('Parent comment must belong to the same discussion.');
            }

            $current = $row['parent_id'] === null ? null : (int)$row['parent_id'];
            $depth++;

            if ($depth > self::MAX_TRAVERSAL_DEPTH) {
                throw new \InvalidArgumentException('Maximum reply depth exceeded.');
            }
        }

        return $depth;
    }
}
