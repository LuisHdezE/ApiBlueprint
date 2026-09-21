<?php

namespace App\Presentation\Http\Controllers;

use App\Application\Blueprint\Exceptions\InvalidBlueprintManifest;
use App\Application\Blueprint\UseCases\ExportBlueprintSolution;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

final class BlueprintExportController
{
    public function __invoke(Request $request, ExportBlueprintSolution $exporter): Response
    {
        try {
            $exported = $exporter->handle($request->json()->all());
        } catch (InvalidBlueprintManifest $exception) {
            return response([
                'type' => 'https://eliasworks.uy/problems/invalid-blueprint-manifest',
                'title' => 'Manifest de ApiBlueprint no válido',
                'status' => 422,
                'detail' => 'La solución no puede exportarse hasta corregir la configuración.',
                'errors' => $exception->errors(),
            ], 422, ['Content-Type' => 'application/problem+json']);
        }

        return response($exported->content, 200, [
            'Content-Type' => $exported->mimeType,
            'Content-Disposition' => 'attachment; filename="'.$exported->filename.'"',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }
}
