<?php

namespace App\Infrastructure\Blueprint\Recipes;

use App\Infrastructure\Blueprint\Recipes\Contracts\LaravelFeatureRecipe;
use LogicException;

final class LaravelFeatureRecipeRegistry
{
    /** @var list<LaravelFeatureRecipe> */
    private array $recipes;

    /** @param iterable<LaravelFeatureRecipe> $recipes */
    public function __construct(iterable $recipes)
    {
        $this->recipes = [];
        $ids = [];

        foreach ($recipes as $recipe) {
            if (isset($ids[$recipe->id()])) {
                throw new LogicException('Duplicate Laravel feature recipe id: '.$recipe->id());
            }

            $ids[$recipe->id()] = true;
            $this->recipes[] = $recipe;
        }
    }

    public function ids(): array
    {
        return array_map(static fn (LaravelFeatureRecipe $recipe): string => $recipe->id(), $this->recipes);
    }

    public function files(array $manifest): array
    {
        $files = [];

        foreach ($this->selected($manifest) as $recipe) {
            foreach ($recipe->files($manifest) as $path => $content) {
                if (array_key_exists($path, $files) && $files[$path] !== $content) {
                    throw new LogicException('Conflicting generated file recipe for path: '.$path);
                }

                $files[$path] = $content;
            }
        }

        return $files;
    }

    public function controller(array $endpoint, string $className): ?string
    {
        $controller = null;

        foreach ($this->recipes as $recipe) {
            if (! in_array($endpoint['id'] ?? null, $recipe->endpointIds(), true)) {
                continue;
            }

            $candidate = $recipe->controller($endpoint, $className);
            if ($candidate === null) {
                continue;
            }

            if ($controller !== null) {
                throw new LogicException('Multiple controller recipes matched endpoint: '.($endpoint['id'] ?? 'unknown'));
            }

            $controller = $candidate;
        }

        return $controller;
    }

    public function serviceProviderImports(array $manifest): array
    {
        $imports = [];
        foreach ($this->selected($manifest) as $recipe) {
            $imports = array_merge($imports, $recipe->serviceProviderImports());
        }

        return array_values(array_unique($imports));
    }

    public function serviceProviderRegistrations(array $manifest): array
    {
        $registrations = [];
        foreach ($this->selected($manifest) as $recipe) {
            $registrations = array_merge($registrations, $recipe->serviceProviderRegistrations());
        }

        return array_values(array_unique($registrations));
    }

    public function composerRequireDev(array $manifest): array
    {
        $requirements = [];
        foreach ($this->selected($manifest) as $recipe) {
            foreach ($recipe->composerRequireDev() as $package => $constraint) {
                if (isset($requirements[$package]) && $requirements[$package] !== $constraint) {
                    throw new LogicException('Conflicting Composer dev requirement recipe for package: '.$package);
                }

                $requirements[$package] = $constraint;
            }
        }

        return $requirements;
    }

    /** @return list<LaravelFeatureRecipe> */
    private function selected(array $manifest): array
    {
        return array_values(array_filter(
            $this->recipes,
            static fn (LaravelFeatureRecipe $recipe): bool => $recipe->matches($manifest),
        ));
    }
}
