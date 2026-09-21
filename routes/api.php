<?php

use App\Presentation\Http\Controllers\BlueprintCatalogController;
use App\Presentation\Http\Controllers\BlueprintExportController;
use App\Presentation\Http\Controllers\BlueprintResolveController;
use App\Presentation\Http\Controllers\BlueprintStatusController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function (): void {
    Route::get('/meta/status', BlueprintStatusController::class)->name('api.v1.meta.status');
    Route::get('/blueprint/catalog', BlueprintCatalogController::class)->name('api.v1.blueprint.catalog');
    Route::post('/blueprint/resolve', BlueprintResolveController::class)->name('api.v1.blueprint.resolve');
    Route::post('/blueprint/export', BlueprintExportController::class)->name('api.v1.blueprint.export');
});
