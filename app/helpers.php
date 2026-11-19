<?php
<<<<<<< HEAD

declare(strict_types=1);

use App\Services\CartService;
use App\Services\CurrencyService;

/*
|--------------------------------------------------------------------------
| Application-specific helpers.
|--------------------------------------------------------------------------
*/

/** The currency the visitor chose in the header switcher. */
function current_currency(): string
{
    $currency = session()->get('currency', config('app.currency.base'));
    return in_array($currency, (array) config('app.currency.supported'), true) ? $currency : 'PHP';
}

/**
 * Formats a base-currency (PHP) amount in the visitor's currency, converting
 * with live Frankfurter rates.
 */
function money(float $amountInBase, ?string $currency = null): string
{
    $currency ??= current_currency();
    return app(CurrencyService::class)->format($amountInBase, $currency);
}

/** Always formats in pesos — used for stored order totals. */
function peso(float $amount): string
{
    return '₱' . number_format($amount, 2);
}

function cart_count(): int
{
    try {
        return app(CartService::class)->count();
    } catch (Throwable) {
        return 0;
    }
}

/**
 * "Ransom note" lettering: every character gets its own cut-out style
 * (font, colours, tilt, size). Deterministic per text, so headings never
 * jump between page loads. Screen readers get the plain text.
 */
function ransom(string $text, string $class = ''): string
{
    // Letters are grouped per word so lines only wrap between words.
    $letters = '<span class="rn-word">';
    $chars = mb_str_split(mb_strtoupper($text));
    foreach ($chars as $i => $char) {
        if ($char === ' ') {
            $letters .= '</span><span class="rn-gap"></span><span class="rn-word">';
            continue;
        }
        $hash = crc32($text . $i . $char);
        $variant = $i === 0 ? 2 : $hash % 6;
        $rotate = (($hash >> 4) % 13) - 6;
        $scale = 0.88 + ((($hash >> 8) % 25) / 100);
        $lift = (($hash >> 12) % 7) - 3;
        $letters .= sprintf(
            '<span class="rn rn-v%d" style="--r:%ddeg;--s:%.2f;--y:%dpx">%s</span>',
            $variant,
            $rotate,
            $scale,
            $lift,
            e($char)
        );
    }
    $letters .= '</span>';
    return '<span class="ransom ' . e($class) . '" role="text" aria-label="' . e($text) . '"><span aria-hidden="true">' . $letters . '</span></span>';
}

/** Star rating as filled/empty stars. */
function stars(float $rating): string
{
    $full = (int) round($rating);
    return '<span class="stars" aria-label="' . e(number_format($rating, 1)) . ' out of 5">'
        . str_repeat('<i class="star on">★</i>', $full)
        . str_repeat('<i class="star">★</i>', max(0, 5 - $full))
        . '</span>';
}

function status_badge(string $status): string
{
    return '<span class="badge badge--' . e($status) . '">' . e(strtoupper($status)) . '</span>';
=======
declare(strict_types=1);

/**
 * Global helper functions for Session, CSRF, and Flash Messages.
 */

if (!function_exists('csrf_token')) {
    function csrf_token(): string
    {
        if (session_status() === PHP_SESSION_NONE) {
            @session_start();
        }
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }
}

if (!function_exists('csrf_check')) {
    function csrf_check(): bool
    {
        if (session_status() === PHP_SESSION_NONE) {
            @session_start();
        }
        $token = $_POST['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
        $expected = $_SESSION['csrf_token'] ?? '';
        if (!$token || !$expected || !hash_equals($expected, $token)) {
            return false;
        }
        return true;
    }
}

if (!function_exists('redirect')) {
    function redirect(string $url): void
    {
        if (!headers_sent()) {
            header('Location: ' . $url, true, 302);
        }
        // Avoid hard exit when running inside automated test runners
        if (php_sapi_name() === 'cli' && defined('TESTING_MODE')) {
            return;
        }
        exit;
    }
}

if (!function_exists('flash')) {
    function flash(string $key, ?string $value = null): ?string
    {
        if (session_status() === PHP_SESSION_NONE) {
            @session_start();
        }
        if ($value !== null) {
            $_SESSION['flash'][$key] = $value;
            return null;
        }
        $val = $_SESSION['flash'][$key] ?? null;
        unset($_SESSION['flash'][$key]);
        return $val;
    }
>>>>>>> 4cebc346a79479eeaffbfda9cc62c1eab63ab568
}
