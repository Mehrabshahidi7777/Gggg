<?php

use Illuminate\Support\Facades\Application;

return [
    'name' => env('APP_NAME', 'سازمت'),
    'env' => env('APP_ENV', 'production'),
    'debug' => (bool) env('APP_DEBUG', false),
    'url' => env('APP_URL', 'http://localhost'),
    'timezone' => 'Asia/Tehran',
    'locale' => env('APP_LOCALE', 'fa'),
    'fallback_locale' => env('APP_FALLBACK_LOCALE', 'fa'),
    'faker_locale' => env('APP_FAKER_LOCALE', 'fa_IR'),
    'key' => env('APP_KEY'),
    'cipher' => 'AES-256-CBC',
    'previous_keys' => [
        ...array_filter(explode(',', (string) env('APP_PREVIOUS_KEYS', ''))),
    ],
    'maintenance' => [
        'driver' => 'file',
        'store' => 'database',
    ],
];
