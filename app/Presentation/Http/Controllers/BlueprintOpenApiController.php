<?php

namespace App\Presentation\Http\Controllers;

use App\Application\Blueprint\Queries\GetTraceableMasterOpenApi;
use Illuminate\Http\JsonResponse;

final class BlueprintOpenApiController
{
    public function __invoke(GetTraceableMasterOpenApi $query): JsonResponse
    {
        return response()->json($query->handle());
    }
}
