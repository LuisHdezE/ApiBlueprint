<?php

namespace App\Providers;

use App\Application\Blueprint\Contracts\BlueprintCatalog;
use App\Infrastructure\Blueprint\ConfigBlueprintCatalog;
use Illuminate\Support\ServiceProvider;

final class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(BlueprintCatalog::class, ConfigBlueprintCatalog::class);
    }

    public function boot(): void {}
}
