<?php

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Route;

Route::get('/v1/meta/status', static fn (): JsonResponse => response()->json([
    'name' => 'ApiBlueprint',
    'status' => 'ok',
    'api_version' => 'v1',
]));
