<?php

namespace App\Application\Blueprint\Contracts;

use App\Application\Blueprint\Data\ExportedBlueprint;

interface BlueprintExporter
{
    /**
     * @param array<string, mixed> $manifest
     */
    public function export(array $manifest): ExportedBlueprint;
}
