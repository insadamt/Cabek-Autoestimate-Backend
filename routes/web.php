<?php

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
});
