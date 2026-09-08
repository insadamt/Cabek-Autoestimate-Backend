<?php

namespace App\Http\Controllers\Internal;

use App\Http\Controllers\Controller;
use App\Models\Marque;
use App\Models\Part;
use App\Models\PartPriceObservation;
use App\Models\Source;
use App\Models\VehicleModel;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class PriceExplorerController extends Controller
{
    public function index(Request $request): Response
    {
        $filters = $request->validate([
            'marque' => ['nullable', 'integer', 'exists:marques,id'],
            'model' => ['nullable', 'integer', 'exists:models,id'],
            'part' => ['nullable', 'integer', 'exists:parts,id'],
            'year' => ['nullable', 'integer', 'between:1900,'.(now()->year + 1)],
            'type' => ['nullable', Rule::in(['original', 'adaptable', 'occasion'])],
            'source_id' => ['nullable', 'integer', 'exists:sources,id'],
        ]);

        $marques = Marque::query()
            ->withCount('models')
            ->orderBy('libelle')
            ->get(['id', 'libelle']);

        $sources = Source::query()
            ->orderBy('libelle')
            ->get(['id', 'libelle']);

        $selectedMarque = isset($filters['marque'])
            ? Marque::find($filters['marque'])
            : null;

        $models = $selectedMarque
            ? VehicleModel::query()
                ->where('marque_id', $selectedMarque->id)
                ->withCount('priceObservations')
                ->orderBy('libelle')
                ->get(['id', 'libelle'])
            : collect();

        $selectedModel = null;
        if ($selectedMarque && isset($filters['model'])) {
            $selectedModel = VehicleModel::query()
                ->where('id', $filters['model'])
                ->where('marque_id', $selectedMarque->id)
                ->first();
        }

        $parts = $selectedModel
            ? $this->partsForModel($selectedModel)
            : collect();

        $selectedPart = null;
        if ($selectedModel && isset($filters['part'])) {
            $selectedPart = Part::query()
                ->where('id', $filters['part'])
                ->whereHas('priceObservations', fn ($query) => $query->where('model_id', $selectedModel->id))
                ->first();
        }

        $statistics = null;
        $observations = null;

        if ($selectedModel && $selectedPart) {
            $query = PartPriceObservation::query()
                ->with('source:id,libelle')
                ->where('model_id', $selectedModel->id)
                ->where('part_id', $selectedPart->id);

            if (isset($filters['year'])) {
                $year = (int) $filters['year'];

                $query->where(function ($yearQuery) use ($year): void {
                    $yearQuery
                        ->where(function ($knownRange) use ($year): void {
                            $knownRange
                                ->where(function ($from) use ($year): void {
                                    $from->whereNull('production_from')
                                        ->orWhere('production_from', '<=', $year);
                                })
                                ->where(function ($to) use ($year): void {
                                    $to->whereNull('production_to')
                                        ->orWhere('production_to', '>=', $year);
                                });
                        });
                });
            }

            if (isset($filters['type'])) {
                $query->where('type', $filters['type']);
            }

            if (isset($filters['source_id'])) {
                $query->where('source_id', $filters['source_id']);
            }

            $prices = (clone $query)
                ->orderBy('price')
                ->pluck('price')
                ->map(fn ($price): float => (float) $price)
                ->values();

            $statistics = $this->calculateStatistics($prices);

            $observations = (clone $query)
                ->orderByDesc('observed_at')
                ->paginate(50)
                ->withQueryString()
                ->through(fn (PartPriceObservation $observation): array => [
                    'id' => $observation->id,
                    'production_from' => $observation->production_from,
                    'production_to' => $observation->production_to,
                    'type' => $observation->type,
                    'price' => $observation->price,
                    'source_reference' => $observation->source_reference,
                    'source_url' => $observation->source_url,
                    'observed_at' => $observation->observed_at?->toIso8601String(),
                    'source' => $observation->source
                        ? [
                            'id' => $observation->source->id,
                            'libelle' => $observation->source->libelle,
                        ]
                        : null,
                ]);
        }

        return Inertia::render('Internal/PriceExplorer', [
            'marques' => $marques,
            'models' => $models,
            'parts' => $parts,
            'sources' => $sources,
            'selected' => [
                'marque' => $this->vehicleResource($selectedMarque),
                'model' => $this->vehicleResource($selectedModel),
                'part' => $this->vehicleResource($selectedPart),
            ],
            'filters' => [
                'year' => $filters['year'] ?? null,
                'type' => $filters['type'] ?? null,
                'source_id' => $filters['source_id'] ?? null,
            ],
            'statistics' => $statistics,
            'observations' => $observations,
            'maxYear' => now()->year + 1,
        ]);
    }

    public function models(Marque $marque): JsonResponse
    {
        $models = VehicleModel::query()
            ->where('marque_id', $marque->id)
            ->withCount('priceObservations')
            ->orderBy('libelle')
            ->get(['id', 'libelle']);

        return response()->json([
            'data' => $models,
        ]);
    }

    public function parts(VehicleModel $model): JsonResponse
    {
        return response()->json([
            'data' => $this->partsForModel($model),
        ]);
    }

    private function partsForModel(VehicleModel $model): Collection
    {
        return Part::query()
            ->select('parts.id', 'parts.libelle')
            ->selectRaw('COUNT(part_price_observations.id) AS observations_count')
            ->join('part_price_observations', 'part_price_observations.part_id', '=', 'parts.id')
            ->where('part_price_observations.model_id', $model->id)
            ->groupBy('parts.id', 'parts.libelle')
            ->orderBy('parts.libelle')
            ->get();
    }

    private function vehicleResource(Marque|VehicleModel|Part|null $record): ?array
    {
        if (! $record) {
            return null;
        }

        return [
            'id' => $record->id,
            'libelle' => $record->libelle,
        ];
    }

    private function calculateStatistics(Collection $prices): array
    {
        $count = $prices->count();

        if ($count === 0) {
            return [
                'count' => 0,
                'median' => null,
                'average' => null,
                'minimum' => null,
                'maximum' => null,
            ];
        }

        $middle = intdiv($count, 2);
        $median = $count % 2 === 1
            ? $prices[$middle]
            : ($prices[$middle - 1] + $prices[$middle]) / 2;

        return [
            'count' => $count,
            'median' => round($median, 2),
            'average' => round((float) $prices->average(), 2),
            'minimum' => round((float) $prices->min(), 2),
            'maximum' => round((float) $prices->max(), 2),
        ];
    }
}
