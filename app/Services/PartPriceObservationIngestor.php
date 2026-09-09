<?php

namespace App\Services;

use App\Models\Marque;
use App\Models\Part;
use App\Models\PartPriceObservation;
use App\Models\Source;
use App\Models\VehicleModel;
use Illuminate\Support\Facades\DB;

class PartPriceObservationIngestor
{
    private array $marqueIds = [];

    private array $modelIds = [];

    private array $partIds = [];

    private array $sourceIds = [];

    public function ingest(array $observations): array
    {
        return DB::transaction(function () use ($observations): array {
            $insertedIds = [];
            $skippedDuplicates = 0;

            foreach ($observations as $observation) {
                $marqueId = $this->marqueId($observation['marque']);
                $modelId = $this->modelId($marqueId, $observation['model']);
                $partId = $this->partId($observation['part']);
                $sourceId = $this->sourceId($observation['source']);

                $identity = [
                    'model_id' => $modelId,
                    'part_id' => $partId,
                    'source_id' => $sourceId,
                    'production_from' => $observation['production_from'] ?? null,
                    'production_to' => $observation['production_to'] ?? null,
                    'type' => $observation['type'],
                    'price' => $observation['price'],
                    'source_reference' => $observation['source_reference'] ?? null,
                    'source_url' => $observation['source_url'] ?? null,
                    'observed_at' => $observation['observed_at'],
                ];

                if (PartPriceObservation::query()->where($identity)->exists()) {
                    $skippedDuplicates++;
                    continue;
                }

                $created = PartPriceObservation::create($identity);
                $insertedIds[] = $created->id;
            }

            return [
                'received' => count($observations),
                'inserted' => count($insertedIds),
                'skipped_duplicates' => $skippedDuplicates,
                'inserted_ids' => $insertedIds,
            ];
        });
    }

    private function marqueId(string $libelle): int
    {
        return $this->marqueIds[$libelle] ??= Marque::query()
            ->where('libelle', $libelle)
            ->firstOrFail()
            ->id;
    }

    private function modelId(int $marqueId, string $libelle): int
    {
        $key = $marqueId."\0".$libelle;

        return $this->modelIds[$key] ??= VehicleModel::query()
            ->where('marque_id', $marqueId)
            ->where('libelle', $libelle)
            ->firstOrFail()
            ->id;
    }

    private function partId(string $libelle): int
    {
        return $this->partIds[$libelle] ??= Part::query()
            ->where('libelle', $libelle)
            ->firstOrFail()
            ->id;
    }

    private function sourceId(string $libelle): int
    {
        return $this->sourceIds[$libelle] ??= Source::query()
            ->where('libelle', $libelle)
            ->firstOrFail()
            ->id;
    }
}
