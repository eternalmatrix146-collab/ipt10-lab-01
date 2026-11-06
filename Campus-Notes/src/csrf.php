<?php
/**
 * CSRF and origin checks (issues 5 and 11).
 *
 * - One random token per session, compared in constant time.
 * - Web forms send it as a hidden field, the JSON API as an X-CSRF-Token header.
 * - Requests whose Origin / Sec-Fetch-Site show another site are rejected.
 *   SameSite cookies alone are not enough: http://localhost:3000 and
 *   http://localhost:8000 are the *same site* but different origins.
 */
declare(strict_types=1);

function csrf_token(): string
{
    if (empty($_SESSION['csrf_token']) || !is_string($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function csrf_is_valid(mixed $submitted): bool
{
    return is_string($submitted)
        && isset($_SESSION['csrf_token'])
        && is_string($_SESSION['csrf_token'])
        && hash_equals($_SESSION['csrf_token'], $submitted);
}

/** The single origin this app is served from, e.g. http://127.0.0.1:8000 */
function app_origin(): string
{
    $configured = getenv('LAB_APP_ORIGIN');
    if (is_string($configured) && $configured !== '') {
        return rtrim($configured, '/');
    }
    $scheme = request_is_https() ? 'https' : 'http';
    return $scheme . '://' . ($_SERVER['HTTP_HOST'] ?? '127.0.0.1:8000');
}

/**
 * False when the browser tells us the request came from another origin.
 * Requests with no Origin header (e.g. top-level GET, curl) are allowed here;
 * state-changing requests still need the CSRF token.
 */
function request_origin_is_trusted(): bool
{
    $fetchSite = $_SERVER['HTTP_SEC_FETCH_SITE'] ?? null;
    if ($fetchSite !== null && !in_array($fetchSite, ['same-origin', 'none'], true)) {
        return false;
    }
    $origin = $_SERVER['HTTP_ORIGIN'] ?? null;
    if ($origin !== null && $origin !== app_origin()) {
        return false;
    }
    return true;
}
