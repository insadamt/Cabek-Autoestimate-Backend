<?php

use App\Http\Controllers\Api\IngestPartPriceObservationsController;
use App\Http\Middleware\EnsureJsonRequest;
use Illuminate\Support\Facades\Route;

Route::post('/ingest', IngestPartPriceObservationsController::class)
    ->middleware(EnsureJsonRequest::class);
