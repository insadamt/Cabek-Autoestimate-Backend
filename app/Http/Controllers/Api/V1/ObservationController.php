<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\ObservationIndexRequest;
use App\Models\PartPriceObservation;
use App\Services\Pricing\PriceLookupService;
use Illuminate\Http\JsonResponse;

class ObservationController extends Controller
{
    public function __construct(private readonly PriceLookupService $priceLookup)
    {
    }

    public function index(ObservationIndexRequest $request): JsonResponse
    {
        $filters = $request->validated();

        $paginator = $this->priceLookup
            ->observations($filters)
            ->with([
                'vehicleModel:id,marque_id,libelle',
                'vehicleModel.marque:id,libelle',
                'part:id,libelle',
                'source:id,libelle',
            ])
            ->orderByDesc('observed_at')
            ->orderByDesc('id')
            ->paginate((int) ($filters['per_page'] ?? 50));

        return response()->json([
            'data' => collect($paginator->items())
                ->map(fn (PartPriceObservation $observation): array => $this->serialize($observation))
                ->values(),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'per_page' => $paginator->perPage(),
                'last_page' => $paginator->lastPage(),
                'total' => $paginator->total(),
            ],
        ]);
    }

    private function serialize(PartPriceObservation $observation): array
    {
        return [
            'id' => $observation->id,
            'marque' => [
                'id' => $observation->vehicleModel->marque->id,
                'libelle' => $observation->vehicleModel->marque->libelle,
            ],
            'model' => [
                'id' => $observation->vehicleModel->id,
                'libelle' => $observation->vehicleModel->libelle,
            ],
            'part' => [
                'id' => $observation->part->id,
                'libelle' => $observation->part->libelle,
            ],
            'production_from' => $observation->production_from,
            'production_to' => $observation->production_to,
            'type' => $observation->type,
            'price' => (float) $observation->price,
            'source' => [
                'id' => $observation->source->id,
                'libelle' => $observation->source->libelle,
            ],
            'source_reference' => $observation->source_reference,
            'source_url' => $observation->source_url,
            'observed_at' => $observation->observed_at->format('Y-m-d H:i:s'),
        ];
    }
}
