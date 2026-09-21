<?php

namespace App\Infrastructure\Blueprint;

use App\Application\Blueprint\Contracts\BlueprintCatalog;

final class ConfigBlueprintCatalog implements BlueprintCatalog
{
    public function get(): array
    {
        return config('blueprint');
    }
}
