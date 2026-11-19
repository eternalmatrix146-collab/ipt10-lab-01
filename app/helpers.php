<?php

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
}
