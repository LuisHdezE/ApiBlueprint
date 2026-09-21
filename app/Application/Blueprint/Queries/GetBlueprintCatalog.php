<?php

namespace App\Application\Blueprint\Queries;

use App\Application\Blueprint\Contracts\BlueprintCatalog;

final readonly class GetBlueprintCatalog
{
    public function __construct(private BlueprintCatalog $catalog) {}

    /**
     * @return array<string, mixed>
     */
    public function handle(): array
    {
        return $this->catalog->get();
    }
}
