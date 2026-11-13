<?php
/**
 * Session handling (issue 6).
 *
 * - Hardened cookie: HttpOnly, SameSite=Strict, Secure when served over HTTPS.
 * - Strict mode: PHP refuses session IDs it did not create.
 * - New session ID on login (prevents session fixation).
 * - Logout destroys the server-side session and expires the cookie.
 * - Idle timeout of 30 minutes.
 */
declare(strict_types=1);

const SESSION_IDLE_SECONDS = 1800;

function request_is_https(): bool
{
    return !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
}

function start_secure_session(): void
{
    if (session_status() === PHP_SESSION_ACTIVE) {
        return;
    }
    $https = request_is_https();

    ini_set('session.use_strict_mode', '1');
    ini_set('session.use_only_cookies', '1');
    ini_set('session.use_trans_sid', '0');
    // Session ID length: PHP's default (32 chars, 128 bits) is used. The old
    // session.sid_length / sid_bits_per_character settings are deprecated in PHP 8.4+.

    // The __Host- prefix is only allowed (and only useful) on HTTPS.
    session_name($https ? '__Host-campus_notes' : 'campus_notes');
    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => '/',
        'secure'   => $https,
        'httponly' => true,
        'samesite' => 'Strict',
    ]);
    session_start();

    $now = time();
    if (isset($_SESSION['last_seen']) && ($now - (int) $_SESSION['last_seen']) > SESSION_IDLE_SECONDS) {
        logout_user();
        session_start();
    }
    $_SESSION['last_seen'] = $now;
}

/** Called only after the password has been verified. */
function login_user(array $user): void
{
    session_regenerate_id(true);           // new ID, old one deleted
    unset($_SESSION['csrf_token']);        // new CSRF token for the new privilege level
    $_SESSION['user_id'] = (int) $user['id'];
    $_SESSION['email']   = (string) $user['email'];
    $_SESSION['role']    = (string) $user['role'];   // read from the DB, never from the request
    $_SESSION['last_seen'] = time();
}

function logout_user(): void
{
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $p = session_get_cookie_params();
        setcookie(session_name(), '', [
            'expires'  => time() - 42000,
            'path'     => $p['path'],
            'secure'   => $p['secure'],
            'httponly' => $p['httponly'],
            'samesite' => $p['samesite'],
        ]);
    }
    session_destroy();
}

function current_user_id(): ?int
{
    $id = $_SESSION['user_id'] ?? null;
    return is_int($id) && $id > 0 ? $id : null;
}
