<?php

namespace App\Application\Blueprint\UseCases;

use App\Application\Blueprint\Contracts\BlueprintExporter;
use App\Application\Blueprint\Data\ExportedBlueprint;
use App\Application\Blueprint\Services\ResolveBlueprintManifest;

final readonly class ExportBlueprintSolution
{
    public function __construct(
        private ResolveBlueprintManifest $resolver,
        private BlueprintExporter $exporter,
    ) {
        //
    }

    public function handle(array $manifest): ExportedBlueprint
    {
        return $this->exporter->export($this->resolver->handle($manifest));
    }
}
