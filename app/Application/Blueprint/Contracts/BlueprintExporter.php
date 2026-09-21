<?php

namespace App\Application\Blueprint\Contracts;

use App\Application\Blueprint\Data\ExportedBlueprint;

interface BlueprintExporter
{
    public function export(array $manifest): ExportedBlueprint;
}
