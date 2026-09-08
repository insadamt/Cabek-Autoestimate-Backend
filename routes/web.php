<?php

use App\Http\Controllers\Internal\PriceExplorerController;
use Illuminate\Support\Facades\Route;

Route::get('/', fn () => redirect()->route('internal.explorer'));

Route::prefix('internal')->name('internal.')->group(function (): void {
    Route::get('/explorer', [PriceExplorerController::class, 'index'])->name('explorer');
    Route::get('/explorer/marques/{marque}/models', [PriceExplorerController::class, 'models'])
        ->name('explorer.models');
    Route::get('/explorer/models/{model}/parts', [PriceExplorerController::class, 'parts'])
        ->name('explorer.parts');
});
