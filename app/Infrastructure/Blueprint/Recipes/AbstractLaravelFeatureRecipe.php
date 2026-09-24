<?php

namespace App\Infrastructure\Blueprint\Recipes;

use App\Infrastructure\Blueprint\Recipes\Contracts\LaravelFeatureRecipe;

abstract class AbstractLaravelFeatureRecipe implements LaravelFeatureRecipe
{
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
