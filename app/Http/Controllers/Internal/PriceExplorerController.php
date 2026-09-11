<?php

namespace App\Http\Controllers\Internal;

use App\Http\Controllers\Controller;
use App\Models\Marque;
use App\Models\Part;
use App\Models\PartPriceObservation;
use App\Models\Source;
use App\Models\VehicleModel;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class PriceExplorerController extends Controller
{
    public function index(Request $request): View
    {
        $filters = $request->validate([
            'marque' => ['nullable', 'integer', 'exists:marques,id'],
            'model' => ['nullable', 'integer', 'exists:models,id'],
            'part' => ['nullable', 'integer', 'exists:parts,id'],
            'q' => ['nullable', 'string', 'max:100'],
            'year' => ['nullable', 'integer', 'between:1900,'.(now()->year + 1)],
            'type' => ['nullable', Rule::in(['original', 'adaptable', 'occasion'])],
            'source_id' => ['nullable', 'integer', 'exists:sources,id'],
        ]);

        $search = trim((string) ($filters['q'] ?? ''));

        $marques = Marque::query()
            ->withCount('models')
            ->when(
                ! isset($filters['marque']) && $search !== '',
                fn ($query) => $query->where('libelle', 'like', '%'.$search.'%'),
            )
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
                ->when(
                    ! isset($filters['model']) && $search !== '',
                    fn ($query) => $query->where('libelle', 'like', '%'.$search.'%'),
                )
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
            ? $this->partsForModel(
                $selectedModel,
                ! isset($filters['part']) ? $search : '',
            )
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

                $query
                    ->where(function ($fromQuery) use ($year): void {
                        $fromQuery
                            ->whereNull('production_from')
                            ->orWhere('production_from', '<=', $year);
                    })
                    ->where(function ($toQuery) use ($year): void {
                        $toQuery
                            ->whereNull('production_to')
                            ->orWhere('production_to', '>', $year);
                    })
                    ->where(function ($knownRangeQuery): void {
                        $knownRangeQuery
                            ->whereNotNull('production_from')
                            ->orWhereNotNull('production_to');
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
                ->withQueryString();
        }

        return view('internal.explorer.index', compact(
            'marques',
            'models',
            'parts',
            'sources',
            'selectedMarque',
            'selectedModel',
            'selectedPart',
            'statistics',
            'observations',
            'filters',
        ));
    }

    private function partsForModel(VehicleModel $model, string $search = ''): Collection
    {
        return Part::query()
            ->select('parts.id', 'parts.libelle')
            ->selectRaw('COUNT(part_price_observations.id) AS observations_count')
            ->join('part_price_observations', 'part_price_observations.part_id', '=', 'parts.id')
            ->where('part_price_observations.model_id', $model->id)
            ->when(
                $search !== '',
                fn ($query) => $query->where('parts.libelle', 'like', '%'.$search.'%'),
            )
            ->groupBy('parts.id', 'parts.libelle')
            ->orderBy('parts.libelle')
            ->get();
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
