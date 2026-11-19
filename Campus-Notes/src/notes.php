<?php
/**
 * Note data access (issues 1, 3, 7, 9).
 *
 * Every query that reads or changes a note includes "AND user_id = ?" with the
 * signed-in user's ID from the session. A note owned by someone else is
 * treated exactly like a note that does not exist.
 */
declare(strict_types=1);

const NOTE_TITLE_MAX = 120;     // matches notes.title VARCHAR(120)
const NOTE_BODY_MAX  = 10000;

function parse_id(mixed $value): ?int
{
    if (is_int($value)) {
        return $value > 0 ? $value : null;
    }
    if (!is_string($value) || !ctype_digit($value)) {
        return null;
    }
    $id = filter_var($value, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
    return $id === false ? null : $id;
}

/** @return string[] validation errors (empty when valid) */
function validate_note_input(mixed $title, mixed $body): array
{
    $errors = [];
    if (!is_string($title) || trim($title) === '') {
        $errors[] = 'Title is required and must be text.';
    } elseif (mb_strlen($title) > NOTE_TITLE_MAX) {
        $errors[] = 'Title must be at most ' . NOTE_TITLE_MAX . ' characters.';
    }
    if (!is_string($body) || trim($body) === '') {
        $errors[] = 'Body is required and must be text.';
    } elseif (mb_strlen($body) > NOTE_BODY_MAX) {
        $errors[] = 'Body must be at most ' . NOTE_BODY_MAX . ' characters.';
    }
    if ((is_string($title) && !mb_check_encoding($title, 'UTF-8'))
        || (is_string($body) && !mb_check_encoding($body, 'UTF-8'))) {
        $errors[] = 'Text must be valid UTF-8.';
    }
    return $errors;
}

function notes_for_user(PDO $db, int $userId): array
{
    $stmt = $db->prepare('SELECT id, user_id, title, body FROM notes WHERE user_id = ? ORDER BY id DESC');
    $stmt->execute([$userId]);
    return $stmt->fetchAll();
}

function find_note_for_user(PDO $db, int $noteId, int $userId): ?array
{
    $stmt = $db->prepare('SELECT id, user_id, title, body FROM notes WHERE id = ? AND user_id = ?');
    $stmt->execute([$noteId, $userId]);
    return $stmt->fetch() ?: null;
}

function create_note(PDO $db, int $userId, string $title, string $body): int
{
    $stmt = $db->prepare('INSERT INTO notes (user_id, title, body) VALUES (?, ?, ?)');
    $stmt->execute([$userId, trim($title), $body]);
    return (int) $db->lastInsertId();
}

/** @return bool false when the note does not exist or belongs to someone else */
function update_note_for_user(PDO $db, int $noteId, int $userId, string $title, string $body): bool
{
    $stmt = $db->prepare('UPDATE notes SET title = ?, body = ? WHERE id = ? AND user_id = ?');
    $stmt->execute([trim($title), $body, $noteId, $userId]);
    return $stmt->rowCount() === 1;
}

/** @return bool false when the note does not exist or belongs to someone else */
function delete_note_for_user(PDO $db, int $noteId, int $userId): bool
{
    $stmt = $db->prepare('DELETE FROM notes WHERE id = ? AND user_id = ?');
    $stmt->execute([$noteId, $userId]);
    return $stmt->rowCount() === 1;
}
