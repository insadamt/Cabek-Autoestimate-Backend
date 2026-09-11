<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\PartIndexRequest;
use App\Models\Part;
use Illuminate\Http\JsonResponse;

class PartController extends Controller
{
    public function index(PartIndexRequest $request): JsonResponse
    {
        $filters = $request->validated();

        $parts = Part::query()
            ->when(
                isset($filters['model_id']),
                fn ($query) => $query->whereHas(
                    'priceObservations',
                    fn ($observationQuery) => $observationQuery->where('model_id', $filters['model_id']),
                ),
            )
            ->orderBy('libelle')
            ->get(['id', 'libelle'])
            ->map(fn (Part $part): array => [
                'id' => $part->id,
                'libelle' => $part->libelle,
            ]);

        return response()->json(['data' => $parts]);
    }
}
