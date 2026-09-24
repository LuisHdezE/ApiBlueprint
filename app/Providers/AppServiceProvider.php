<?php

namespace App\Providers;

use App\Application\Blueprint\Contracts\BlueprintCatalog;
use App\Application\Blueprint\Contracts\BlueprintExporter;
use App\Infrastructure\Blueprint\ArchitectureConformanceBlueprintExporter;
use App\Infrastructure\Blueprint\ConfigBlueprintCatalog;
use App\Infrastructure\Blueprint\LaravelZipBlueprintExporter;
use App\Infrastructure\Blueprint\OperationalContractBlueprintExporter;
use App\Infrastructure\Blueprint\Recipes\AuthLoginRecipe;
use App\Infrastructure\Blueprint\Recipes\AuthLogoutRecipe;
use App\Infrastructure\Blueprint\Recipes\LaravelFeatureRecipeRegistry;
use App\Infrastructure\Blueprint\Recipes\ListQuerySupportRecipe;
use App\Infrastructure\Blueprint\Recipes\ProductsListRecipe;
use App\Infrastructure\Blueprint\Recipes\ProductsSharedRecipe;
use App\Infrastructure\Blueprint\Recipes\ProductsShowRecipe;
use App\Infrastructure\Blueprint\Recipes\UsersCreateRecipe;
use App\Infrastructure\Blueprint\Recipes\UsersListRecipe;
use App\Infrastructure\Blueprint\Recipes\UsersSharedRecipe;
use App\Infrastructure\Blueprint\Recipes\UsersShowRecipe;
use Illuminate\Support\ServiceProvider;

final class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(BlueprintCatalog::class, ConfigBlueprintCatalog::class);
        $this->app->singleton(
            LaravelFeatureRecipeRegistry::class,
            static fn (): LaravelFeatureRecipeRegistry => new LaravelFeatureRecipeRegistry([
                new AuthLoginRecipe,
                new AuthLogoutRecipe,
                new UsersSharedRecipe,
                new UsersListRecipe,
                new UsersShowRecipe,
                new UsersCreateRecipe,
                new ProductsSharedRecipe,
                new ProductsShowRecipe,
                new ProductsListRecipe,
                new ListQuerySupportRecipe,
            ]),
        );
        $this->app->bind(
            BlueprintExporter::class,
            fn ($app) => new OperationalContractBlueprintExporter(
                new ArchitectureConformanceBlueprintExporter(
                    $app->make(LaravelZipBlueprintExporter::class),
                ),
                $app->make(BlueprintCatalog::class),
            ),
        );
    }

    public function boot(): void {}
}
