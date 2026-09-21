<?php

namespace App\Presentation\Http\Controllers;

use App\Application\Blueprint\Exceptions\InvalidBlueprintManifest;
use App\Application\Blueprint\Services\ResolveBlueprintManifest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class BlueprintResolveController
{
    public function __invoke(Request $request, ResolveBlueprintManifest $resolver): JsonResponse
    {
        try {
            return response()->json($resolver->handle($request->json()->all()));
        } catch (InvalidBlueprintManifest $exception) {
            return response()->json([
                'type' => 'https://eliasworks.uy/problems/invalid-blueprint-manifest',
                'title' => 'Manifest de ApiBlueprint no válido',
                'status' => 422,
                'detail' => 'La configuración enviada contiene errores y no puede resolverse.',
                'errors' => $exception->errors(),
            ], 422, ['Content-Type' => 'application/problem+json']);
        }
    }
}
