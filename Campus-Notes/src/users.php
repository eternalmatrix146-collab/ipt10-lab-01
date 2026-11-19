<?php
/**
 * Account logic (issues 1, 2, 7).
 *
 * - password_hash()/password_verify() instead of MD5.
 * - Prepared statements only.
 * - New accounts are always 'student'; the role is never taken from the request.
 */
declare(strict_types=1);

const PASSWORD_MIN_LENGTH = 12;
const PASSWORD_MAX_BYTES  = 72;   // bcrypt ignores anything past 72 bytes
const DEFAULT_ROLE        = 'student';

// Hash of a random value; verified against when the e-mail is unknown so that
// "no such user" and "wrong password" take about the same time.
const DUMMY_PASSWORD_HASH = '$2y$10$sOrcxvhvUGuUbIT2EvP0/OgYfqs2f06gALliuknXsBGWoTHR0C11S';

/** @return string[] validation errors (empty when valid) */
function validate_registration(mixed $email, mixed $password): array
{
    $errors = [];
    if (!is_string($email) || strlen($email) > 254 || filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
        $errors[] = 'Enter a valid e-mail address.';
    }
    if (!is_string($password) || mb_strlen($password) < PASSWORD_MIN_LENGTH) {
        $errors[] = 'Password must be at least ' . PASSWORD_MIN_LENGTH . ' characters.';
    } elseif (strlen($password) > PASSWORD_MAX_BYTES) {
        $errors[] = 'Password must be at most ' . PASSWORD_MAX_BYTES . ' bytes.';
    }
    return $errors;
}

/** @return bool false when the e-mail is already registered */
function register_user(PDO $db, string $email, string $password): bool
{
    $stmt = $db->prepare('INSERT INTO users (email, password_hash, role) VALUES (?, ?, ?)');
    try {
        $stmt->execute([$email, password_hash($password, PASSWORD_DEFAULT), DEFAULT_ROLE]);
    } catch (PDOException $e) {
        if ($e->getCode() === '23000') {   // duplicate e-mail
            return false;
        }
        throw $e;
    }
    return true;
}

/** @return array|null the user row (without hash) on success */
function authenticate(PDO $db, mixed $email, mixed $password): ?array
{
    if (!is_string($email) || !is_string($password) || $email === '' || $password === '') {
        return null;
    }
    $stmt = $db->prepare('SELECT id, email, password_hash, role FROM users WHERE email = ?');
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if (!$user) {
        password_verify($password, DUMMY_PASSWORD_HASH);
        return null;
    }
    if (!password_verify($password, $user['password_hash'])) {
        return null;
    }
    if (password_needs_rehash($user['password_hash'], PASSWORD_DEFAULT)) {
        $db->prepare('UPDATE users SET password_hash = ? WHERE id = ?')
           ->execute([password_hash($password, PASSWORD_DEFAULT), $user['id']]);
    }
    unset($user['password_hash']);
    return $user;
}
