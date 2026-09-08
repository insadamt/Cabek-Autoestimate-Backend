<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StorePartPriceObservationsRequest;
use App\Services\PartPriceObservationIngestor;
use Illuminate\Http\JsonResponse;

class IngestPartPriceObservationsController extends Controller
{
    public function __invoke(
        StorePartPriceObservationsRequest $request,
        PartPriceObservationIngestor $ingestor,
    ): JsonResponse {
        $result = $ingestor->ingest($request->validatedObservations());

        return response()->json([
            'message' => 'Observations ingested successfully.',
            'data' => $result,
        ], $result['inserted'] > 0 ? 201 : 200);
    }
}
