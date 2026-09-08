<?php

namespace App\Http\Controllers\Internal;

use App\Http\Controllers\Controller;
use App\Models\Marque;
use App\Models\Part;
use App\Models\PartPriceObservation;
use App\Models\Source;
use App\Models\VehicleModel;
use Illuminate\View\View;

class StatisticsController extends Controller
{
    private const LOW_OBSERVATION_THRESHOLD = 5;

    public function index(): View
    {
        $summary = [
            'marques' => Marque::query()->count(),
            'models' => VehicleModel::query()->count(),
            'parts' => Part::query()->count(),
            'sources' => Source::query()->count(),
            'observations' => PartPriceObservation::query()->count(),
        ];

        $activity = [
            'today' => PartPriceObservation::query()
                ->whereDate('created_at', today())
                ->count(),
            'week' => PartPriceObservation::query()
                ->where('created_at', '>=', now()->startOfWeek())
                ->count(),
            'month' => PartPriceObservation::query()
                ->where('created_at', '>=', now()->startOfMonth())
                ->count(),
        ];

        $storedTypeCounts = PartPriceObservation::query()
            ->select('type')
            ->selectRaw('COUNT(*) AS observations_count')
            ->groupBy('type')
            ->pluck('observations_count', 'type');

        $typeCounts = collect(['original', 'adaptable', 'occasion'])
            ->mapWithKeys(fn (string $type): array => [
                $type => (int) ($storedTypeCounts[$type] ?? 0),
            ]);

        $sourceStats = Source::query()
            ->select('sources.id', 'sources.libelle')
            ->leftJoin(
                'part_price_observations',
                'part_price_observations.source_id',
                '=',
                'sources.id'
            )
            ->selectRaw('COUNT(part_price_observations.id) AS observations_count')
            ->groupBy('sources.id', 'sources.libelle')
            ->orderByDesc('observations_count')
            ->orderBy('sources.libelle')
            ->get();

        $topMarques = Marque::query()
            ->select('marques.id', 'marques.libelle')
            ->leftJoin('models', 'models.marque_id', '=', 'marques.id')
            ->leftJoin(
                'part_price_observations',
                'part_price_observations.model_id',
                '=',
                'models.id'
            )
            ->selectRaw('COUNT(part_price_observations.id) AS observations_count')
            ->groupBy('marques.id', 'marques.libelle')
            ->orderByDesc('observations_count')
            ->orderBy('marques.libelle')
            ->limit(10)
            ->get();

        $topModels = VehicleModel::query()
            ->select(
                'models.id',
                'models.libelle',
                'marques.libelle AS marque_libelle'
            )
            ->join('marques', 'marques.id', '=', 'models.marque_id')
            ->leftJoin(
                'part_price_observations',
                'part_price_observations.model_id',
                '=',
                'models.id'
            )
            ->selectRaw('COUNT(part_price_observations.id) AS observations_count')
            ->groupBy('models.id', 'models.libelle', 'marques.libelle')
            ->orderByDesc('observations_count')
            ->orderBy('marques.libelle')
            ->orderBy('models.libelle')
            ->limit(10)
            ->get();

        $topParts = Part::query()
            ->select('parts.id', 'parts.libelle')
            ->leftJoin(
                'part_price_observations',
                'part_price_observations.part_id',
                '=',
                'parts.id'
            )
            ->selectRaw('COUNT(part_price_observations.id) AS observations_count')
            ->groupBy('parts.id', 'parts.libelle')
            ->orderByDesc('observations_count')
            ->orderBy('parts.libelle')
            ->limit(10)
            ->get();

        $modelsWithoutObservations = VehicleModel::query()
            ->whereDoesntHave('priceObservations')
            ->count();

        $modelsWithoutObservationSample = VehicleModel::query()
            ->with('marque:id,libelle')
            ->whereDoesntHave('priceObservations')
            ->orderBy('libelle')
            ->limit(10)
            ->get(['id', 'marque_id', 'libelle']);

        $lowObservationParts = Part::query()
            ->withCount('priceObservations')
            ->get(['id', 'libelle'])
            ->filter(
                fn (Part $part): bool => $part->price_observations_count < self::LOW_OBSERVATION_THRESHOLD
            )
            ->sortBy(
                fn (Part $part): string => sprintf(
                    '%010d-%s',
                    $part->price_observations_count,
                    $part->libelle
                )
            )
            ->values();

        $coverage = [
            'models_without_observations' => $modelsWithoutObservations,
            'parts_with_few_observations' => $lowObservationParts->count(),
            'low_observation_threshold' => self::LOW_OBSERVATION_THRESHOLD,
        ];

        return view('internal.statistics.index', [
            'summary' => $summary,
            'activity' => $activity,
            'typeCounts' => $typeCounts,
            'sourceStats' => $sourceStats,
            'topMarques' => $topMarques,
            'topModels' => $topModels,
            'topParts' => $topParts,
            'coverage' => $coverage,
            'modelsWithoutObservationSample' => $modelsWithoutObservationSample,
            'lowObservationPartSample' => $lowObservationParts->take(10),
        ]);
    }
}
