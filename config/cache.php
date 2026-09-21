<?php

use Illuminate\Support\Str;

return [
    'default' => env('CACHE_STORE', 'array'),
    'stores' => [
        'array' => ['driver' => 'array', 'serialize' => false],
        'file' => ['driver' => 'file', 'path' => storage_path('framework/cache/data')],
    ],
    'prefix' => env('CACHE_PREFIX', Str::slug((string) env('APP_NAME', 'api-blueprint')).'-cache-'),
];
