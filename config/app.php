<?php

return [
    'name' => env('APP_NAME', 'Midnight Market'),
    'env' => env('APP_ENV', 'local'),
    'debug' => (bool) env('APP_DEBUG', false),
    'url' => env('APP_URL', 'http://localhost:8000'),
    'timezone' => env('APP_TIMEZONE', 'Asia/Manila'),
    'session_name' => 'midnight_session',

    // Prices are stored in the base currency; others are converted live via the Frankfurter API.
    'currency' => [
        'base' => 'PHP',
        'supported' => ['PHP', 'USD', 'EUR', 'JPY', 'GBP', 'KRW', 'SGD'],
        'symbols' => ['PHP' => '₱', 'USD' => '$', 'EUR' => '€', 'JPY' => '¥', 'GBP' => '£', 'KRW' => '₩', 'SGD' => 'S$'],
    ],

    'shipping' => [
        'flat_fee' => 150.00,
        'free_over' => 3000.00,
    ],
];
