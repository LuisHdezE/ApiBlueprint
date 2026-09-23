<?php

namespace App\Providers;

use App\Application\Blueprint\Contracts\BlueprintCatalog;
use App\Application\Blueprint\Contracts\BlueprintExporter;
use App\Infrastructure\Blueprint\ArchitectureConformanceBlueprintExporter;
use App\Infrastructure\Blueprint\ConfigBlueprintCatalog;
use App\Infrastructure\Blueprint\LaravelZipBlueprintExporter;
use App\Infrastructure\Blueprint\OperationalContractBlueprintExporter;
use Illuminate\Support\ServiceProvider;

final class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(BlueprintCatalog::class, ConfigBlueprintCatalog::class);
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
