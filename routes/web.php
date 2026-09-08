<?php

use App\Http\Controllers\Internal\JsonIngestorController;
use App\Http\Controllers\Internal\PriceExplorerController;
use App\Http\Controllers\Internal\StatisticsController;
use Illuminate\Support\Facades\Route;

Route::get('/', fn () => redirect()->route('internal.statistics'));
Route::get('/internal', fn () => redirect()->route('internal.statistics'));

Route::prefix('internal')->name('internal.')->group(function (): void {
    Route::get('/statistics', [StatisticsController::class, 'index'])
        ->name('statistics');

    Route::get('/explorer', [PriceExplorerController::class, 'index'])
        ->name('explorer');

    Route::get('/ingest', [JsonIngestorController::class, 'index'])
        ->name('ingest');
    Route::post('/ingest/validate', [JsonIngestorController::class, 'validateUpload'])
        ->name('ingest.validate');
    Route::post('/ingest', [JsonIngestorController::class, 'ingestUpload'])
        ->name('ingest.store');
});
