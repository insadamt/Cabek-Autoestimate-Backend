<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\EstimationRequest;
use App\Models\Marque;
use App\Models\Part;
use App\Models\Source;
use App\Models\VehicleModel;
use App\Services\Pricing\PriceLookupService;
use Illuminate\Http\JsonResponse;

class EstimationController extends Controller
{
    public function __construct(private readonly PriceLookupService $priceLookup)
    {
    }

    public function show(EstimationRequest $request): JsonResponse
    {
        $filters = $request->validated();

        $statistics = $this->priceLookup->statistics(
            $this->priceLookup->observations($filters),
        );

        if ($statistics === null) {
            return response()->json(['data' => null]);
        }

        $marque = Marque::query()->findOrFail($filters['marque_id']);
        $model = VehicleModel::query()->findOrFail($filters['model_id']);
        $part = Part::query()->findOrFail($filters['part_id']);
        $source = isset($filters['source_id'])
            ? Source::query()->findOrFail($filters['source_id'])
            : null;

        return response()->json([
            'data' => [
                'marque' => [
                    'id' => $marque->id,
                    'libelle' => $marque->libelle,
                ],
                'model' => [
                    'id' => $model->id,
                    'libelle' => $model->libelle,
                ],
                'part' => [
                    'id' => $part->id,
                    'libelle' => $part->libelle,
                ],
                'type' => $filters['type'],
                'year' => isset($filters['year']) ? (int) $filters['year'] : null,
                'source' => $source ? [
                    'id' => $source->id,
                    'libelle' => $source->libelle,
                ] : null,
                'estimation' => $statistics,
            ],
        ]);
    }
}
