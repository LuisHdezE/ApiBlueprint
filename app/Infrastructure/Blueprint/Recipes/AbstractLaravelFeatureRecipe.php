<?php

namespace App\Infrastructure\Blueprint\Recipes;

use App\Infrastructure\Blueprint\Recipes\Contracts\LaravelFeatureRecipe;

abstract class AbstractLaravelFeatureRecipe implements LaravelFeatureRecipe
{
    public function matches(array $manifest): bool
    {
        $endpointIds = array_fill_keys($this->endpointIds(), true);

        foreach ($manifest['endpoints'] ?? [] as $endpoint) {
            if (is_array($endpoint) && isset($endpointIds[$endpoint['id'] ?? null])) {
                return true;
            }
        }

        return false;
    }

    public function files(array $manifest): array
    {
        return [];
    }

    public function controller(array $endpoint, string $className): ?string
    {
        return null;
    }

    public function serviceProviderImports(): array
    {
        return [];
    }

    public function serviceProviderRegistrations(): array
    {
        return [];
    }

    public function composerRequireDev(): array
    {
        return [];
    }
}
