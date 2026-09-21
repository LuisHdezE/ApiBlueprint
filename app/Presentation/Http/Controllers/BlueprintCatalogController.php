<?php

namespace App\Presentation\Http\Controllers;

use App\Application\Blueprint\Queries\GetBlueprintCatalog;
use Illuminate\Http\JsonResponse;

final class BlueprintCatalogController
{
    public function __invoke(GetBlueprintCatalog $query): JsonResponse
    {
        return response()->json($query->handle());
    }
}
