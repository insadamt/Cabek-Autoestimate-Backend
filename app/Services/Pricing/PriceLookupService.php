<?php

namespace App\Services\Pricing;

use App\Models\PartPriceObservation;
use Illuminate\Database\Eloquent\Builder;

class PriceLookupService
{
    public function observations(array $filters = []): Builder
    {
        $query = PartPriceObservation::query();

        if (isset($filters['marque_id'])) {
            $query->whereHas(
                'vehicleModel',
                fn (Builder $modelQuery): Builder => $modelQuery->where('marque_id', $filters['marque_id']),
            );
        }

        if (isset($filters['model_id'])) {
            $query->where('model_id', $filters['model_id']);
        }

        if (isset($filters['part_id'])) {
            $query->where('part_id', $filters['part_id']);
        }

        if (isset($filters['type'])) {
            $query->where('type', $filters['type']);
        }

        if (isset($filters['source_id'])) {
            $query->where('source_id', $filters['source_id']);
        }

        if (isset($filters['year'])) {
            $this->applyYearCompatibility($query, (int) $filters['year']);
        }

        return $query;
    }

    public function applyYearCompatibility(Builder $query, int $year): Builder
    {
        return $query
            ->where(function (Builder $fromQuery) use ($year): void {
                $fromQuery
                    ->whereNull('production_from')
                    ->orWhere('production_from', '<=', $year);
            })
            ->where(function (Builder $toQuery) use ($year): void {
                $toQuery
                    ->whereNull('production_to')
                    ->orWhere('production_to', '>', $year);
            })
            ->where(function (Builder $knownRangeQuery): void {
                $knownRangeQuery
                    ->whereNotNull('production_from')
                    ->orWhereNotNull('production_to');
            });
    }

    public function statistics(Builder $query): ?array
    {
        $prices = (clone $query)
            ->orderBy('price')
            ->pluck('price')
            ->map(fn ($price): float => (float) $price)
            ->values();

        $count = $prices->count();

        if ($count === 0) {
            return null;
        }

        $middle = intdiv($count, 2);
        $median = $count % 2 === 1
            ? $prices[$middle]
            : ($prices[$middle - 1] + $prices[$middle]) / 2;

        return [
            'median' => round((float) $median, 2),
            'average' => round((float) $prices->average(), 2),
            'minimum' => round((float) $prices->min(), 2),
            'maximum' => round((float) $prices->max(), 2),
            'observation_count' => $count,
        ];
    }
}
