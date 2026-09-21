<?php

namespace App\Application\Blueprint\Contracts;

interface BlueprintCatalog
{
    /**
     * @return array<string, mixed>
     */
    public function get(): array;
}
