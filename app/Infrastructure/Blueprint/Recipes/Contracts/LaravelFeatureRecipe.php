<?php

namespace App\Infrastructure\Blueprint\Recipes\Contracts;

interface LaravelFeatureRecipe
{
    public function id(): string;

    public function endpointIds(): array;

    public function matches(array $manifest): bool;

    public function files(array $manifest): array;

    public function controller(array $endpoint, string $className): ?string;

    public function serviceProviderImports(): array;

    public function serviceProviderRegistrations(): array;

    public function composerRequireDev(): array;
}
