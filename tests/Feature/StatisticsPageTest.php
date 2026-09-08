<?php

namespace Tests\Feature;

use App\Models\Marque;
use App\Models\Part;
use App\Models\PartPriceObservation;
use App\Models\Source;
use App\Models\VehicleModel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StatisticsPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_statistics_page_reports_database_totals_activity_and_coverage(): void
    {
        $marque = Marque::create(['libelle' => 'Renault']);

        $observedModel = VehicleModel::create([
            'marque_id' => $marque->id,
            'libelle' => 'Clio',
        ]);

        VehicleModel::create([
            'marque_id' => $marque->id,
            'libelle' => 'Mégane',
        ]);

        $observedPart = Part::create(['libelle' => 'Pare-choc Avant']);
        Part::create(['libelle' => 'Capot']);

        $source = Source::create(['libelle' => 'Pieces Auto Maroc']);

        PartPriceObservation::create([
            'model_id' => $observedModel->id,
            'part_id' => $observedPart->id,
            'source_id' => $source->id,
            'production_from' => 2012,
            'production_to' => 2016,
            'type' => 'original',
            'price' => 1200,
            'source_reference' => null,
            'source_url' => null,
            'observed_at' => now()->subDay(),
        ]);

        $response = $this->get('/internal/statistics');

        $response
            ->assertOk()
            ->assertSee('Statistics')
            ->assertViewHas('summary', fn (array $summary): bool => $summary === [
                'marques' => 1,
                'models' => 2,
                'parts' => 2,
                'sources' => 1,
                'observations' => 1,
            ])
            ->assertViewHas('activity', fn (array $activity): bool => $activity === [
                'today' => 1,
                'week' => 1,
                'month' => 1,
            ])
            ->assertViewHas(
                'typeCounts',
                fn ($typeCounts): bool => $typeCounts->all() === [
                    'original' => 1,
                    'adaptable' => 0,
                    'occasion' => 0,
                ]
            )
            ->assertViewHas('coverage', fn (array $coverage): bool => $coverage === [
                'models_without_observations' => 1,
                'parts_with_few_observations' => 2,
                'low_observation_threshold' => 5,
            ]);
    }
}
