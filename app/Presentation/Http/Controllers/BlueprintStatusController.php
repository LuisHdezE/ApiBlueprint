<?php

namespace App\Presentation\Http\Controllers;

use Illuminate\Http\JsonResponse;

final class BlueprintStatusController
{
    public function __invoke(): JsonResponse
    {
        return response()->json([
            'name' => 'ApiBlueprint',
            'status' => 'ok',
            'message' => 'ApiBlueprint está operativo.',
            'api_version' => 'v1',
            'blueprint_schema' => '0.3',
        ]);
    }
}
