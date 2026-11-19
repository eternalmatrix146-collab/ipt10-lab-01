<?php
/**
 * Output helpers (issue 4) and HTTP response helpers.
 */
declare(strict_types=1);

/** Escape any value for HTML text or a quoted attribute. Use on EVERY dynamic value. */
function e(mixed $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML5, 'UTF-8');
}

function send_security_headers(): void
{
    header("Content-Security-Policy: default-src 'self'; script-src 'self'; style-src 'self'; "
         . "object-src 'none'; base-uri 'none'; form-action 'self'; frame-ancestors 'none'");
    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: DENY');
    header('Referrer-Policy: same-origin');   // no-referrer would make browsers send "Origin: null" on our own forms
    header('Cache-Control: no-store');
}

/** Store a one-time message in the session instead of putting it in the URL. */
function flash(string $message): void
{
    $_SESSION['flash'][] = $message;
}

/** @return string[] */
function take_flashes(): array
{
    $messages = $_SESSION['flash'] ?? [];
    unset($_SESSION['flash']);
    return is_array($messages) ? $messages : [];
}

function redirect(string $path = '/'): never
{
    header('Location: ' . $path, true, 303);
    exit;
}

function render(string $template, array $vars = [], int $status = 200): never
{
    http_response_code($status);
    header('Content-Type: text/html; charset=utf-8');
    extract($vars, EXTR_SKIP);
    require dirname(__DIR__) . '/templates/' . $template . '.php';
    exit;
}

function json_response(int $status, array $payload): never
{
    http_response_code($status);
    echo json_encode($payload, JSON_UNESCAPED_SLASHES);
    exit;
}
