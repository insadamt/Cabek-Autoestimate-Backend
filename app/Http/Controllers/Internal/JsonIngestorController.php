<?php

namespace App\Http\Controllers\Internal;

use App\Http\Controllers\Controller;
use App\Http\Requests\StorePartPriceObservationsRequest;
use App\Services\PartPriceObservationIngestor;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;

class JsonIngestorController extends Controller
{
    public function index(): View
    {
        return view('internal.ingest.index');
    }

    public function validatePayload(StorePartPriceObservationsRequest $request): JsonResponse
    {
        $observations = $request->validatedObservations();

        return response()->json([
            'message' => 'JSON payload is valid.',
            'data' => [
                'received' => count($observations),
                'observations' => $observations,
            ],
        ]);
    }

    public function ingest(
        StorePartPriceObservationsRequest $request,
        PartPriceObservationIngestor $ingestor,
    ): JsonResponse {
        $result = $ingestor->ingest($request->validatedObservations());

        return response()->json([
            'message' => 'Observations ingested successfully.',
            'data' => $result,
        ]);
    }
}
