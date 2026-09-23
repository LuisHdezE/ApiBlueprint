<?php

namespace App\Presentation\Http\Controllers;

use App\Application\Blueprint\Queries\GetMasterOpenApi;
use Illuminate\Http\JsonResponse;

final class BlueprintOpenApiController
{
    public function __invoke(GetMasterOpenApi $query): JsonResponse
    {
        return response()->json($query->handle());
    }
}
