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
    public function ingest(array $observations): array
    {
        return DB::transaction(function () use ($observations): array {
            $insertedIds = [];
            $skippedDuplicates = 0;

            foreach ($observations as $observation) {
                $marque = Marque::firstOrCreate([
                    'libelle' => $observation['marque'],
                ]);

                $vehicleModel = VehicleModel::firstOrCreate([
                    'marque_id' => $marque->id,
                    'libelle' => $observation['model'],
                ]);

                $part = Part::firstOrCreate([
                    'libelle' => $observation['part'],
                ]);

                $source = Source::where('libelle', $observation['source'])->firstOrFail();

                $identity = [
                    'model_id' => $vehicleModel->id,
                    'part_id' => $part->id,
                    'source_id' => $source->id,
                    'production_from' => $observation['production_from'] ?? null,
                    'production_to' => $observation['production_to'] ?? null,
                    'type' => $observation['type'],
                    'price' => $observation['price'],
                    'source_reference' => $observation['source_reference'] ?? null,
                    'source_url' => $observation['source_url'] ?? null,
                    'observed_at' => $observation['observed_at'],
                ];

                $existing = PartPriceObservation::query()
                    ->where($identity)
                    ->first();

                if ($existing) {
                    $skippedDuplicates++;
                    continue;
                }

                $created = PartPriceObservation::create([
                    ...$identity,
                    'details' => $observation['details'] ?? null,
                ]);

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
}
