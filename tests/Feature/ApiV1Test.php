<?php

namespace Tests\Feature;

use App\Models\ApiClient;
use App\Models\Marque;
use App\Models\Part;
use App\Models\PartPriceObservation;
use App\Models\Source;
use App\Models\VehicleModel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class ApiV1Test extends TestCase
{
    use RefreshDatabase;

    public function test_api_requires_a_valid_bearer_token(): void
    {
        $this->getJson('/api/v1/marques')
            ->assertUnauthorized()
            ->assertJson(['message' => 'Unauthenticated.']);
    }

    public function test_marques_are_returned_without_pagination(): void
    {
        Marque::create(['libelle' => 'Renault']);
        Marque::create(['libelle' => 'Dacia']);

        $this->withHeaders($this->apiHeaders())
            ->getJson('/api/v1/marques')
            ->assertOk()
            ->assertJsonPath('data.0.libelle', 'Dacia')
            ->assertJsonPath('data.1.libelle', 'Renault')
            ->assertJsonMissingPath('meta');
    }

    public function test_models_are_paginated_globally_but_not_when_filtered_by_marque(): void
    {
        $renault = Marque::create(['libelle' => 'Renault']);
        $dacia = Marque::create(['libelle' => 'Dacia']);

        VehicleModel::create(['marque_id' => $renault->id, 'libelle' => 'Clio']);
        VehicleModel::create(['marque_id' => $renault->id, 'libelle' => 'Mégane']);
        VehicleModel::create(['marque_id' => $dacia->id, 'libelle' => 'Duster']);

        $headers = $this->apiHeaders();

        $this->withHeaders($headers)
            ->getJson('/api/v1/models?per_page=2')
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('meta.per_page', 2)
            ->assertJsonPath('meta.total', 3);

        $this->withHeaders($headers)
            ->getJson('/api/v1/models?marque_id='.$renault->id)
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonMissingPath('meta');
    }

    public function test_parts_return_all_parts_or_only_parts_available_for_a_model(): void
    {
        [, $clio, $pareChoc, , $source] = $this->catalog();

        $this->observation($clio, $pareChoc, $source, 2018, 2022, 'adaptable', 800);

        $headers = $this->apiHeaders();

        $this->withHeaders($headers)
            ->getJson('/api/v1/parts')
            ->assertOk()
            ->assertJsonCount(2, 'data');

        $this->withHeaders($headers)
            ->getJson('/api/v1/parts?model_id='.$clio->id)
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $pareChoc->id);
    }

    public function test_sources_are_returned_without_pagination(): void
    {
        Source::create(['libelle' => 'AtlasAuto']);
        Source::create(['libelle' => 'Pieces Auto Maroc']);

        $this->withHeaders($this->apiHeaders())
            ->getJson('/api/v1/sources')
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonMissingPath('meta');
    }

    public function test_estimation_uses_exclusive_production_to_boundary_and_source_filter(): void
    {
        [$renault, $clio, $pareChoc, , $atlas] = $this->catalog();
        $pieces = Source::create(['libelle' => 'Pieces Auto Maroc']);

        $this->observation($clio, $pareChoc, $atlas, 2014, 2018, 'adaptable', 500);
        $this->observation($clio, $pareChoc, $atlas, 2018, 2022, 'adaptable', 900);
        $this->observation($clio, $pareChoc, $pieces, 2018, 2022, 'adaptable', 1100);
        $this->observation($clio, $pareChoc, $atlas, null, null, 'adaptable', 2000);
        $this->observation($clio, $pareChoc, $atlas, 2018, 2022, 'original', 3000);

        $query = http_build_query([
            'marque_id' => $renault->id,
            'model_id' => $clio->id,
            'part_id' => $pareChoc->id,
            'type' => 'adaptable',
            'year' => 2018,
            'source_id' => $atlas->id,
        ]);

        $this->withHeaders($this->apiHeaders())
            ->getJson('/api/v1/estimation?'.$query)
            ->assertOk()
            ->assertJsonPath('data.estimation.median', 900)
            ->assertJsonPath('data.estimation.average', 900)
            ->assertJsonPath('data.estimation.minimum', 900)
            ->assertJsonPath('data.estimation.maximum', 900)
            ->assertJsonPath('data.estimation.observation_count', 1)
            ->assertJsonPath('data.year', 2018)
            ->assertJsonPath('data.source.id', $atlas->id);
    }

    public function test_estimation_returns_null_when_no_observation_matches(): void
    {
        [$renault, $clio, $pareChoc] = $this->catalog();

        $query = http_build_query([
            'marque_id' => $renault->id,
            'model_id' => $clio->id,
            'part_id' => $pareChoc->id,
            'type' => 'occasion',
            'year' => 2018,
        ]);

        $this->withHeaders($this->apiHeaders())
            ->getJson('/api/v1/estimation?'.$query)
            ->assertOk()
            ->assertExactJson(['data' => null]);
    }

    public function test_estimation_rejects_a_model_from_another_marque(): void
    {
        [$renault, , $pareChoc] = $this->catalog();
        $peugeot = Marque::create(['libelle' => 'Peugeot']);
        $model208 = VehicleModel::create(['marque_id' => $peugeot->id, 'libelle' => '208']);

        $query = http_build_query([
            'marque_id' => $renault->id,
            'model_id' => $model208->id,
            'part_id' => $pareChoc->id,
            'type' => 'adaptable',
        ]);

        $this->withHeaders($this->apiHeaders())
            ->getJson('/api/v1/estimation?'.$query)
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['model_id']);
    }

    public function test_observations_are_paginated_and_filterable_by_source(): void
    {
        [$renault, $clio, $pareChoc, , $atlas] = $this->catalog();
        $pieces = Source::create(['libelle' => 'Pieces Auto Maroc']);

        $this->observation($clio, $pareChoc, $atlas, 2018, 2022, 'adaptable', 800);
        $this->observation($clio, $pareChoc, $atlas, 2018, 2022, 'adaptable', 900);
        $this->observation($clio, $pareChoc, $pieces, 2018, 2022, 'adaptable', 1000);

        $query = http_build_query([
            'marque_id' => $renault->id,
            'model_id' => $clio->id,
            'part_id' => $pareChoc->id,
            'type' => 'adaptable',
            'year' => 2018,
            'source_id' => $atlas->id,
            'per_page' => 1,
        ]);

        $this->withHeaders($this->apiHeaders())
            ->getJson('/api/v1/observations?'.$query)
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.source.id', $atlas->id)
            ->assertJsonPath('meta.per_page', 1)
            ->assertJsonPath('meta.total', 2)
            ->assertJsonPath('meta.last_page', 2);
    }

    public function test_api_rate_limit_is_applied_per_token(): void
    {
        config(['autoestimate.api_rate_limit_per_minute' => 2]);

        $headers = $this->apiHeaders();

        $this->withHeaders($headers)->getJson('/api/v1/marques')->assertOk();
        $this->withHeaders($headers)->getJson('/api/v1/marques')->assertOk();
        $this->withHeaders($headers)->getJson('/api/v1/marques')->assertStatus(429);
    }

    private function apiHeaders(): array
    {
        $plainTextToken = 'test_'.Str::random(48);

        ApiClient::create([
            'name' => 'test-client-'.Str::random(12),
            'token_hash' => hash('sha256', $plainTextToken),
            'is_active' => true,
        ]);

        return [
            'Authorization' => 'Bearer '.$plainTextToken,
            'Accept' => 'application/json',
        ];
    }

    private function catalog(): array
    {
        $renault = Marque::create(['libelle' => 'Renault']);
        $clio = VehicleModel::create([
            'marque_id' => $renault->id,
            'libelle' => 'Clio',
        ]);
        $pareChoc = Part::create(['libelle' => 'Pare-choc Avant']);
        $capot = Part::create(['libelle' => 'Capot']);
        $atlas = Source::create(['libelle' => 'AtlasAuto']);

        return [$renault, $clio, $pareChoc, $capot, $atlas];
    }

    private function observation(
        VehicleModel $model,
        Part $part,
        Source $source,
        ?int $productionFrom,
        ?int $productionTo,
        string $type,
        float $price,
    ): PartPriceObservation {
        return PartPriceObservation::create([
            'model_id' => $model->id,
            'part_id' => $part->id,
            'source_id' => $source->id,
            'production_from' => $productionFrom,
            'production_to' => $productionTo,
            'type' => $type,
            'price' => $price,
            'source_reference' => null,
            'source_url' => null,
            'observed_at' => now(),
        ]);
    }
}
