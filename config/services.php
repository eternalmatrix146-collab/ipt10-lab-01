<?php

return [
    // NVIDIA NIM (OpenAI-compatible Chat Completions) — powers the Rook shopping agent,
    // the Rook Intel admin agent and the product copywriter. Up to three keys are rotated
    // to spread requests across their rate limits.
    'nvidia' => [
        // NVIDIA_API_KEY / NVIDIA_API_KEY2 / NVIDIA_API_KEY3 are accepted as alternative names.
        'api_keys' => [
            env('NVIDIA_API_KEY_1') ?: env('NVIDIA_API_KEY', ''),
            env('NVIDIA_API_KEY_2') ?: env('NVIDIA_API_KEY2', ''),
            env('NVIDIA_API_KEY_3') ?: env('NVIDIA_API_KEY3', ''),
        ],
        'base_url' => env('NVIDIA_BASE_URL', 'https://integrate.api.nvidia.com/v1'),
        'status_url' => env('NVIDIA_STATUS_URL', 'https://api.nvcf.nvidia.com/v2/nvcf/pexec/status'),
        'model' => env('NVIDIA_MODEL', 'nvidia/nemotron-3-ultra-550b-a55b'),
        'reasoning_effort' => env('NVIDIA_REASONING_EFFORT', 'none'), // none (fastest, default) | medium | high
        'max_tokens' => (int) env('NVIDIA_MAX_TOKENS', 8192),
        'timeout' => (int) env('NVIDIA_TIMEOUT', 60), // a stuck request moves on to the next key
        'max_agent_steps' => 8,
    ],

    // Frankfurter — free, key-less exchange-rate API (European Central Bank data).
    'frankfurter' => [
        'base_url' => env('FRANKFURTER_URL', 'https://api.frankfurter.dev/v1'),
        'cache_minutes' => 360,
    ],
];
