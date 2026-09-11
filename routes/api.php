<?php

use App\Http\Controllers\Api\V1\EstimationController;
use App\Http\Controllers\Api\V1\MarqueController;
use App\Http\Controllers\Api\V1\ModelController;
use App\Http\Controllers\Api\V1\ObservationController;
use App\Http\Controllers\Api\V1\PartController;
use App\Http\Controllers\Api\V1\SourceController;
use Illuminate\Support\Facades\Route;

// Observation imports remain intentionally CLI-only.
// The production API is read-only and exposes verified pricing data only.
Route::prefix('v1')
    ->middleware(['api.client', 'throttle:autoestimate-api'])
    ->group(function (): void {
        Route::get('/marques', [MarqueController::class, 'index']);
        Route::get('/models', [ModelController::class, 'index']);
        Route::get('/parts', [PartController::class, 'index']);
        Route::get('/sources', [SourceController::class, 'index']);
        Route::get('/estimation', [EstimationController::class, 'show']);
        Route::get('/observations', [ObservationController::class, 'index']);
    });
