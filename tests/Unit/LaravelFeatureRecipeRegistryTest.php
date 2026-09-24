<?php

namespace Tests\Unit;

use App\Infrastructure\Blueprint\Recipes\AbstractLaravelFeatureRecipe;
use App\Infrastructure\Blueprint\Recipes\LaravelFeatureRecipeRegistry;
use LogicException;
use Tests\TestCase;

final class LaravelFeatureRecipeRegistryTest extends TestCase
{
    public function test_application_registers_feature_and_support_recipes_in_stable_order(): void
    {
        $registry = $this->app->make(LaravelFeatureRecipeRegistry::class);

        $this->assertSame([
            'auth.login',
            'auth.logout',
            'support.users',
            'users.list',
            'users.show',
            'users.create',
            'support.products',
            'products.show',
            'products.list',
            'support.list-query',
        ], $registry->ids());
    }

    public function test_conflicting_recipe_files_are_rejected_instead_of_silently_overwritten(): void
    {
        $first = new class extends AbstractLaravelFeatureRecipe
        {
            public function id(): string
            {
                return 'test.first';
            }

            public function endpointIds(): array
            {
                return ['products.list'];
            }

            public function files(array $manifest): array
            {
                return ['same.php' => 'first'];
            }
        };
        $second = new class extends AbstractLaravelFeatureRecipe
        {
            public function id(): string
            {
                return 'test.second';
            }

            public function endpointIds(): array
            {
                return ['products.list'];
            }

            public function files(array $manifest): array
            {
                return ['same.php' => 'second'];
            }
        };

        $registry = new LaravelFeatureRecipeRegistry([$first, $second]);

        $this->expectException(LogicException::class);
        $registry->files(['endpoints' => [['id' => 'products.list']]]);
    }
}
